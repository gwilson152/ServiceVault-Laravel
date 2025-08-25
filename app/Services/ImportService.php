<?php

namespace App\Services;

use App\Models\ImportJob;
use App\Models\ImportMapping;
use App\Models\ImportProfile;
use App\Models\ImportRecord;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ImportService
{
    protected PostgreSQLConnectionService $connectionService;

    protected DuplicateDetectionService $duplicateService;

    public function __construct(
        PostgreSQLConnectionService $connectionService,
        DuplicateDetectionService $duplicateService
    ) {
        $this->connectionService = $connectionService;
        $this->duplicateService = $duplicateService;
    }

    /**
     * Start an import job for a profile.
     */
    public function startImport(ImportProfile $profile, array $options = [], array $modeConfig = []): ImportJob
    {
        // Update profile with new mode configuration if provided
        if (! empty($modeConfig)) {
            $profile->update([
                'import_mode' => $modeConfig['import_mode'] ?? 'upsert',
                'duplicate_detection' => $modeConfig['duplicate_detection'] ?? null,
                'skip_duplicates' => $modeConfig['skip_duplicates'] ?? false,
                'update_duplicates' => $modeConfig['update_duplicates'] ?? true,
                'source_identifier_field' => $modeConfig['source_identifier_field'] ?? null,
                'matching_strategy' => $modeConfig['matching_strategy'] ?? null,
            ]);
        }

        // Create import job
        $job = ImportJob::create([
            'profile_id' => $profile->id,
            'status' => 'pending',
            'import_options' => $options,
            'mode_config' => $modeConfig,
            'created_by' => Auth::id(),
        ]);

        // Mark job as started
        $job->markAsStarted();
        $job->updateProgress(0, 'Initializing import...');

        try {
            // Create connection to source database
            $connectionName = $this->connectionService->createConnection($profile);
            $job->updateProgress(10, 'Connected to source database');

            // Check if profile has query builder configuration or mappings
            if ($profile->configuration && !empty($profile->configuration)) {
                // Use query builder configuration
                $this->processQueryBasedImport($connectionName, $profile->configuration, $job);
            } else {
                // Fall back to mapping-based import
                $mappings = $profile->importMappings()->active()->get();
                
                if ($mappings->isEmpty()) {
                    throw new Exception('No query configuration or active import mappings found for this profile. Please configure the import using the Query Builder.');
                }

                $this->processMappingBasedImport($connectionName, $mappings, $job);
            }

            // Processing is handled by the specific import method above

            // Finalize import
            $job->updateProgress(95, 'Finalizing import...');

            // Update profile statistics
            $stats = [
                'total' => $job->records_processed,
                'created' => $job->records_imported,
                'updated' => $job->records_updated ?? 0,
                'skipped' => $job->records_skipped ?? 0,
                'failed' => $job->records_failed ?? 0,
            ];
            $profile->updateImportStats($stats);

            $job->markAsCompleted();

            // Clean up connection
            $this->connectionService->closeConnection($connectionName);

            Log::info('Import job completed successfully', [
                'job_id' => $job->id,
                'profile_id' => $profile->id,
                'records_imported' => $job->records_imported,
                'import_stats' => $stats,
            ]);

        } catch (Exception $e) {
            $job->markAsFailed($e->getMessage());

            Log::error('Import job failed', [
                'job_id' => $job->id,
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }

        return $job;
    }

    /**
     * Process mapping-based import.
     */
    protected function processMappingBasedImport(string $connectionName, $mappings, ImportJob $job): void
    {
        $totalMappings = $mappings->count();
        $currentMapping = 0;

        foreach ($mappings as $mapping) {
            $progressBase = 20 + (($currentMapping / $totalMappings) * 70); // 70% of total progress
            $this->processMapping($connectionName, $mapping, $job, $progressBase);
            $currentMapping++;
        }
    }

    /**
     * Process a single import mapping.
     */
    protected function processMapping(string $connectionName, ImportMapping $mapping, ImportJob $job, float $progressBase): void
    {
        try {
            // Build query for source data
            $sourceFields = array_keys($mapping->field_mappings);
            $query = 'SELECT '.implode(', ', $sourceFields)." FROM {$mapping->source_table}";

            if ($mapping->where_conditions) {
                $query .= " WHERE {$mapping->where_conditions}";
            }

            // Get total row count for progress tracking
            $countQuery = "SELECT COUNT(*) as total FROM {$mapping->source_table}";
            if ($mapping->where_conditions) {
                $countQuery .= " WHERE {$mapping->where_conditions}";
            }

            $totalRows = $this->connectionService->getRowCount($connectionName, $mapping->source_table, $mapping->where_conditions);

            if ($totalRows === 0) {
                return; // Nothing to import for this mapping
            }

            // Process records in batches
            $processedRows = 0;
            $batchSize = 100; // Process in batches of 100 records

            foreach ($this->connectionService->executeQuery($connectionName, $query) as $sourceRecord) {
                try {
                    $this->importRecord($sourceRecord, $mapping, $job);
                    $job->records_imported++;
                } catch (Exception $e) {
                    $job->records_failed++;
                    $this->logImportError($job, $mapping, $sourceRecord, $e);
                }

                $processedRows++;
                $job->records_processed++;

                // Update progress every batch
                if ($processedRows % $batchSize === 0) {
                    $mappingProgress = ($processedRows / $totalRows) * 20; // 20% per mapping max
                    $job->updateProgress(
                        (int) ($progressBase + $mappingProgress),
                        "Processed {$processedRows}/{$totalRows} records from {$mapping->source_table}"
                    );
                }
            }

        } catch (Exception $e) {
            throw new Exception("Failed to process mapping {$mapping->source_table}: ".$e->getMessage());
        }
    }

    /**
     * Import a single record using the mapping configuration with duplicate detection.
     */
    protected function importRecord(array $sourceRecord, ImportMapping $mapping, ImportJob $job): void
    {
        $profile = $job->profile;
        $sourceId = $sourceRecord['id'] ?? null;

        if (! $sourceId) {
            throw new Exception("Source record must have an 'id' field for tracking");
        }

        // Apply field transformations
        $destinationData = [];
        foreach ($mapping->field_mappings as $sourceField => $destinationField) {
            if (isset($sourceRecord[$sourceField])) {
                $value = $sourceRecord[$sourceField];

                // Apply transformation rules if defined
                if ($transformationRule = $mapping->getTransformationRule($sourceField)) {
                    $value = $this->applyTransformation($value, $transformationRule);
                }

                $destinationData[$destinationField] = $value;
            }
        }

        // Add metadata fields
        $destinationData['created_at'] = now();
        $destinationData['updated_at'] = now();

        // Perform duplicate detection
        $duplicateResult = $this->duplicateService->detectDuplicates($sourceRecord, $profile);
        $importDecision = $this->duplicateService->shouldProceedWithImport($duplicateResult, $profile);

        // Generate source hash for tracking
        $sourceHash = ImportRecord::generateHash(
            $sourceRecord,
            $profile->getDefaultDuplicateDetection()['fields'] ?? []
        );

        $importAction = 'failed';
        $targetId = null;
        $errorMessage = null;
        $duplicateOfRecord = null;

        try {
            if (! $importDecision['proceed']) {
                // Skip this record
                $importAction = 'skipped';
                $job->records_skipped++;

                if ($duplicateResult['is_duplicate'] && ! empty($duplicateResult['matches'])) {
                    $bestMatch = $duplicateResult['matches'][0];
                    $duplicateOfRecord = $bestMatch['record']['id'] ?? null;
                }
            } else {
                // Proceed with import based on action
                switch ($importDecision['action']) {
                    case 'create':
                        $targetId = $this->createDestinationRecord($destinationData, $mapping);
                        $importAction = 'created';
                        $job->records_imported++;
                        break;

                    case 'update':
                        $existingRecord = $importDecision['duplicate_record'] ?? null;
                        if ($existingRecord && isset($existingRecord['target_id'])) {
                            $this->updateDestinationRecord($destinationData, $mapping, $existingRecord['target_id']);
                            $targetId = $existingRecord['target_id'];
                            $importAction = 'updated';
                            $job->records_updated++;
                        } else {
                            throw new Exception('Update action specified but no existing record found');
                        }
                        break;

                    default:
                        throw new Exception("Unknown import action: {$importDecision['action']}");
                }
            }
        } catch (Exception $e) {
            $importAction = 'failed';
            $errorMessage = $e->getMessage();
            $job->records_failed++;
        }

        // Create comprehensive import record for tracking
        ImportRecord::create([
            'import_job_id' => $job->id,
            'import_profile_id' => $profile->id,
            'source_table' => $mapping->source_table,
            'source_data' => $sourceRecord,
            'source_identifier' => (string) $sourceId,
            'source_hash' => $sourceHash,
            'target_type' => $mapping->destination_table,
            'target_id' => $targetId,
            'import_action' => $importAction,
            'import_mode' => $profile->import_mode ?? 'upsert',
            'matching_rules' => $duplicateResult['detection_strategy'] ?? null,
            'matching_fields' => $duplicateResult['matches'][0]['matching_fields'] ?? null,
            'duplicate_of' => $duplicateOfRecord,
            'error_message' => $errorMessage,
            'field_mappings' => $mapping->field_mappings,
        ]);
    }

    /**
     * Create a new destination record
     */
    protected function createDestinationRecord(array $data, ImportMapping $mapping): string
    {
        // For UUID tables, generate UUID
        if (in_array($mapping->destination_table, ['users', 'accounts', 'tickets', 'time_entries'])) {
            $id = \Illuminate\Support\Str::uuid();
            $data['id'] = $id;
            \DB::table($mapping->destination_table)->insert($data);

            return $id;
        } else {
            // For auto-increment tables
            return \DB::table($mapping->destination_table)->insertGetId($data);
        }
    }

    /**
     * Update an existing destination record
     */
    protected function updateDestinationRecord(array $data, ImportMapping $mapping, string $targetId): void
    {
        // Don't update created_at timestamp
        unset($data['created_at']);

        \DB::table($mapping->destination_table)
            ->where('id', $targetId)
            ->update($data);
    }

    /**
     * Apply transformation rules to a field value.
     */
    protected function applyTransformation($value, array $rule): mixed
    {
        switch ($rule['type'] ?? 'none') {
            case 'date_format':
                if ($value && isset($rule['from_format'], $rule['to_format'])) {
                    try {
                        $date = \DateTime::createFromFormat($rule['from_format'], $value);

                        return $date ? $date->format($rule['to_format']) : $value;
                    } catch (Exception $e) {
                        return $value; // Return original if transformation fails
                    }
                }
                break;

            case 'string_replace':
                if (isset($rule['search'], $rule['replace'])) {
                    return str_replace($rule['search'], $rule['replace'], $value);
                }
                break;

            case 'default_value':
                return $value ?: ($rule['default'] ?? null);

            case 'uppercase':
                return strtoupper($value);

            case 'lowercase':
                return strtolower($value);

            case 'json_decode':
                if (is_string($value)) {
                    return json_decode($value, true) ?: $value;
                }
                break;
        }

        return $value;
    }

    /**
     * Log import error for debugging.
     */
    protected function logImportError(ImportJob $job, ImportMapping $mapping, array $sourceRecord, Exception $error): void
    {
        $errorMessage = "Import error in {$mapping->source_table}: {$error->getMessage()}";
        $errorDetails = [
            'job_id' => $job->id,
            'mapping_id' => $mapping->id,
            'source_table' => $mapping->source_table,
            'destination_table' => $mapping->destination_table,
            'source_record' => $sourceRecord,
            'error' => $error->getMessage(),
        ];

        // Append to job errors
        $currentErrors = $job->errors ?? '';
        $job->errors = $currentErrors.($currentErrors ? "\n" : '').$errorMessage;
        $job->save();

        Log::warning('Record import failed', $errorDetails);
    }

    /**
     * Preview import data before execution.
     */
    public function previewImport(ImportProfile $profile, int $limit = 10): array
    {
        $connectionName = $this->connectionService->createConnection($profile);

        try {
            $preview = [];
            $mappings = $profile->importMappings()->active()->get();

            foreach ($mappings as $mapping) {
                $sourceFields = array_keys($mapping->field_mappings);
                $query = 'SELECT '.implode(', ', $sourceFields)." FROM {$mapping->source_table}";

                if ($mapping->where_conditions) {
                    $query .= " WHERE {$mapping->where_conditions}";
                }

                $query .= " LIMIT {$limit}";

                $sourceData = \DB::connection($connectionName)->select($query);

                $preview[$mapping->source_table] = [
                    'mapping' => $mapping,
                    'source_data' => $sourceData,
                    'row_count' => $this->connectionService->getRowCount(
                        $connectionName,
                        $mapping->source_table,
                        $mapping->where_conditions
                    ),
                ];
            }

            return $preview;

        } finally {
            $this->connectionService->closeConnection($connectionName);
        }
    }

    /**
     * Process query-based import using query builder configuration.
     */
    private function processQueryBasedImport(string $connectionName, array $configuration, ImportJob $job): void
    {
        try {
            // Generate SQL from configuration using the same method as the controller
            $sql = $this->generateSQL($configuration);
            
            $job->updateProgress(20, 'Generated import query');
            
            // Get total record count first
            $countSQL = "SELECT COUNT(*) as count FROM ({$sql}) as query_result";
            $countResult = \DB::connection($connectionName)->select($countSQL);
            $totalRows = (int) ($countResult[0]->count ?? 0);
            
            if ($totalRows === 0) {
                $job->updateProgress(100, 'No records found to import');
                return;
            }
            
            $job->updateProgress(30, "Found {$totalRows} records to import");
            
            // Process records in batches
            $batchSize = 100;
            $processedRows = 0;
            $offset = 0;
            
            while ($offset < $totalRows) {
                $batchSQL = $sql . " LIMIT {$batchSize} OFFSET {$offset}";
                $records = \DB::connection($connectionName)->select($batchSQL);
                
                foreach ($records as $record) {
                    try {
                        $this->importQueryRecord($record, $configuration, $job);
                        $job->records_imported++;
                    } catch (\Exception $e) {
                        $job->records_failed++;
                        \Log::warning('Failed to import record', [
                            'job_id' => $job->id,
                            'record' => $record,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $processedRows++;
                    $job->records_processed = $processedRows;
                    
                    // Update progress every 10 records
                    if ($processedRows % 10 === 0) {
                        $progress = 30 + (($processedRows / $totalRows) * 60);
                        $job->updateProgress($progress, "Processed {$processedRows}/{$totalRows} records");
                    }
                }
                
                $offset += $batchSize;
            }
            
        } catch (\Exception $e) {
            \Log::error('Query-based import failed', [
                'job_id' => $job->id,
                'configuration' => $configuration,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Import a single record from query-based import.
     */
    private function importQueryRecord(object $record, array $configuration, ImportJob $job): void
    {
        $targetType = $configuration['target_type'] ?? 'customer_users';
        
        // Convert record object to associative array for easier field access
        $recordArray = (array) $record;
        
        // Map fields based on configuration
        $mappedData = [];
        foreach ($configuration['fields'] as $field) {
            $sourceField = $field['source'];
            $targetField = $field['target'];
            
            // Handle table.column format by using just the alias/target field name
            $value = $recordArray[$targetField] ?? null;
            
            $mappedData[$targetField] = $value;
        }
        
        // Import based on target type
        switch ($targetType) {
            case 'customer_users':
                $this->importCustomerUser($mappedData, $job);
                break;
            case 'accounts':
                $this->importAccount($mappedData, $job);
                break;
            case 'tickets':
                $this->importTicket($mappedData, $job);
                break;
            case 'time_entries':
                $this->importTimeEntry($mappedData, $job);
                break;
            case 'agents':
                $this->importAgent($mappedData, $job);
                break;
            default:
                throw new \Exception("Unsupported target type: {$targetType}");
        }
    }
    
    /**
     * Generate SQL from query configuration (copied from controller for consistency).
     */
    private function generateSQL(array $config): string
    {
        $sql = 'SELECT ';

        // Fields
        if (empty($config['fields'])) {
            $sql .= '*';
        } else {
            $fieldList = [];
            foreach ($config['fields'] as $field) {
                if (isset($field['source']) && isset($field['target'])) {
                    $fieldList[] = "{$field['source']} AS {$field['target']}";
                }
            }
            $sql .= implode(', ', $fieldList);
        }

        // FROM clause
        $sql .= "\nFROM {$config['base_table']}";

        // JOINs
        if (!empty($config['joins'])) {
            foreach ($config['joins'] as $join) {
                // Handle different join formats from frontend
                if (isset($join['leftColumn']) && isset($join['rightColumn'])) {
                    // Frontend format: leftColumn = rightColumn
                    $joinCondition = "{$join['leftColumn']} = {$join['rightColumn']}";
                } else if (isset($join['on'])) {
                    // Backend format: pre-formatted join condition
                    $joinCondition = $join['on'];
                } else {
                    // Skip invalid joins
                    continue;
                }
                
                $sql .= "\n{$join['type']} JOIN {$join['table']} ON {$joinCondition}";
                if (!empty($join['condition'])) {
                    $sql .= " AND {$join['condition']}";
                }
            }
        }

        // WHERE clause
        if (!empty($config['filters'])) {
            $whereConditions = [];
            foreach ($config['filters'] as $filter) {
                $whereConditions[] = $this->formatFilterCondition($filter);
            }
            if (!empty($whereConditions)) {
                $sql .= "\nWHERE ".implode(' AND ', $whereConditions);
            }
        }

        return $sql;
    }
    
    /**
     * Format a filter condition for SQL (copied from controller).
     */
    private function formatFilterCondition(array $filter): string
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'] ?? '';
        $value2 = $filter['value2'] ?? '';

        switch ($operator) {
            case 'IS NULL':
            case 'IS NOT NULL':
                return "{$field} {$operator}";

            case 'BETWEEN':
                return "{$field} BETWEEN '{$value}' AND '{$value2}'";

            case 'IN':
            case 'NOT IN':
                // Handle comma-separated values
                $values = array_map('trim', explode(',', $value));
                $quotedValues = array_map(function ($v) {
                    return "'".str_replace("'", "''", $v)."'";
                }, $values);

                return "{$field} {$operator} (".implode(', ', $quotedValues).')';

            case 'LIKE':
            case 'ILIKE':
            case 'NOT LIKE':
                // Add wildcards if not present
                if (strpos($value, '%') === false) {
                    $value = "%{$value}%";
                }

                return "{$field} {$operator} '".str_replace("'", "''", $value)."'";

            case 'REGEXP':
                return "{$field} ~ '".str_replace("'", "''", $value)."'";

            case '=':
            case '!=':
            case '>':
            case '>=':
            case '<':
            case '<=':
            default:
                // Escape single quotes and wrap in quotes
                $escapedValue = str_replace("'", "''", $value);

                return "{$field} {$operator} '{$escapedValue}'";
        }
    }

    /**
     * Import a customer user record.
     */
    private function importCustomerUser(array $data, ImportJob $job): void
    {
        // Basic customer user import - extend as needed
        throw new \Exception("Customer user import not yet implemented in query builder mode");
    }
    
    /**
     * Import an account record.
     */
    private function importAccount(array $data, ImportJob $job): void
    {
        // Basic account import - extend as needed
        throw new \Exception("Account import not yet implemented in query builder mode");
    }
    
    /**
     * Import a ticket record.
     */
    private function importTicket(array $data, ImportJob $job): void
    {
        // Basic ticket import - extend as needed
        throw new \Exception("Ticket import not yet implemented in query builder mode");
    }
    
    /**
     * Import a time entry record.
     */
    private function importTimeEntry(array $data, ImportJob $job): void
    {
        // Basic time entry import - extend as needed
        throw new \Exception("Time entry import not yet implemented in query builder mode");
    }
    
    /**
     * Import an agent record.
     */
    private function importAgent(array $data, ImportJob $job): void
    {
        // Basic agent import - extend as needed
        throw new \Exception("Agent import not yet implemented in query builder mode");
    }

    /**
     * Cancel a running import job.
     */
    public function cancelImport(ImportJob $job): void
    {
        if ($job->isRunning()) {
            $job->update([
                'status' => 'cancelled',
                'completed_at' => now(),
                'current_operation' => null,
            ]);

            Log::info('Import job cancelled', ['job_id' => $job->id]);
        }
    }
}
