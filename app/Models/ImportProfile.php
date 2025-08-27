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
        'template_id',
        'name',
        'description',
        'source_type',
        'connection_config',
        'configuration',
        'import_mode',
        'enable_scheduling',
        'schedule_frequency',
        'schedule_time',
        'schedule_days',
        'last_sync_at',
        'next_sync_at',
        'is_active',
    ];

    protected $casts = [
        'connection_config' => 'array',
        'configuration' => 'array',
        'schedule_days' => 'array',
        'last_sync_at' => 'datetime',
        'next_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'enable_scheduling' => 'boolean',
    ];

    protected $hidden = [
        'password',
    ];

    protected $appends = [
        'has_custom_queries',
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
        // Return the stored connection_config JSON or default structure
        $config = $this->connection_config ?? [];
        
        // For API profiles (like FreeScout API)
        if ($this->source_type === 'api') {
            return [
                'host' => $config['host'] ?? null,
                'port' => $config['port'] ?? 443,
                'api_key' => $config['api_key'] ?? null,
                'password' => $config['api_key'] ?? null, // Legacy compatibility
                'ssl_mode' => $config['ssl_mode'] ?? 'require',
                'test_result' => $config['test_result'] ?? null,
            ];
        }
        
        // For database profiles
        $driverMap = [
            'postgresql' => 'pgsql',
            'mysql' => 'mysql', 
            'sqlite' => 'sqlite',
        ];
        
        $driver = $driverMap[$config['database_type'] ?? 'postgresql'] ?? 'pgsql';

        return [
            'database_type' => $config['database_type'] ?? 'postgresql',
            'driver' => $driver,
            'url' => null,
            'host' => $config['host'] ?? null,
            'port' => $config['port'] ?? 5432,
            'database' => $config['database'] ?? null,
            'username' => $config['username'] ?? null,
            'password' => $config['password'] ?? null,
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => $config['ssl_mode'] ?? 'prefer',
            'options' => array_merge([
                \PDO::ATTR_TIMEOUT => 30,
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            ], $this->connection_options ?? []),
        ];
    }

    /**
     * Get the host attribute from connection_config for backward compatibility.
     */
    public function getHostAttribute()
    {
        return $this->connection_config['host'] ?? null;
    }

    /**
     * Get the database_type for backward compatibility.
     */
    public function getDatabaseTypeAttribute()
    {
        if ($this->source_type === 'api') {
            return 'freescout_api'; // Legacy compatibility for API profiles
        }
        return $this->connection_config['database_type'] ?? 'postgresql';
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
     * Check if this profile has custom queries saved.
     */
    public function getHasCustomQueriesAttribute(): bool
    {
        return !empty($this->configuration);
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

    /**
     * Check if sync is enabled for this profile
     */
    public function isSyncEnabled(): bool
    {
        return $this->sync_enabled && $this->is_active;
    }

    /**
     * Check if the profile is due for sync
     */
    public function isDueForSync(): bool
    {
        if (!$this->isSyncEnabled()) {
            return false;
        }

        return is_null($this->next_sync_at) || $this->next_sync_at <= now();
    }

    /**
     * Get sync configuration
     */
    public function getSyncConfig(): array
    {
        return [
            'enabled' => $this->sync_enabled,
            'frequency' => $this->sync_frequency ?? 'daily',
            'time' => $this->sync_time ?? '02:00',
            'timezone' => $this->sync_timezone ?? 'UTC',
            'cron_expression' => $this->sync_cron_expression,
            'options' => $this->sync_options ?? [],
            'last_sync_at' => $this->last_sync_at,
            'next_sync_at' => $this->next_sync_at,
        ];
    }

    /**
     * Get default sync options
     */
    public function getDefaultSyncOptions(): array
    {
        return $this->sync_options ?? [
            'update_existing' => $this->shouldUpdateDuplicates(),
            'skip_existing' => $this->shouldSkipDuplicates(),
            'batch_size' => 100,
            'max_records_per_run' => null,
            'error_threshold' => 10,
            'timeout_minutes' => 30,
            'notification_on_failure' => true,
            'import_filters' => [],
        ];
    }

    /**
     * Update sync configuration
     */
    public function updateSyncConfig(array $config): void
    {
        if (isset($config['enabled'])) {
            $this->sync_enabled = $config['enabled'];
        }
        
        if (isset($config['frequency'])) {
            $this->sync_frequency = $config['frequency'];
        }
        
        if (isset($config['time'])) {
            $this->sync_time = $config['time'];
        }
        
        if (isset($config['timezone'])) {
            $this->sync_timezone = $config['timezone'];
        }
        
        if (isset($config['cron_expression'])) {
            $this->sync_cron_expression = $config['cron_expression'];
        }
        
        if (isset($config['options'])) {
            $this->sync_options = array_merge($this->getDefaultSyncOptions(), $config['options']);
        }

        $this->save();
    }

    /**
     * Calculate the next sync time
     */
    public function calculateNextSyncTime(): ?\Carbon\Carbon
    {
        if (!$this->isSyncEnabled()) {
            return null;
        }

        $timezone = $this->sync_timezone ?? 'UTC';
        $syncTime = $this->sync_time ?? '02:00';
        
        $now = now()->setTimezone($timezone);
        
        switch ($this->sync_frequency) {
            case 'hourly':
                return $now->addHour();
                
            case 'daily':
                $next = $now->copy()->addDay();
                [$hour, $minute] = explode(':', $syncTime);
                return $next->setTime((int)$hour, (int)$minute, 0);
                
            case 'weekly':
                $next = $now->copy()->addWeek()->startOfWeek();
                [$hour, $minute] = explode(':', $syncTime);
                return $next->setTime((int)$hour, (int)$minute, 0);
                
            case 'monthly':
                $next = $now->copy()->addMonth()->startOfMonth();
                [$hour, $minute] = explode(':', $syncTime);
                return $next->setTime((int)$hour, (int)$minute, 0);
                
            case 'custom':
                // For custom cron expressions, would need a cron parser library
                // Fall back to daily for now
                return $now->copy()->addDay();
                
            default:
                return $now->copy()->addDay();
        }
    }

    /**
     * Get last sync statistics
     */
    public function getLastSyncStats(): ?array
    {
        return $this->sync_stats['last_sync'] ?? null;
    }

    /**
     * Get sync history
     */
    public function getSyncHistory(): array
    {
        return $this->sync_stats['history'] ?? [];
    }

    /**
     * Check if last sync was successful
     */
    public function wasLastSyncSuccessful(): bool
    {
        $lastSync = $this->getLastSyncStats();
        if (!$lastSync) {
            return false;
        }

        return ($lastSync['records_failed'] ?? 0) === 0;
    }

    /**
     * Duplicate this import profile with a new name
     */
    public function duplicate(string $newName, ?string $newDescription = null): self
    {
        // Get all attributes except ID and timestamps
        $attributes = $this->getAttributes();
        
        // Remove fields that shouldn't be copied
        unset($attributes['id']);
        unset($attributes['created_at']);
        unset($attributes['updated_at']);
        
        // Reset sync tracking fields for the duplicate
        $attributes['last_sync_at'] = null;
        $attributes['next_sync_at'] = null;
        $attributes['sync_stats'] = null;
        $attributes['import_stats'] = null;
        $attributes['last_tested_at'] = null;
        $attributes['last_test_result'] = null;
        
        // Set new name and description
        $attributes['name'] = $newName;
        if ($newDescription !== null) {
            $attributes['description'] = $newDescription;
        } else {
            $attributes['description'] = 'Copy of ' . $this->name;
        }
        
        // Set created_by to current user if available
        if (auth()->check()) {
            $attributes['created_by'] = auth()->id();
        }
        
        // Create the duplicate
        $duplicate = static::create($attributes);
        
        // Copy related mappings if they exist (for legacy mapping-based profiles)
        foreach ($this->importMappings as $mapping) {
            $mappingAttributes = $mapping->getAttributes();
            unset($mappingAttributes['id']);
            unset($mappingAttributes['created_at']);
            unset($mappingAttributes['updated_at']);
            $mappingAttributes['profile_id'] = $duplicate->id;
            
            $duplicate->importMappings()->create($mappingAttributes);
        }
        
        // Copy import queries if they exist (for query-based profiles)  
        foreach ($this->queries as $query) {
            $queryAttributes = $query->getAttributes();
            unset($queryAttributes['id']);
            unset($queryAttributes['created_at']);
            unset($queryAttributes['updated_at']);
            $queryAttributes['profile_id'] = $duplicate->id;
            
            $duplicate->queries()->create($queryAttributes);
        }
        
        return $duplicate->fresh();
    }
}
