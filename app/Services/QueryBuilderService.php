<?php

namespace App\Services;

use App\Models\ImportQuery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Exception;

class QueryBuilderService
{
    protected PostgreSQLConnectionService $connectionService;

    public function __construct(PostgreSQLConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Build a complete SQL query from an ImportQuery definition.
     */
    public function buildQuery(ImportQuery $query): string
    {
        $sql = "SELECT ";
        
        // Build SELECT clause
        if ($query->select_fields && !empty($query->select_fields)) {
            $sql .= implode(', ', $query->select_fields);
        } else {
            // Default to all fields from base table and joined tables
            $sql .= $this->buildDefaultSelectClause($query);
        }
        
        // FROM clause
        $sql .= " FROM {$query->base_table}";
        
        // JOIN clauses
        if ($query->joins && !empty($query->joins)) {
            foreach ($query->joins as $join) {
                $sql .= $this->buildJoinClause($join);
            }
        }
        
        // WHERE clause
        if ($query->where_conditions) {
            $sql .= " WHERE " . $query->where_conditions;
        }
        
        // ORDER BY clause
        if ($query->order_by) {
            $sql .= " ORDER BY " . $query->order_by;
        }
        
        // LIMIT clause
        if ($query->limit_clause) {
            $sql .= " LIMIT " . $query->limit_clause;
        }
        
        return $sql;
    }

    /**
     * Build a count query to get total records.
     */
    public function buildCountQuery(ImportQuery $query): string
    {
        $sql = "SELECT COUNT(*) as count FROM {$query->base_table}";
        
        // JOIN clauses
        if ($query->joins && !empty($query->joins)) {
            foreach ($query->joins as $join) {
                $sql .= $this->buildJoinClause($join);
            }
        }
        
        // WHERE clause
        if ($query->where_conditions) {
            $sql .= " WHERE " . $query->where_conditions;
        }
        
        return $sql;
    }

    /**
     * Execute a query and return results.
     */
    public function executeQuery(string $connectionName, ImportQuery $query): Collection
    {
        $sql = $this->buildQuery($query);
        $results = DB::connection($connectionName)->select($sql);
        
        return collect($results)->map(function ($result) {
            return (array) $result;
        });
    }

    /**
     * Validate that a query is syntactically correct.
     */
    public function validateQuery(ImportQuery $query): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
            'warnings' => [],
        ];

        // Check base table is specified
        if (empty($query->base_table)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Base table is required';
        }

        // Check destination table is specified
        if (empty($query->destination_table)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = 'Destination table is required';
        }

        // Validate JOIN syntax
        if ($query->joins) {
            foreach ($query->joins as $index => $join) {
                $joinValidation = $this->validateJoinDefinition($join, $index);
                if (!$joinValidation['is_valid']) {
                    $validation['is_valid'] = false;
                    $validation['errors'] = array_merge($validation['errors'], $joinValidation['errors']);
                }
            }
        }

        // Validate WHERE conditions syntax (basic check)
        if ($query->where_conditions) {
            $whereValidation = $this->validateWhereConditions($query->where_conditions);
            if (!$whereValidation['is_valid']) {
                $validation['warnings'][] = 'WHERE conditions may contain syntax errors: ' . $whereValidation['message'];
            }
        }

        // Check field mappings
        if (empty($query->field_mappings)) {
            $validation['warnings'][] = 'No field mappings defined - data may not be imported correctly';
        }

