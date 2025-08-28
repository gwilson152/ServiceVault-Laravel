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
        // Legacy field names for backward compatibility
        'records_processed',
        'records_imported',
        'records_updated',
        'records_skipped',
        'records_failed',
        'summary',
        'progress_percentage',
        'current_operation',
        'created_by',
    ];

    protected $casts = [
        'mode_config' => 'array',
        'errors' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'total_records' => 'integer',
        'processed_records' => 'integer',
        'successful_records' => 'integer',
        'failed_records' => 'integer',
        'skipped_records' => 'integer',
        'updated_records' => 'integer',
        'duration' => 'integer',
        // Legacy field casts
        'records_processed' => 'integer',
        'records_imported' => 'integer',
        'records_updated' => 'integer',
        'records_skipped' => 'integer',
        'records_failed' => 'integer',
        'progress_percentage' => 'integer',
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
     * Get the user who started this import job.
     */
    public function startedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
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
        $duration = $this->started_at ? (int) $this->started_at->diffInSeconds(now()) : null;
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
        $duration = $this->started_at ? (int) $this->started_at->diffInSeconds(now()) : null;
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
            
            // Update legacy progress fields for backward compatibility
            $this->update([
                'progress_percentage' => $percentage,
                'current_operation' => $totalOrOperation
            ]);
            
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
