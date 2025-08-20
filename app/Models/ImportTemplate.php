<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\ImportTemplateFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'platform',
        'description',
        'database_type',
        'configuration',
        'is_system',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'configuration' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the user who created this template.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the import profiles using this template.
     */
    public function profiles(): HasMany
    {
        return $this->hasMany(ImportProfile::class, 'template_id');
    }

    /**
     * Scope to get active templates.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get system templates.
     */
    public function scopeSystem($query)
    {
        return $query->where('is_system', true);
    }

    /**
     * Scope to get templates for a specific platform.
     */
    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    /**
     * Scope to get templates for a specific database type.
     */
    public function scopeForDatabaseType($query, string $databaseType)
    {
        return $query->where('database_type', $databaseType);
    }

    /**
     * Get suggested queries from template configuration.
     */
    public function getSuggestedQueries(): array
    {
        return $this->configuration['queries'] ?? [];
    }

    /**
     * Get default settings from template configuration.
     */
    public function getDefaultSettings(): array
    {
        return $this->configuration['settings'] ?? [];
    }

    /**
     * Get template metadata.
     */
    public function getMetadata(): array
    {
        return $this->configuration['metadata'] ?? [];
    }
}
