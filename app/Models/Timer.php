<?php

namespace App\Models;

use App\Traits\HasUuid;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Timer extends Model
{
    use HasFactory, HasUuid;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'task_id',
        'billing_rate_id',
        'ticket_id',
        'account_id',
        'time_entry_id',
        'description',
        'ticket_number',
        'status',
        'started_at',
        'stopped_at',
        'paused_at',
        'total_paused_duration',
        'device_id',
        'is_synced',
        'metadata',
        'billable',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'stopped_at' => 'datetime',
        'paused_at' => 'datetime',
        'is_synced' => 'boolean',
        'metadata' => 'array',
        'total_paused_duration' => 'integer',
        'billable' => 'boolean',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'billable' => true,
    ];

    /**
     * The attributes that should be appended to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'duration',
        'duration_formatted',
        'is_running',
        'is_paused',
        'calculated_amount',
    ];

    /**
     * Get the user that owns the timer.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the task associated with the timer.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the billing rate associated with the timer.
     */
    public function billingRate(): BelongsTo
    {
        return $this->belongsTo(BillingRate::class);
    }

    /**
     * Get the account associated with the timer.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the ticket associated with the timer.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the time entry created from this timer.
     */
    public function timeEntry(): BelongsTo
    {
        return $this->belongsTo(TimeEntry::class);
    }

    /**
     * Calculate the duration of the timer in seconds.
     *
     * @return int
     */
    public function getDurationAttribute(): int
    {
        if (!$this->started_at) {
            return 0;
        }

        $endTime = $this->stopped_at ?? now();
        $totalDurationSeconds = $this->started_at->diffInSeconds($endTime);
        
        // Subtract paused duration (stored in seconds)
        $pausedDurationSeconds = $this->total_paused_duration ?? 0;
        
        // If currently paused, add the current pause duration
        if ($this->status === 'paused' && $this->paused_at) {
            $pausedDurationSeconds += now()->diffInSeconds($this->paused_at);
        }
        
        // Calculate net duration in seconds
        return (int) max(0, $totalDurationSeconds - $pausedDurationSeconds);
    }

    /**
     * Get the formatted duration.
     *
     * @return string
     */
    public function getDurationFormattedAttribute(): string
    {
        $durationSeconds = $this->duration;
        
        $hours = floor($durationSeconds / 3600);
        $minutes = floor(($durationSeconds % 3600) / 60);
        $seconds = $durationSeconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        } elseif ($minutes > 0) {
            return sprintf('%d:%02d', $minutes, $seconds);
        }
        
        return sprintf('%ds', $seconds);
    }

    /**
     * Check if the timer is running.
     *
     * @return bool
     */
    public function getIsRunningAttribute(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the timer is paused.
     *
     * @return bool
     */
    public function getIsPausedAttribute(): bool
    {
        return $this->status === 'paused';
    }

    /**
     * Calculate the billed amount based on duration and rate.
     *
     * @return float|null
     */
    public function getCalculatedAmountAttribute(): ?float
    {
        if (!$this->billingRate) {
            return null;
        }

        $hours = $this->duration / 3600; // Convert seconds to hours
        
        return round($hours * $this->billingRate->rate, 2);
    }

    /**
     * Scope a query to only include active timers.
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['running', 'paused']);
    }

    /**
     * Scope a query to only include running timers.
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    /**
     * Scope a query to only include stopped timers.
     */
    public function scopeStopped($query)
    {
        return $query->where('status', 'stopped');
    }

    /**
     * Scope a query to only include timers for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }


    /**
     * Scope a query to only include timers for a specific ticket.
     */
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Check if user already has an active timer for this ticket
     */
    public static function userHasActiveTimerForTicket($userId, $ticketId): bool
    {
        return self::where('user_id', $userId)
            ->where('ticket_id', $ticketId)
            ->whereIn('status', ['running', 'paused'])
            ->exists();
    }

    /**
     * Get user's active timer for a specific ticket
     */
    public static function getUserActiveTimerForTicket($userId, $ticketId): ?Timer
    {
        return self::where('user_id', $userId)
            ->where('ticket_id', $ticketId)
            ->whereIn('status', ['running', 'paused'])
            ->first();
    }

    /**
     * Stop user's active timer for a specific ticket if it exists
     */
    public static function stopUserActiveTimerForTicket($userId, $ticketId): ?Timer
    {
        $timer = self::getUserActiveTimerForTicket($userId, $ticketId);
        if ($timer) {
            $timer->stop();
            return $timer;
        }
        return null;
    }

    /**
     * Scope a query to only include timers within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Stop the timer.
     *
     * @return void
     */
    public function stop(): void
    {
        if ($this->status === 'paused' && $this->paused_at) {
            // Calculate paused duration in seconds and ensure it's a positive integer
            $additionalPausedSeconds = max(0, now()->diffInSeconds($this->paused_at));
            $this->total_paused_duration = max(0, ($this->total_paused_duration ?? 0) + $additionalPausedSeconds);
            $this->paused_at = null;
        }

        $this->status = 'canceled';
        $this->stopped_at = now();
        $this->save();
    }

    /**
     * Cancel the timer (mark as abandoned without creating time entry).
     *
     * @return void
     */
    public function cancel(): void
    {
        if ($this->status === 'paused' && $this->paused_at) {
            // Calculate paused duration in seconds and ensure it's a positive integer
            $additionalPausedSeconds = max(0, now()->diffInSeconds($this->paused_at));
            $this->total_paused_duration = max(0, ($this->total_paused_duration ?? 0) + $additionalPausedSeconds);
            $this->paused_at = null;
        }
        $this->status = 'canceled';
        $this->stopped_at = now();
        $this->save();
    }

    /**
     * Mark timer as committed (should only be called after successful time entry creation).
     *
     * @return void
     */
    public function commit(): void
    {
        if ($this->status === 'paused' && $this->paused_at) {
            // Calculate paused duration in seconds and ensure it's a positive integer
            $additionalPausedSeconds = max(0, now()->diffInSeconds($this->paused_at));
            $this->total_paused_duration = max(0, ($this->total_paused_duration ?? 0) + $additionalPausedSeconds);
            $this->paused_at = null;
        }
        $this->status = 'committed';
        $this->stopped_at = now();
        $this->save();
    }

    /**
     * Pause the timer.
     *
     * @return void
     */
    public function pause(): void
    {
        if ($this->status === 'running') {
            $this->status = 'paused';
            $this->paused_at = now();
            $this->save();
        }
    }

    /**
     * Resume the timer.
     *
     * @return void
     */
    public function resume(): void
    {
        if ($this->status === 'paused' && $this->paused_at) {
            // Calculate paused duration in seconds and ensure it's a positive integer
            $additionalPausedSeconds = max(0, now()->diffInSeconds($this->paused_at));
            $this->total_paused_duration = max(0, ($this->total_paused_duration ?? 0) + $additionalPausedSeconds);
            $this->paused_at = null;
            $this->status = 'running';
            $this->save();
        }
    }

    /**
     * Check if timer can be converted to a time entry.
     * Requires either ticket_id or account_id to be set for billing context.
     *
     * @return bool
     */
    public function canConvertToTimeEntry(): bool
    {
        return $this->ticket_id || $this->account_id;
    }

    /**
     * Get the account ID for billing purposes.
     * If timer is assigned to a ticket, use ticket's account.
     * Otherwise use timer's direct account assignment.
     *
     * @return string|null
     */
    public function getBillingAccountId(): ?string
    {
        if ($this->ticket_id && $this->ticket) {
            return $this->ticket->account_id;
        }
        
        return $this->account_id;
    }

    /**
     * Convert the timer to a time entry.
     *
     * @param array $additionalData
     * @return TimeEntry|null
     * @throws \Exception
     */
    public function convertToTimeEntry(array $additionalData = []): ?TimeEntry
    {
        if ($this->time_entry_id) {
            return $this->timeEntry;
        }

        // Validate that timer can be converted (needs ticket OR account assignment)
        if (!$this->canConvertToTimeEntry()) {
            throw new \Exception('Timer must be assigned to either a ticket or account before converting to time entry');
        }

        if ($this->status !== 'stopped') {
            $this->stop();
        }

        // Get the account ID for billing (from ticket or direct assignment)
        $billingAccountId = $this->getBillingAccountId();
        
        if (!$billingAccountId) {
            throw new \Exception('Cannot determine billing account for time entry');
        }

        // Capture the current billing rate for historical accuracy
        $currentRate = $this->billingRate?->rate;
        
        $timeEntry = TimeEntry::create(array_merge([
            'user_id' => $this->user_id,
            'account_id' => $billingAccountId, // Always required for billing
            'billing_rate_id' => $this->billing_rate_id,
            'ticket_id' => $this->ticket_id, // Optional - for ticket-specific work
            'description' => $this->description,
            'started_at' => $this->started_at,
            'ended_at' => $this->stopped_at,
            'duration' => isset($additionalData['duration']) ? $additionalData['duration'] : ceil($this->duration / 60), // Convert timer seconds to time entry minutes
            'billable' => true,
            'rate_at_time' => $currentRate, // Capture current rate for historical accuracy
            'status' => 'pending',
        ], $additionalData));

        $this->time_entry_id = $timeEntry->id;
        $this->save();

        return $timeEntry;
    }
}