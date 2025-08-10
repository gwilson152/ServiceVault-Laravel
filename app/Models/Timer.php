<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Timer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'project_id',
        'task_id',
        'billing_rate_id',
        'ticket_id',
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
     * Get the project associated with the timer.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
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
        $totalDuration = $this->started_at->diffInSeconds($endTime);
        
        // Subtract paused duration
        $pausedDuration = $this->total_paused_duration ?? 0;
        
        // If currently paused, add the current pause duration
        if ($this->status === 'paused' && $this->paused_at) {
            $pausedDuration += now()->diffInSeconds($this->paused_at);
        }
        
        return max(0, $totalDuration - $pausedDuration);
    }

    /**
     * Get the formatted duration.
     *
     * @return string
     */
    public function getDurationFormattedAttribute(): string
    {
        $duration = $this->duration;
        
        $hours = floor($duration / 3600);
        $minutes = floor(($duration % 3600) / 60);
        $seconds = $duration % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        
        return sprintf('%02d:%02d', $minutes, $seconds);
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

        $hours = $this->duration / 3600;
        
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
     * Scope a query to only include timers for a specific project.
     */
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
    }

    /**
     * Scope a query to only include timers for a specific service ticket.
     */
    public function scopeForServiceTicket($query, $serviceTicketId)
    {
        return $query->where('service_ticket_id', $serviceTicketId);
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
            $this->total_paused_duration += now()->diffInSeconds($this->paused_at);
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
            $this->total_paused_duration += now()->diffInSeconds($this->paused_at);
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
            'project_id' => $this->project_id,
            'task_id' => $this->task_id,
            'billing_rate_id' => $this->billing_rate_id,
            'service_ticket_id' => $this->service_ticket_id,
            'description' => $this->description,
            'started_at' => $this->started_at,
            'ended_at' => $this->stopped_at,
            'duration' => $this->duration,
            'billable' => true,
            'status' => 'pending',
        ], $additionalData));

        $this->time_entry_id = $timeEntry->id;
        $this->save();

        return $timeEntry;
    }
}