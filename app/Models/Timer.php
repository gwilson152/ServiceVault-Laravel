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
     * Calculate the duration of the timer in minutes.
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
        
        // Subtract paused duration (stored in minutes, convert to seconds for calculation)
        $pausedDurationSeconds = ($this->total_paused_duration ?? 0) * 60;
        
        // If currently paused, add the current pause duration
        if ($this->status === 'paused' && $this->paused_at) {
            $pausedDurationSeconds += now()->diffInSeconds($this->paused_at);
        }
        
        // Calculate net duration in seconds, then convert to minutes
        $netDurationSeconds = max(0, $totalDurationSeconds - $pausedDurationSeconds);
        return (int) max(0, round($netDurationSeconds / 60));
    }

    /**
     * Get the formatted duration.
     *
     * @return string
     */
    public function getDurationFormattedAttribute(): string
    {
        $durationMinutes = $this->duration;
        
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d', $hours, $minutes);
        }
        
        return sprintf('%d min', $minutes);
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

        $hours = $this->duration / 60; // Convert minutes to hours
        
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
            // Calculate paused duration in minutes and ensure it's a positive integer
            $additionalPausedSeconds = max(0, now()->diffInSeconds($this->paused_at));
            $additionalPausedMinutes = round($additionalPausedSeconds / 60);
            $this->total_paused_duration = max(0, ($this->total_paused_duration ?? 0) + $additionalPausedMinutes);
            $this->paused_at = null;
        }

        $this->status = 'stopped';
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
            // Calculate paused duration safely and ensure it's a positive integer
            $additionalPaused = max(0, now()->diffInSeconds($this->paused_at));
            $this->total_paused_duration = max(0, ($this->total_paused_duration ?? 0) + $additionalPaused);
            $this->paused_at = null;
            $this->status = 'running';
            $this->save();
        }
    }

    /**
     * Convert the timer to a time entry.
     *
     * @param array $additionalData
     * @return TimeEntry|null
     */
    public function convertToTimeEntry(array $additionalData = []): ?TimeEntry
    {
        if ($this->time_entry_id) {
            return $this->timeEntry;
        }

        if ($this->status !== 'stopped') {
            $this->stop();
        }

        $timeEntry = TimeEntry::create(array_merge([
            'user_id' => $this->user_id,
            'account_id' => $this->account_id,
            'billing_rate_id' => $this->billing_rate_id,
            'ticket_id' => $this->ticket_id,
            'description' => $this->description,
            'started_at' => $this->started_at,
            'ended_at' => $this->stopped_at,
            'duration' => $additionalData['duration'] ?? $this->duration,
            'billable' => true,
            'status' => 'pending',
        ], $additionalData));

        $this->time_entry_id = $timeEntry->id;
        $this->save();

        return $timeEntry;
    }
}