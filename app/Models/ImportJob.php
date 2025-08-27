<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    /** @use HasFactory<\Database\Factories\ImportJobFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'profile_id',
        'status',
        'mode',
        'mode_config',
        'total_records',
        'processed_records',
        'successful_records',
        'failed_records',
        'skipped_records',
        'updated_records',
        'errors',
        'started_at',
        'completed_at',
        'duration',
        'started_by',
    ];

    protected $casts = [
        'mode_config' => 'array',
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'successful_records' => 'integer',
        'failed_records' => 'integer',
        'skipped_records' => 'integer',
        'updated_records' => 'integer',
        'duration' => 'integer',
    ];

    protected $appends = ['progress', 'import_options'];

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
        return $this->status === 'completed' && $this->failed_records === 0;
    }

    /**
     * Get the calculated duration of the job in seconds.
     */
    public function getCalculatedDurationAttribute(): ?int
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
     * Get progress attribute (calculated from processed/total records).
     */
    public function getProgressAttribute(): int
    {
        if ($this->total_records > 0) {
            return (int) round(($this->processed_records / $this->total_records) * 100);
        }
        return 0;
    }
    
    /**
     * Get import_options attribute (maps mode_config for backward compatibility).
     */
    public function getImportOptionsAttribute(): ?array
    {
        return $this->mode_config;
    }

    /**
     * Get the success rate as a percentage.
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->processed_records === 0) {
            return 0;
        }

        return round(($this->successful_records / $this->processed_records) * 100, 2);
    }

    /**
     * Mark the job as started.
     */
    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        $this->broadcastStatusChange('job_started');
    }

    /**
     * Mark the job as completed.
     */
    public function markAsCompleted(): void
    {
        $duration = $this->started_at ? (int) now()->diffInSeconds($this->started_at) : null;
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'duration' => $duration,
        ]);

        $this->broadcastStatusChange('job_completed');
    }

    /**
     * Mark the job as failed.
     */
    public function markAsFailed(?string $error = null): void
    {
        $duration = $this->started_at ? (int) now()->diffInSeconds($this->started_at) : null;
        $errors = $this->errors ?: [];
        if ($error) {
            $errors[] = $error;
        }
        
        $this->update([
            'status' => 'failed',
            'completed_at' => now(),
            'duration' => $duration,
            'errors' => $errors,
        ]);

        $this->broadcastStatusChange('job_failed');
    }

    /**
     * Update job progress with real-time broadcasting.
     * Supports both new signature (processed, total, operation) and legacy signature (percentage, operation).
     */
    public function updateProgress(int $processedOrPercentage, $totalOrOperation = null, ?string $operation = null): void
    {
        // Handle legacy signature: updateProgress(percentage, 'operation')
        if (is_string($totalOrOperation) && $operation === null) {
            // Legacy call: (percentage, operation)
            $percentage = $processedOrPercentage;
            // Don't update database for legacy percentage calls, just broadcast
            broadcast(new \App\Events\ImportProgressUpdated($this))->toOthers();
            return;
        }
        
        // Handle new signature: updateProgress(processed, total, operation)
        $updateData = ['processed_records' => $processedOrPercentage];

        if (is_int($totalOrOperation) && $totalOrOperation > 0) {
            $updateData['total_records'] = $totalOrOperation;
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
