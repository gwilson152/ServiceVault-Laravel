<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportQuery extends Model
{
    /** @use HasFactory<\Database\Factories\ImportQueryFactory> */
    use HasFactory, HasUuids;

    protected $fillable = [
        'profile_id',
        'name',
        'description',
        'base_table',
        'joins',
        'where_conditions',
        'select_fields',
        'order_by',
        'limit_clause',
        'destination_table',
        'field_mappings',
        'transformation_rules',
        'validation_rules',
        'import_order',
        'is_active',
    ];

    protected $casts = [
        'joins' => 'array',
        'select_fields' => 'array',
        'field_mappings' => 'array',
        'transformation_rules' => 'array',
        'validation_rules' => 'array',
        'import_order' => 'integer',
        'limit_clause' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Get the import profile this query belongs to.
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(ImportProfile::class, 'profile_id');
    }

    /**
     * Scope to get active queries.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get queries for a specific profile.
     */
    public function scopeForProfile($query, string $profileId)
    {
        return $query->where('profile_id', $profileId);
    }

    /**
     * Scope to get queries ordered by import order.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('import_order')->orderBy('name');
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
     * Get validation rule for a field.
     */
    public function getValidationRule(string $field): ?array
    {
        return $this->validation_rules[$field] ?? null;
    }

    /**
     * Set validation rule for a field.
     */
    public function setValidationRule(string $field, array $rule): void
    {
        $rules = $this->validation_rules ?? [];
        $rules[$field] = $rule;
        $this->validation_rules = $rules;
    }

    /**
     * Add a JOIN to the query.
     */
    public function addJoin(string $table, string $type, string $on, ?string $alias = null): void
    {
        $joins = $this->joins ?? [];
        $joins[] = [
            'table' => $table,
            'type' => $type,
            'on' => $on,
            'alias' => $alias,
        ];
        $this->joins = $joins;
    }

    /**
     * Remove a JOIN from the query.
     */
    public function removeJoin(int $index): void
    {
        $joins = $this->joins ?? [];
        if (isset($joins[$index])) {
            unset($joins[$index]);
            $this->joins = array_values($joins);
        }
    }

    /**
     * Get all JOIN tables referenced in this query.
     */
    public function getJoinTables(): array
    {
        $tables = [$this->base_table];
        foreach ($this->joins ?? [] as $join) {
            $tables[] = $join['alias'] ?? $join['table'];
        }
        return $tables;
    }
}