        return $validation;
    }

    /**
     * Detect relationships between tables in a database.
     */
    public function detectRelationships(string $connectionName, array $tables): array
    {
        $relationships = [];

        foreach ($tables as $table) {
            $foreignKeys = $this->connectionService->getTableForeignKeys($connectionName, $table);
            
            foreach ($foreignKeys as $fk) {
                $relationships[] = [
                    'source_table' => $table,
                    'source_column' => $fk->column_name,
                    'target_table' => $fk->foreign_table_name,
                    'target_column' => $fk->foreign_column_name,
                    'constraint_name' => $fk->constraint_name,
                    'suggested_join_type' => 'LEFT JOIN', // Default suggestion
                ];
            }
        }

        return $relationships;
    }

    /**
     * Suggest optimal JOINs for a base table.
     */
    public function suggestJoins(string $connectionName, string $baseTable): array
    {
        $suggestions = [];
        
        // Get foreign keys from the base table
        $foreignKeys = $this->connectionService->getTableForeignKeys($connectionName, $baseTable);
        
        foreach ($foreignKeys as $fk) {
            $suggestions[] = [
                'table' => $fk->foreign_table_name,
                'type' => 'LEFT JOIN',
                'on' => "{$baseTable}.{$fk->column_name} = {$fk->foreign_table_name}.{$fk->foreign_column_name}",
                'alias' => null,
                'description' => "Join {$fk->foreign_table_name} via {$fk->column_name}",
                'confidence' => 'high',
            ];
        }

        // Find tables that reference the base table
        $allTables = $this->connectionService->getTables($connectionName);
        
        foreach ($allTables as $table) {
            $tableName = $table->table_name ?? $table['table_name'];
            
            if ($tableName === $baseTable) continue;
            
            $tableFks = $this->connectionService->getTableForeignKeys($connectionName, $tableName);
            
            foreach ($tableFks as $fk) {
                if ($fk->foreign_table_name === $baseTable) {
                    $suggestions[] = [
                        'table' => $tableName,
                        'type' => 'LEFT JOIN',
                        'on' => "{$baseTable}.{$fk->foreign_column_name} = {$tableName}.{$fk->column_name}",
                        'alias' => null,
                        'description' => "Join {$tableName} (references {$baseTable})",
                        'confidence' => 'medium',
                    ];
                }
            }
        }

        // Sort suggestions by confidence
        usort($suggestions, function ($a, $b) {
            $confidenceOrder = ['high' => 3, 'medium' => 2, 'low' => 1];
            return ($confidenceOrder[$b['confidence']] ?? 0) - ($confidenceOrder[$a['confidence']] ?? 0);
        });

        return $suggestions;
    }

    /**
     * Get all available fields from a query (including JOINed tables).
     */
    public function getAvailableFields(string $connectionName, ImportQuery $query): array
    {
        $fields = [];

        // Get fields from base table
        $baseTableFields = $this->connectionService->getTableColumns($connectionName, $query->base_table);
        foreach ($baseTableFields as $field) {
            $fields[] = [
                'table' => $query->base_table,
                'column' => $field->column_name,
                'full_name' => "{$query->base_table}.{$field->column_name}",
                'data_type' => $field->data_type,
                'is_nullable' => $field->is_nullable === 'YES',
            ];
        }

        // Get fields from joined tables
        if ($query->joins) {
            foreach ($query->joins as $join) {
                $tableName = $join['table'];
                $alias = $join['alias'] ?? $tableName;
                
                try {
                    $joinTableFields = $this->connectionService->getTableColumns($connectionName, $tableName);
                    foreach ($joinTableFields as $field) {
                        $fields[] = [
                            'table' => $tableName,
                            'alias' => $alias,
                            'column' => $field->column_name,
                            'full_name' => "{$alias}.{$field->column_name}",
                            'data_type' => $field->data_type,
                            'is_nullable' => $field->is_nullable === 'YES',
                        ];
                    }
                } catch (Exception $e) {
                    // Table might not exist, skip
                    continue;
                }
            }
        }

        return $fields;
    }

    /**
     * Build default SELECT clause when no specific fields are selected.
     */
    protected function buildDefaultSelectClause(ImportQuery $query): string
    {
        $selects = ["{$query->base_table}.*"];
        
        if ($query->joins) {
            foreach ($query->joins as $join) {
                $alias = $join['alias'] ?? $join['table'];
                $selects[] = "{$alias}.*";
            }
        }
        
        return implode(', ', $selects);
    }

    /**
     * Build a JOIN clause from a join definition.
     */
    protected function buildJoinClause(array $join): string
    {
        $type = strtoupper($join['type'] ?? 'LEFT JOIN');
        $table = $join['table'];
        $on = $join['on'];
        $alias = $join['alias'] ?? null;
        
        $clause = " {$type} {$table}";
        
        if ($alias) {
            $clause .= " AS {$alias}";
        }
        
        $clause .= " ON {$on}";
        
        return $clause;
    }

    /**
     * Validate a single JOIN definition.
     */
    protected function validateJoinDefinition(array $join, int $index): array
    {
        $validation = [
            'is_valid' => true,
            'errors' => [],
        ];

        // Check required fields
        if (empty($join['table'])) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "JOIN {$index}: table is required";
        }

        if (empty($join['on'])) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "JOIN {$index}: ON condition is required";
        }

        // Validate JOIN type
        $validJoinTypes = ['INNER JOIN', 'LEFT JOIN', 'RIGHT JOIN', 'FULL OUTER JOIN'];
        $joinType = strtoupper($join['type'] ?? 'LEFT JOIN');
        
        if (!in_array($joinType, $validJoinTypes)) {
            $validation['is_valid'] = false;
            $validation['errors'][] = "JOIN {$index}: invalid join type '{$joinType}'";
        }

        // Basic validation of ON condition (should contain = operator)
        if (!empty($join['on']) && !str_contains($join['on'], '=')) {
            $validation['errors'][] = "JOIN {$index}: ON condition should contain equality comparison";
        }

        return $validation;
    }

    /**
     * Basic validation of WHERE conditions.
     */
    protected function validateWhereConditions(string $whereConditions): array
    {
        $validation = [
            'is_valid' => true,
            'message' => '',
        ];

        // Basic checks for common SQL injection patterns
        $dangerousPatterns = [
            '/;\s*drop\s+/i',
            '/;\s*delete\s+/i',
            '/;\s*update\s+/i',
            '/;\s*insert\s+/i',
            '/;\s*create\s+/i',
            '/;\s*alter\s+/i',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $whereConditions)) {
                $validation['is_valid'] = false;
                $validation['message'] = 'WHERE conditions contain potentially dangerous SQL statements';
                break;
            }
        }

        // Check for unmatched parentheses
        $openParens = substr_count($whereConditions, '(');
        $closeParens = substr_count($whereConditions, ')');
        
        if ($openParens !== $closeParens) {
            $validation['is_valid'] = false;
            $validation['message'] = 'Unmatched parentheses in WHERE conditions';
        }

        return $validation;
    }
}