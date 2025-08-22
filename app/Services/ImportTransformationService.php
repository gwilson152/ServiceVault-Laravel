<?php

namespace App\Services;

use Illuminate\Support\Str;

class ImportTransformationService
{
    /**
     * Transform an integer ID to a deterministic UUID.
     * This ensures the same integer always generates the same UUID.
     */
    public function integerToUuid(int $id, string $prefix = ''): string
    {
        // Create a deterministic UUID based on the integer ID and prefix
        $namespace = '6ba7b810-9dad-11d1-80b4-00c04fd430c8'; // Random namespace UUID
        $name = $prefix.$id;

        // Generate UUID v5 (deterministic based on namespace and name)
        return (string) Str::uuid5($namespace, $name);
    }

    /**
     * Transform a value using the specified transformation rules.
     */
    public function transform($value, array $rules, array $sourceRecord = []): mixed
    {
        switch ($rules['type']) {
            case 'integer_to_uuid':
                if (is_numeric($value)) {
                    return $this->integerToUuid((int) $value, $rules['prefix'] ?? '');
                }

                return $value;

            case 'combine_fields':
                $combined = [];
                foreach ($rules['fields'] as $field) {
                    if (isset($sourceRecord[$field]) && ! empty($sourceRecord[$field])) {
                        $combined[] = $sourceRecord[$field];
                    }
                }

                return implode($rules['separator'] ?? ' ', $combined);

            case 'role_mapping':
                return $rules['mappings'][$value] ?? $rules['default'] ?? $value;

            case 'status_mapping':
                return $rules['mappings'][$value] ?? $rules['default'] ?? $value;

            case 'default_value':
                return $rules['default'];

            case 'static_value':
                return $rules['value'];

            case 'conditional_field':
                foreach ($rules['conditions'] as $condition) {
                    if (isset($condition['default']) && $condition['default'] === true) {
                        if ($condition['type'] === 'combine_fields') {
                            return $this->transform(null, $condition, $sourceRecord);
                        }

                        return $sourceRecord[$condition['use_field']] ?? null;
                    }

                    $checkField = $condition['if_field'];
                    if (isset($condition['if_not_empty']) && $condition['if_not_empty']) {
                        if (! empty($sourceRecord[$checkField])) {
                            return $sourceRecord[$condition['use_field']];
                        }
                    }
                }

                return $value;

            case 'conditional_name':
                $primaryField = $rules['primary_field'];
                if (! empty($sourceRecord[$primaryField])) {
                    return $sourceRecord[$primaryField];
                }

                // Fallback to combined fields
                $combined = [];
                foreach ($rules['fallback_fields'] as $field) {
                    if (isset($sourceRecord[$field]) && ! empty($sourceRecord[$field])) {
                        $combined[] = $sourceRecord[$field];
                    }
                }

                return implode($rules['fallback_separator'] ?? ' ', $combined);

            case 'lookup_by_source_uuid':
                // This would need to be handled by the import executor
                // For now, return a placeholder
                $sourceId = $sourceRecord[$rules['source_field']] ?? null;
                if ($sourceId && is_numeric($sourceId)) {
                    $prefix = $rules['source_prefix'] ?? '';

                    return $this->integerToUuid((int) $sourceId, $prefix);
                }

                return null;

            case 'conditional_lookup_uuid':
                foreach ($rules['conditions'] as $condition) {
                    $checkField = $condition['if_field'];
                    if (isset($condition['if_not_null']) && $condition['if_not_null']) {
                        $sourceValue = $sourceRecord[$checkField] ?? null;
                        if ($sourceValue !== null && is_numeric($sourceValue)) {
                            $prefix = $condition['source_prefix'] ?? '';

                            return $this->integerToUuid((int) $sourceValue, $prefix);
                        }
                    }
                }

                return null;

            case 'thread_type_mapping':
                return $rules['mappings'][$value] ?? $rules['default'] ?? false;

            default:
                return $value;
        }
    }

    /**
     * Apply all transformations for a record.
     */
    public function transformRecord(array $sourceRecord, array $transformationRules): array
    {
        $transformed = [];

        foreach ($transformationRules as $targetField => $rules) {
            $sourceField = $rules['source_field'] ?? $targetField;
            $sourceValue = $sourceRecord[$sourceField] ?? null;

            $transformed[$targetField] = $this->transform($sourceValue, $rules, $sourceRecord);
        }

        return $transformed;
    }
}
