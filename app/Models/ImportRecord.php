<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_id',
        'mapping_id',
        'source_table',
        'source_id',
        'destination_table',
        'destination_id',
        'source_data',
        'import_status',
        'error_message',
    ];

    protected $casts = [
        'source_data' => 'array',
    ];

    /**
     * Get the import job this record belongs to.
     */
    public function job(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class, 'job_id');
    }

    /**
     * Get the import mapping this record used.
     */
    public function mapping(): BelongsTo
    {
        return $this->belongsTo(ImportMapping::class, 'mapping_id');
    }

    /**
     * Check if the record was successfully imported.
     */
    public function wasImported(): bool
    {
        return in_array($this->import_status, ['imported', 'updated']);
    }

    /**
     * Check if the record failed to import.
     */
    public function hasFailed(): bool
    {
        return $this->import_status === 'failed';
    }

    /**
     * Scope to get records for a specific job.
     */
    public function scopeForJob($query, int $jobId)
    {
        return $query->where('job_id', $jobId);
    }

    /**
     * Scope to get imported records.
     */
    public function scopeImported($query)
    {
        return $query->whereIn('import_status', ['imported', 'updated']);
    }

    /**
     * Scope to get failed records.
     */
    public function scopeFailed($query)
    {
        return $query->where('import_status', 'failed');
    }

    /**
     * Find existing import record by source reference.
     */
    public static function findBySource(string $sourceTable, string $sourceId): ?self
    {
        return static::where('source_table', $sourceTable)
                    ->where('source_id', $sourceId)
                    ->orderBy('created_at', 'desc')
                    ->first();
    }

    /**
     * Check if a source record has been imported before.
     */
    public static function hasBeenImported(string $sourceTable, string $sourceId): bool
    {
        return static::where('source_table', $sourceTable)
                    ->where('source_id', $sourceId)
                    ->where('import_status', '!=', 'failed')
                    ->exists();
    }
}
