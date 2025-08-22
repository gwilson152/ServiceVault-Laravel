<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Crypt;

class ImportProfile extends Model
{
    /** @use HasFactory<\Database\Factories\ImportProfileFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'template_id',
        'database_type',
        'host',
        'port',
        'database',
        'username',
        'password',
        'ssl_mode',
        'description',
        'notes',
        'connection_options',
        'is_active',
        'created_by',
        'last_tested_at',
        'last_test_result',
        'import_mode',
        'duplicate_detection',
        'skip_duplicates',
        'update_duplicates',
        'source_identifier_field',
        'matching_strategy',
        'import_stats',
    ];

    protected $casts = [
        'connection_options' => 'array',
        'last_test_result' => 'array',
        'duplicate_detection' => 'array',
        'matching_strategy' => 'array',
        'import_stats' => 'array',
        'is_active' => 'boolean',
        'skip_duplicates' => 'boolean',
        'update_duplicates' => 'boolean',
        'port' => 'integer',
        'last_tested_at' => 'datetime',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Get the user who created this import profile.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the import jobs for this profile.
     */
    public function importJobs(): HasMany
    {
        return $this->hasMany(ImportJob::class, 'profile_id');
    }

    /**
     * Get the import records for this profile.
     */
    public function importRecords(): HasMany
    {
        return $this->hasMany(ImportRecord::class);
    }

    /**
     * Get the import mappings for this profile.
     */
    public function importMappings(): HasMany
    {
        return $this->hasMany(ImportMapping::class, 'profile_id');
    }

    /**
     * Encrypt the password when setting it.
     */
    public function setPasswordAttribute($value)
    {
        if ($value) {
            $this->attributes['password'] = Crypt::encryptString($value);
        }
    }

    /**
     * Decrypt the password when getting it.
     */
    public function getPasswordAttribute($value)
    {
        if ($value) {
            return Crypt::decryptString($value);
        }

        return null;
    }

    /**
     * Get the connection configuration array.
     */
    public function getConnectionConfig(): array
    {
        // Map our database_type to Laravel's driver names
        $driverMap = [
            'postgresql' => 'pgsql',
            'mysql' => 'mysql',
            'sqlite' => 'sqlite',
        ];

        $driver = $driverMap[$this->database_type] ?? 'pgsql';

        return [
            'database_type' => $this->database_type,
            'driver' => $driver,
            'url' => null,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => $this->ssl_mode,
            'options' => array_merge([
                \PDO::ATTR_TIMEOUT => 30,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ], $this->connection_options ?? []),
        ];
    }

    /**
     * Check if this profile has been tested recently.
     */
    public function isTestedRecently(): bool
    {
        return $this->last_tested_at && $this->last_tested_at->isAfter(now()->subHours(24));
    }

    /**
     * Check if the last connection test was successful.
     */
    public function lastTestSuccessful(): bool
    {
        return $this->last_test_result &&
               isset($this->last_test_result['success']) &&
               $this->last_test_result['success'] === true;
    }

    /**
     * Get the import template for this profile.
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ImportTemplate::class, 'template_id');
    }

    /**
     * Get the import queries for this profile.
     */
    public function queries(): HasMany
    {
        return $this->hasMany(ImportQuery::class, 'profile_id');
    }

    /**
     * Get the field mappings for this import profile.
     *
     * @deprecated Use queries() instead for the new system
     */
    public function mappings()
    {
        return $this->hasMany(ImportMapping::class, 'profile_id');
    }

    /**
     * Get the default duplicate detection configuration
     */
    public function getDefaultDuplicateDetection(): array
    {
        return $this->duplicate_detection ?? [
            'enabled' => true,
            'fields' => [$this->source_identifier_field ?? 'id'],
            'strategy' => 'exact_match',
            'case_sensitive' => false,
        ];
    }

    /**
     * Get the default matching strategy
     */
    public function getDefaultMatchingStrategy(): array
    {
        return $this->matching_strategy ?? [
            'primary_fields' => [$this->source_identifier_field ?? 'id'],
            'secondary_fields' => ['email'],
            'fuzzy_matching' => false,
            'similarity_threshold' => 0.8,
        ];
    }

    /**
     * Check if the profile should skip duplicates
     */
    public function shouldSkipDuplicates(): bool
    {
        return $this->skip_duplicates;
    }

    /**
     * Check if the profile should update duplicates
     */
    public function shouldUpdateDuplicates(): bool
    {
        return $this->update_duplicates;
    }

    /**
     * Get import mode configuration
     */
    public function getImportModeConfig(): array
    {
        return [
            'mode' => $this->import_mode ?? 'upsert',
            'skip_duplicates' => $this->shouldSkipDuplicates(),
            'update_duplicates' => $this->shouldUpdateDuplicates(),
            'source_identifier_field' => $this->source_identifier_field,
            'duplicate_detection' => $this->getDefaultDuplicateDetection(),
            'matching_strategy' => $this->getDefaultMatchingStrategy(),
        ];
    }

    /**
     * Update import statistics
     */
    public function updateImportStats(array $stats): void
    {
        $this->import_stats = array_merge($this->import_stats ?? [], [
            'last_import' => now()->toISOString(),
            'total_records' => $stats['total'] ?? 0,
            'created' => $stats['created'] ?? 0,
            'updated' => $stats['updated'] ?? 0,
            'skipped' => $stats['skipped'] ?? 0,
            'failed' => $stats['failed'] ?? 0,
        ]);
        $this->save();
    }
}
