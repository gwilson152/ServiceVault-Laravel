<?php

namespace App\Services;

use App\Models\ImportProfile;
use App\Models\ImportQuery;
use App\Models\ImportJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Exception;

class UniversalImportService
{
    protected QueryBuilderService $queryBuilder;
    protected PostgreSQLConnectionService $connectionService;

    public function __construct(
        QueryBuilderService $queryBuilder,
        PostgreSQLConnectionService $connectionService
    ) {
        $this->queryBuilder = $queryBuilder;
        $this->connectionService = $connectionService;
    }

    /**
     * Execute import for all queries in a profile.
     */
    public function executeImport(ImportProfile $profile, array $options = []): ImportJob
    {
        // Create import job
        $job = ImportJob::create([
            'profile_id' => $profile->id,
            'status' => 'running',
            'import_options' => $options,
            'started_at' => now(),
        ]);

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            
            // Get all active queries for this profile, ordered by import_order
            $queries = $profile->queries()->active()->ordered()->get();
            
            if ($queries->isEmpty()) {
                throw new Exception('No active queries configured for this profile');
            }

            $totalRecords = 0;
            $processedRecords = 0;
            $failedRecords = 0;
            $resultSummary = [];

            foreach ($queries as $query) {
                try {
                    $result = $this->executeQuery($connectionName, $query, $options);
                    
                    $totalRecords += $result['total_count'];
                    $processedRecords += $result['processed_count'];
                    $failedRecords += $result['failed_count'];
                    
                    $resultSummary[$query->name] = $result;
                    
                    // Update job progress
                    $job->update([
                        'progress' => round(($processedRecords / max($totalRecords, 1)) * 100, 2),
                        'processed_records' => $processedRecords,
                        'failed_records' => $failedRecords,
                        'total_records' => $totalRecords,
                    ]);
                    
                } catch (Exception $e) {
                    $resultSummary[$query->name] = [
                        'error' => $e->getMessage(),
                        'processed_count' => 0,
                        'failed_count' => 0,
                        'total_count' => 0,
                    ];
                    $failedRecords++;
                }
            }

            // Complete the job
            $job->update([
                'status' => $failedRecords > 0 ? 'completed_with_errors' : 'completed',
                'completed_at' => now(),
                'result_summary' => $resultSummary,
                'progress' => 100,
            ]);

            $this->connectionService->closeConnection($connectionName);
            
        } catch (Exception $e) {
            $job->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'completed_at' => now(),
            ]);
            throw $e;
        }

        return $job;
    }

    /**
     * Execute a single import query.
     */
    protected function executeQuery(string $connectionName, ImportQuery $query, array $options = []): array
    {
        // Build and execute the SQL query
        $sql = $this->queryBuilder->buildQuery($query);
        $sourceData = $this->queryBuilder->executeQuery($connectionName, $query);
        
        $processedCount = 0;
        $failedCount = 0;
        $totalCount = $sourceData->count();

        foreach ($sourceData as $record) {
            try {
                // Transform the data according to transformation rules
                $transformedData = $this->transformRecord($record, $query);
                
                // Validate the data according to validation rules
                $this->validateRecord($transformedData, $query);
                
                // Insert into destination table
                $this->insertRecord($query->destination_table, $transformedData);
                
                $processedCount++;
                
            } catch (Exception $e) {
                $failedCount++;
                // Log the error but continue processing
                \Log::error("Failed to import record", [
                    'query' => $query->name,
                    'record' => $record,
                    'error' => $e->getMessage()
                ]);
            }
        }

        return [
            'total_count' => $totalCount,
            'processed_count' => $processedCount,
            'failed_count' => $failedCount,
            'sql_query' => $sql,
        ];
    }

    /**
     * Preview import data for a profile.
     */
    public function previewImport(ImportProfile $profile, ?ImportQuery $specificQuery = null): array
    {
        $connectionName = $this->connectionService->createConnection($profile);
        
        try {
            $preview = [];
            
            if ($specificQuery) {
                // Preview specific query
                $queries = [$specificQuery];
            } else {
                // Preview all active queries
                $queries = $profile->queries()->active()->ordered()->get();
            }

            foreach ($queries as $query) {
                try {
                    // Get sample data (limit to 10 records for preview)
                    $limitedQuery = clone $query;
                    $limitedQuery->limit_clause = 10;
                    
                    $sql = $this->queryBuilder->buildQuery($limitedQuery);
                    $sampleData = $this->queryBuilder->executeQuery($connectionName, $limitedQuery);
                    
                    // Get total count without limit
                    $totalCountSql = $this->queryBuilder->buildCountQuery($query);
                    $totalCount = DB::connection($connectionName)->selectOne($totalCountSql)->count ?? 0;
                    
                    $preview[$query->name] = [
                        'title' => $query->name,
                        'description' => $query->description ?: "Query: {$query->base_table} → {$query->destination_table}",
                        'sample_data' => $sampleData->toArray(),
                        'total_count' => $totalCount,
                        'sql_query' => $sql,
                        'destination_table' => $query->destination_table,
                        'field_mappings' => $query->field_mappings,
                    ];
                    
                } catch (Exception $e) {
                    $preview[$query->name] = [
                        'title' => $query->name,
                        'description' => "Error: " . $e->getMessage(),
                        'sample_data' => [],
                        'total_count' => 0,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            return $preview;
            
        } finally {
            $this->connectionService->closeConnection($connectionName);
        }
    }

    /**
     * Validate import data for a profile.
     */
    public function validateImportData(ImportProfile $profile, ImportQuery $query): array
    {
        $connectionName = $this->connectionService->createConnection($profile);
        
        try {
            $validation = [
                'is_valid' => true,
                'errors' => [],
                'warnings' => [],
            ];

            // Validate the query syntax
            try {
                $sql = $this->queryBuilder->buildQuery($query);
                DB::connection($connectionName)->select("EXPLAIN " . $sql);
            } catch (Exception $e) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Invalid SQL query: " . $e->getMessage();
            }

            // Validate field mappings
            $sourceFields = $this->getAvailableFields($connectionName, $query);
            $mappedFields = array_keys($query->field_mappings ?? []);
            
            foreach ($mappedFields as $field) {
                if (!in_array($field, $sourceFields)) {
                    $validation['warnings'][] = "Mapped field '{$field}' not found in query results";
                }
            }

            // Validate destination table exists
            $destinationTables = $this->getDestinationTables();
            if (!in_array($query->destination_table, $destinationTables)) {
                $validation['is_valid'] = false;
                $validation['errors'][] = "Destination table '{$query->destination_table}' does not exist";
            }

            return $validation;
            
        } finally {
            $this->connectionService->closeConnection($connectionName);
        }
    }

    /**
     * Transform a record according to transformation rules.
     */
    protected function transformRecord(array $record, ImportQuery $query): array
    {
        $transformed = [];
        
        // Apply field mappings
        foreach ($query->field_mappings ?? [] as $sourceField => $destinationField) {
            if (isset($record[$sourceField])) {
                $transformed[$destinationField] = $record[$sourceField];
            }
        }
        
        // Apply transformation rules
        foreach ($query->transformation_rules ?? [] as $field => $rule) {
            $transformed[$field] = $this->applyTransformation($transformed, $field, $rule);
        }
        
        return $transformed;
    }

    /**
     * Apply a transformation rule to a field.
     */
    protected function applyTransformation(array $data, string $field, array $rule): mixed
    {
        switch ($rule['type'] ?? 'direct') {
            case 'combine':
                $fields = $rule['fields'] ?? [];
                $separator = $rule['separator'] ?? ' ';
                $values = array_filter(array_map(fn($f) => $data[$f] ?? null, $fields));
                return implode($separator, $values);
                
            case 'uuid_convert':
                $prefix = $rule['prefix'] ?? '';
                $value = $data[$field] ?? null;
                return $value ? $prefix . $value : null;
                
            case 'static':
                return $rule['value'] ?? null;
                
            case 'format':
                $format = $rule['format'] ?? '%s';
                $value = $data[$field] ?? null;
                return $value ? sprintf($format, $value) : null;
                
            default:
                return $data[$field] ?? null;
        }
    }

    /**
     * Validate a record according to validation rules.
     */
    protected function validateRecord(array $data, ImportQuery $query): void
    {
        foreach ($query->validation_rules ?? [] as $field => $rules) {
            $value = $data[$field] ?? null;
            
            foreach ($rules as $rule) {
                switch ($rule['type']) {
                    case 'required':
                        if (empty($value)) {
                            throw new Exception("Field '{$field}' is required but empty");
                        }
                        break;
                        
                    case 'email':
                        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            throw new Exception("Field '{$field}' must be a valid email address");
                        }
                        break;
                        
                    case 'unique':
                        // Check if value already exists in destination table
                        $table = $query->destination_table;
                        $existing = DB::table($table)->where($field, $value)->exists();
                        if ($existing) {
                            throw new Exception("Value '{$value}' for field '{$field}' already exists");
                        }
                        break;
                }
            }
        }
    }

    /**
     * Insert a record into the destination table.
     */
    protected function insertRecord(string $table, array $data): void
    {
        // Add timestamps if not present
        if (!isset($data['created_at'])) {
            $data['created_at'] = now();
        }
        if (!isset($data['updated_at'])) {
            $data['updated_at'] = now();
        }
        
        DB::table($table)->insert($data);
    }

    /**
     * Get available fields from a query result.
     */
    protected function getAvailableFields(string $connectionName, ImportQuery $query): array
    {
        try {
            $limitedQuery = clone $query;
            $limitedQuery->limit_clause = 1;
            
            $sql = $this->queryBuilder->buildQuery($limitedQuery);
            $result = DB::connection($connectionName)->select($sql);
            
            if (empty($result)) {
                return [];
            }
            
            return array_keys((array) $result[0]);
            
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get available destination tables in Service Vault.
     */
    protected function getDestinationTables(): array
    {
        return [
            'users',
            'accounts',
            'tickets',
            'ticket_comments',
            'time_entries',
            'ticket_categories',
            'ticket_statuses',
            'ticket_priorities',
        ];
    }
}