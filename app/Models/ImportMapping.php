<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportMapping extends Model
{
    /** @use HasFactory<\Database\Factories\ImportMappingFactory> */
    use HasFactory;

    protected $fillable = [
        'profile_id',
        'source_table',
        'destination_table',
        'field_mappings',
        'where_conditions',
        'transformation_rules',
        'is_active',
        'import_order',
    ];

    protected $casts = [
        'field_mappings' => 'array',
        'transformation_rules' => 'array',
        'is_active' => 'boolean',
        'import_order' => 'integer',
    ];

    /**
     * Get the import profile this mapping belongs to.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ImportProfile::class, 'profile_id');
    }

    /**
     * Get the source field names from the field mappings.
     */
    public function getSourceFields(): array
    {
        return array_keys($this->field_mappings ?? []);
    }

    /**
     * Get the destination field names from the field mappings.
     */
    public function getDestinationFields(): array
    {
        return array_values($this->field_mappings ?? []);
    }

    /**
     * Get a specific field mapping.
     */
    public function getFieldMapping(string $sourceField): ?string
    {
        return $this->field_mappings[$sourceField] ?? null;
    }

    /**
     * Set a field mapping.
     */
    public function setFieldMapping(string $sourceField, string $destinationField): void
    {
        $mappings = $this->field_mappings ?? [];
        $mappings[$sourceField] = $destinationField;
        $this->field_mappings = $mappings;
    }

    /**
     * Remove a field mapping.
     */
    public function removeFieldMapping(string $sourceField): void
    {
        $mappings = $this->field_mappings ?? [];
        unset($mappings[$sourceField]);
        $this->field_mappings = $mappings;
    }

    /**
     * Get transformation rule for a field.
     */
    public function getTransformationRule(string $field): ?array
    {
        return $this->transformation_rules[$field] ?? null;
    }

    /**
     * Set transformation rule for a field.
     */
    public function setTransformationRule(string $field, array $rule): void
    {
        $rules = $this->transformation_rules ?? [];
        $rules[$field] = $rule;
        $this->transformation_rules = $rules;
    }

    /**
     * Scope to get active mappings ordered by import order.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('import_order');
    }

    /**
     * Scope to get mappings for a specific profile.
     */
    public function scopeForProfile($query, int $profileId)
    {
        return $query->where('profile_id', $profileId);
    }
}
