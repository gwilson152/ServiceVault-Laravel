<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    /** @use HasFactory<\Database\Factories\ImportJobFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'status',
        'import_options',
        'mode_config',
        'started_at',
        'completed_at',
        'records_processed',
        'records_imported',
        'records_updated',
        'records_skipped',
        'records_failed',
        'summary',
        'errors',
        'progress_percentage',
        'current_operation',
        'created_by',
    ];

    protected $casts = [
        'import_options' => 'array',
        'mode_config' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'records_processed' => 'integer',
        'records_imported' => 'integer',
        'records_updated' => 'integer',
        'records_skipped' => 'integer',
        'records_failed' => 'integer',
        'progress_percentage' => 'integer',
    ];

    /**
     * Get the import profile this job belongs to.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ImportProfile::class, 'profile_id');
    }

    /**
     * Get the user who created this import job.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if the job is currently running.
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if the job is completed (successfully or with failures).
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, ['completed', 'failed']);
    }

    /**
     * Check if the job completed successfully.
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'completed' && $this->records_failed === 0;
    }

    /**
     * Get the duration of the job in seconds.
     */
    public function getDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->completed_at->diffInSeconds($this->started_at);
        }

        if ($this->started_at && $this->isRunning()) {
            return now()->diffInSeconds($this->started_at);
        }

        return null;
    }

    /**
     * Get the success rate as a percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->records_processed === 0) {
            return 0;
        }

        return round(($this->records_imported / $this->records_processed) * 100, 2);
    }

    /**
     * Mark the job as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
            'progress_percentage' => 0,
        ]);

        $this->broadcastStatusChange('job_started');
    }

    /**
     * Mark the job as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress_percentage' => 100,
            'current_operation' => null,
        ]);

        $this->broadcastStatusChange('job_completed');
    }

    /**
     * Mark the job as failed.
     */
    public function markAsFailed(?string $error = null): void
    {
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'errors' => $error ? ($this->errors ? $this->errors."\n".$error : $error) : $this->errors,
            'current_operation' => null,
        ]);

        $this->broadcastStatusChange('job_failed');
    }

    /**
     * Update job progress with real-time broadcasting.
     */
    public function updateProgress(int $percentage, ?string $operation = null): void
    {
        $updateData = ['progress_percentage' => min(100, max(0, $percentage))];

        if ($operation) {
            $updateData['current_operation'] = $operation;
        }

        $this->update($updateData);

        // Broadcast progress update in real-time
        broadcast(new \App\Events\ImportProgressUpdated($this))->toOthers();
    }

    /**
     * Broadcast job status change.
     */
    public function broadcastStatusChange(string $event = 'status_changed'): void
    {
        broadcast(new \App\Events\ImportJobStatusChanged($this, $event))->toOthers();
    }
}
