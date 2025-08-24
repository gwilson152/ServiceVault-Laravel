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

            // Get import mappings for this profile (mapping-based import)
            $mappings = $profile->importMappings()->active()->get();

            if ($mappings->isEmpty()) {
                throw new Exception('No active import mappings found for this profile');
            }

            $this->processMappingBasedImport($connectionName, $mappings, $job);

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
