<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRecord extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'import_job_id',
        'import_profile_id',
        'source_table',
        'source_data',
        'source_identifier',
        'source_hash',
        'target_type',
        'target_id',
        'import_action',
        'import_mode',
        'matching_rules',
        'matching_fields',
        'duplicate_of',
        'error_message',
        'validation_errors',
        'field_mappings',
        'transformations',
    ];

    protected $casts = [
        'source_data' => 'array',
        'matching_rules' => 'array',
        'matching_fields' => 'array',
        'validation_errors' => 'array',
        'field_mappings' => 'array',
        'transformations' => 'array',
    ];

    /**
     * The import job this record belongs to
     */
    public function importJob(): BelongsTo
    {
        return $this->belongsTo(ImportJob::class);
    }

    /**
     * The import profile used for this record
     */
    public function importProfile(): BelongsTo
    {
        return $this->belongsTo(ImportProfile::class);
    }

    /**
     * The original import record this is a duplicate of
     */
    public function duplicateOf(): BelongsTo
    {
        return $this->belongsTo(ImportRecord::class, 'duplicate_of');
    }

    /**
     * Records that are duplicates of this record
     */
    public function duplicates()
    {
        return $this->hasMany(ImportRecord::class, 'duplicate_of');
    }

    /**
     * Get the target record (polymorphic relationship)
     */
    public function target()
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    /**
     * Generate a hash for duplicate detection
     */
    public static function generateHash(array $data, array $matchingFields = []): string
    {
        // Use specific fields if provided, otherwise use all data
        $hashData = empty($matchingFields) ? $data : array_intersect_key($data, array_flip($matchingFields));
        
        // Sort to ensure consistent hashing
        ksort($hashData);
        
        return hash('sha256', serialize($hashData));
    }

    /**
     * Check if this record was successfully imported
     */
    public function isSuccessful(): bool
    {
        return in_array($this->import_action, ['created', 'updated']);
    }

    /**
     * Check if this record failed to import
     */
    public function isFailed(): bool
    {
        return $this->import_action === 'failed';
    }

    /**
     * Check if this record was skipped
     */
    public function isSkipped(): bool
    {
        return $this->import_action === 'skipped';
    }

    /**
     * Check if this record is a duplicate
     */
    public function isDuplicate(): bool
    {
        return !is_null($this->duplicate_of);
    }

    /**
     * Get formatted error information
     */
    public function getFormattedErrors(): array
    {
        $errors = [];
        
        if ($this->error_message) {
            $errors['general'] = $this->error_message;
        }
        
        if ($this->validation_errors) {
            $errors['validation'] = $this->validation_errors;
        }
        
        return $errors;
    }

    /**
     * Scope to filter by import action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('import_action', $action);
    }

    /**
     * Scope to filter by import mode
     */
    public function scopeByMode($query, string $mode)
    {
        return $query->where('import_mode', $mode);
    }

    /**
     * Scope to filter by target type
     */
    public function scopeByTargetType($query, string $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    /**
     * Scope to get successful imports
     */
    public function scopeSuccessful($query)
    {
        return $query->whereIn('import_action', ['created', 'updated']);
    }

    /**
     * Scope to get failed imports
     */
    public function scopeFailed($query)
    {
        return $query->where('import_action', 'failed');
    }

    /**
     * Scope to get skipped imports
     */
    public function scopeSkipped($query)
    {
        return $query->where('import_action', 'skipped');
    }

    /**
     * Scope to get duplicates
     */
    public function scopeDuplicates($query)
    {
        return $query->whereNotNull('duplicate_of');
    }

    /**
     * Find existing import record by source reference
     */
    public static function findBySource(string $sourceTable, string $sourceIdentifier): ?self
    {
        return static::where('source_table', $sourceTable)
                    ->where('source_identifier', $sourceIdentifier)
                    ->orderBy('created_at', 'desc')
                    ->first();
    }

    /**
     * Check if a source record has been imported before
     */
    public static function hasBeenImported(string $sourceTable, string $sourceIdentifier): bool
    {
        return static::where('source_table', $sourceTable)
                    ->where('source_identifier', $sourceIdentifier)
                    ->where('import_action', '!=', 'failed')
                    ->exists();
    }
}
