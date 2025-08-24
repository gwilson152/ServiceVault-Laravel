<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EmailProcessingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'email_id',
        'account_id',
        'email_config_id',
        'direction',
        'status',
        'from_address',
        'to_addresses',
        'cc_addresses',
        'bcc_addresses',
        'subject',
        'message_id',
        'in_reply_to',
        'references',
        'received_at',
        'processed_at',
        'processing_duration_ms',
        'retry_count',
        'next_retry_at',
        'actions_taken',
        'ticket_id',
        'ticket_comment_id',
        'created_new_ticket',
        'error_message',
        'error_details',
        'error_stack_trace',
        'raw_email_content',
        'parsed_body_text',
        'parsed_body_html',
        'attachments',
        'queue_name',
        'job_id',
        'job_attempts',
    ];

    protected $casts = [
        'to_addresses' => 'array',
        'cc_addresses' => 'array',
        'bcc_addresses' => 'array',
        'references' => 'array',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'actions_taken' => 'array',
        'created_new_ticket' => 'boolean',
        'error_details' => 'array',
        'attachments' => 'array',
        'processing_duration_ms' => 'integer',
        'retry_count' => 'integer',
        'job_attempts' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->email_id)) {
                $model->email_id = (string) Str::uuid();
            }
        });
    }

    /**
     * Relationships
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function emailConfig(): BelongsTo
    {
        return $this->belongsTo(EmailConfig::class);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function ticketComment(): BelongsTo
    {
        return $this->belongsTo(TicketComment::class);
    }

    /**
     * Scopes
     */
    public function scopeIncoming($query)
    {
        return $query->where('direction', 'incoming');
    }

    public function scopeOutgoing($query)
    {
        return $query->where('direction', 'outgoing');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForAccount($query, $accountId)
    {
        return $query->where('account_id', $accountId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function scopeNeedsRetry($query)
    {
        return $query->where('status', 'retry')
                     ->where('next_retry_at', '<=', now());
    }

    /**
     * Business Logic Methods
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now(),
        ]);
    }

    public function markAsProcessed(array $actionsTaken = []): void
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
            'processing_duration_ms' => $this->getProcessingDuration(),
            'actions_taken' => $actionsTaken,
        ]);
    }

    public function markAsFailed(string $errorMessage, array $errorDetails = [], ?string $stackTrace = null): void
    {
        $this->increment('retry_count');
        
        $this->update([
            'status' => $this->shouldRetry() ? 'retry' : 'failed',
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'error_stack_trace' => $stackTrace,
            'next_retry_at' => $this->shouldRetry() ? $this->calculateNextRetry() : null,
        ]);
    }

    public function shouldRetry(): bool
    {
        return $this->retry_count < 3; // Max 3 retry attempts
    }

    public function calculateNextRetry(): \Carbon\Carbon
    {
        // Exponential backoff: 5 minutes, 15 minutes, 1 hour
        $delays = [5, 15, 60];
        $delayMinutes = $delays[min($this->retry_count, count($delays) - 1)];
        
        return now()->addMinutes($delayMinutes);
    }

    public function getProcessingDuration(): ?int
    {
        if (!$this->received_at || !$this->processed_at) {
            return null;
        }

        return $this->received_at->diffInMilliseconds($this->processed_at);
    }

    public function isIncoming(): bool
    {
        return $this->direction === 'incoming';
    }

    public function isOutgoing(): bool
    {
        return $this->direction === 'outgoing';
    }

    public function hasAttachments(): bool
    {
        return !empty($this->attachments);
    }

    public function getAttachmentCount(): int
    {
        return count($this->attachments ?? []);
    }

    public function wasSuccessful(): bool
    {
        return $this->status === 'processed';
    }

    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    public function needsRetry(): bool
    {
        return $this->status === 'retry' && $this->next_retry_at <= now();
    }

    public function getFromName(): ?string
    {
        if (strpos($this->from_address, '<') !== false) {
            preg_match('/^(.*?)\s*<(.+)>$/', $this->from_address, $matches);
            return $matches[1] ?? null;
        }

        return null;
    }

    public function getFromEmailOnly(): string
    {
        if (strpos($this->from_address, '<') !== false) {
            preg_match('/^.*?<(.+)>$/', $this->from_address, $matches);
            return $matches[1] ?? $this->from_address;
        }

        return $this->from_address;
    }

    public function getAllRecipients(): array
    {
        return array_merge(
            $this->to_addresses ?? [],
            $this->cc_addresses ?? [],
            $this->bcc_addresses ?? []
        );
    }

    /**
     * Create a new email processing log entry
     */
    public static function createFromEmailData(array $emailData, ?string $accountId = null): EmailProcessingLog
    {
        return self::create([
            'email_id' => $emailData['email_id'] ?? (string) Str::uuid(),
            'account_id' => $accountId,
            'direction' => $emailData['direction'] ?? 'incoming',
            'from_address' => $emailData['from'] ?? '',
            'to_addresses' => $emailData['to'] ?? [],
            'cc_addresses' => $emailData['cc'] ?? [],
            'bcc_addresses' => $emailData['bcc'] ?? [],
            'subject' => $emailData['subject'] ?? '',
            'message_id' => $emailData['message_id'] ?? null,
            'in_reply_to' => $emailData['in_reply_to'] ?? null,
            'references' => $emailData['references'] ?? [],
            'received_at' => $emailData['received_at'] ?? now(),
            'raw_email_content' => $emailData['raw_content'] ?? null,
            'parsed_body_text' => $emailData['body_text'] ?? null,
            'parsed_body_html' => $emailData['body_html'] ?? null,
            'attachments' => $emailData['attachments'] ?? [],
        ]);
    }
}
