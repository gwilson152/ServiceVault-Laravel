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
        'description',
        'source_type',
        'default_configuration',
        'field_mappings',
        'is_active',
    ];

    protected $casts = [
        'default_configuration' => 'array',
        'field_mappings' => 'array',
        'is_active' => 'boolean',
    ];

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
     * Scope to get templates for a specific source type.
     */
    public function scopeForSourceType($query, string $sourceType)
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Get suggested queries from template configuration.
     */
    public function getSuggestedQueries(): array
    {
        return $this->default_configuration['queries'] ?? [];
    }

    /**
     * Get default settings from template configuration.
     */
    public function getDefaultSettings(): array
    {
        return $this->default_configuration['settings'] ?? [];
    }

    /**
     * Get template metadata.
     */
    public function getMetadata(): array
    {
        return $this->default_configuration['metadata'] ?? [];
    }
}
