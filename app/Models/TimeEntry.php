<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, HasUuid, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'account_id',
        'billing_rate_id',
        'external_id',
        'ticket_id',
        'description',
        'started_at',
        'ended_at',
        'duration',
        'billable',
        'billed_amount',
        'rate_at_time',
        'rate_override',
        'approved_amount',
        'status',
        'approved_by',
        'approved_at',
        'approval_notes',
        'notes',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'approved_at' => 'datetime',
        'billable' => 'boolean',
        'billed_amount' => 'decimal:2',
        'rate_at_time' => 'decimal:2',
        'rate_override' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'metadata' => 'array',
        'duration' => 'integer',
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
     * Get the user that owns the time entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the account associated with the time entry.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * Get the billing rate associated with the time entry.
     */
    public function billingRate(): BelongsTo
    {
        return $this->belongsTo(BillingRate::class);
    }

    /**
     * Get the ticket associated with the time entry.
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who approved this time entry.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include entries for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope a query to only include entries for a specific ticket.
     */
    public function scopeForTicket($query, $ticketId)
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Scope a query to only include entries within a date range.
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('started_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include entries with a specific status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope a query to only include billable entries.
     */
    public function scopeBillable($query)
    {
        return $query->where('billable', true);
    }

    /**
     * Scope a query to only include approved entries.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Get the formatted duration (duration stored in minutes).
     */
    public function getDurationFormattedAttribute(): string
    {
        $totalMinutes = $this->duration; // Duration is stored in minutes
        $hours = floor($totalMinutes / 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d', $hours, $minutes);
        }

        return sprintf('%d min', $minutes);
    }

    /**
     * Get the effective rate (override takes precedence over rate_at_time).
     */
    public function getEffectiveRateAttribute(): ?float
    {
        return $this->rate_override ?? $this->rate_at_time;
    }

    /**
     * Calculate the cost based on duration and effective rate (duration stored in minutes).
     */
    public function getCalculatedCostAttribute(): ?float
    {
        if (! $this->billable || ! $this->effective_rate) {
            return null;
        }

        $hours = $this->duration / 60; // Convert minutes to hours

        return round($hours * $this->effective_rate, 2);
    }

    /**
     * Get the final billable amount (approved_amount if locked, otherwise calculated_cost).
     */
    public function getFinalAmountAttribute(): ?float
    {
        // If approved and amount is locked, use approved_amount
        if ($this->isApproved() && $this->approved_amount !== null) {
            return $this->approved_amount;
        }

        // Otherwise use calculated cost
        return $this->calculated_cost;
    }

    /**
     * Validate that a user can create time entries.
     * Only Agents (service providers) can create time entries.
     *
     * @throws \Exception
     */
    public static function validateUserCanCreateTimeEntry(User $user): bool
    {
        if (! $user->canCreateTimeEntries()) {
            throw new \Exception('Only Agents can create time entries. Account Users (customers) cannot log time.');
        }

        return true;
    }

    /**
     * Validate time entry data before creation.
     *
     * @throws \Exception
     */
    public static function validateTimeEntryData(array $data): bool
    {
        // Account ID is always required for billing purposes
        if (empty($data['account_id'])) {
            throw new \Exception('Time entries must be associated with a customer account for billing purposes.');
        }

        // If ticket_id is provided, ensure consistency with account_id
        if (! empty($data['ticket_id']) && ! empty($data['account_id'])) {
            $ticket = Ticket::find($data['ticket_id']);
            if ($ticket && $ticket->account_id !== $data['account_id']) {
                throw new \Exception('Time entry account must match the ticket\'s account.');
            }
        }

        return true;
    }

    /**
     * Check if the time entry can be edited.
     */
    public function canEdit(): bool
    {
        return in_array($this->status, ['draft', 'pending', 'rejected']);
    }

    /**
     * Check if the time entry is pending approval.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if the time entry is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /**
     * Approve the time entry and lock the calculated amount.
     */
    public function approve(string $approvedBy, ?string $notes = null, ?float $rateOverride = null): bool
    {
        // Apply rate override if provided
        if ($rateOverride !== null) {
            $this->rate_override = $rateOverride;
        }

        // Lock the current calculated amount at approval time
        $this->approved_amount = $this->calculated_cost;

        $this->status = 'approved';
        $this->approved_by = $approvedBy;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Reject the time entry.
     */
    public function reject(string $rejectedBy, ?string $notes = null): bool
    {
        $this->status = 'rejected';
        $this->approved_by = $rejectedBy;
        $this->approved_at = now();
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Check if the time entry is associated with any invoice.
     */
    public function isInvoiced(): bool
    {
        return $this->invoiceLineItems()->exists();
    }

    /**
     * Check if the time entry can be unapproved.
     */
    public function canUnapprove(): bool
    {
        return $this->status === 'approved' && ! $this->isInvoiced();
    }

    /**
     * Unapprove the time entry (only if not invoiced).
     */
    public function unapprove(string $unapprovedBy, ?string $notes = null): bool
    {
        if (! $this->canUnapprove()) {
            return false;
        }

        $this->status = 'pending';
        $this->approved_by = null;
        $this->approved_at = null;
        $this->approved_amount = null;
        $this->approval_notes = $notes;

        return $this->save();
    }

    /**
     * Get the invoice line items for this time entry.
     */
    public function invoiceLineItems()
    {
        return $this->hasMany(InvoiceLineItem::class);
    }
}
