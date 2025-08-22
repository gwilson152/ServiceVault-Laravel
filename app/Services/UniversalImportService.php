<?php

namespace App\Services;

use App\Models\ImportJob;
use App\Models\ImportProfile;
use App\Models\ImportQuery;
use Exception;
use Illuminate\Support\Facades\DB;

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
                \Log::error('Failed to import record', [
                    'query' => $query->name,
                    'record' => $record,
                    'error' => $e->getMessage(),
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
                        'description' => 'Error: '.$e->getMessage(),
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
                DB::connection($connectionName)->select('EXPLAIN '.$sql);
            } catch (Exception $e) {
                $validation['is_valid'] = false;
                $validation['errors'][] = 'Invalid SQL query: '.$e->getMessage();
            }

            // Validate field mappings
            $sourceFields = $this->getAvailableFields($connectionName, $query);
            $mappedFields = array_keys($query->field_mappings ?? []);

            foreach ($mappedFields as $field) {
                if (! in_array($field, $sourceFields)) {
                    $validation['warnings'][] = "Mapped field '{$field}' not found in query results";
                }
            }

            // Validate destination table exists
            $destinationTables = $this->getDestinationTables();
            if (! in_array($query->destination_table, $destinationTables)) {
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
                $values = array_filter(array_map(fn ($f) => $data[$f] ?? null, $fields));

                return implode($separator, $values);

            case 'uuid_convert':
                $prefix = $rule['prefix'] ?? '';
                $value = $data[$field] ?? null;

                return $value ? $prefix.$value : null;

            case 'static':
                return $rule['value'] ?? null;

            case 'format':
                $format = $rule['format'] ?? '%s';
                $value = $data[$field] ?? null;

                return $value ? sprintf($format, $value) : null;

                // Time-specific transformations
            case 'time_to_minutes':
                $value = $data[$field] ?? null;

                return $this->convertTimeToMinutes($value);

            case 'duration_format':
                $value = $data[$field] ?? null;

                return $this->standardizeDuration($value);

            case 'calculate_duration':
                $startField = $rule['start_field'] ?? 'started_at';
                $endField = $rule['end_field'] ?? 'ended_at';
                $startTime = $data[$startField] ?? null;
                $endTime = $data[$endField] ?? null;

                return $this->calculateDuration($startTime, $endTime);

            case 'billing_rate_lookup':
                $userId = $data['user_id'] ?? null;
                $accountId = $data['account_id'] ?? null;

                return $this->resolveBillingRate($userId, $accountId, $rule);

            case 'account_from_ticket':
                $ticketId = $data['ticket_id'] ?? null;

                return $this->resolveAccountFromTicket($ticketId);

            default:
                return $data[$field] ?? null;
        }
    }

    /**
     * Convert various time formats to minutes.
     */
    protected function convertTimeToMinutes($value): ?int
    {
        if (! $value) {
            return null;
        }

        // If already numeric (seconds or minutes), assume seconds and convert
        if (is_numeric($value)) {
            return round($value / 60); // Convert seconds to minutes
        }

        // Handle string formats
        if (is_string($value)) {
            // Format: "1h 30m" or "1:30" or "90m"
            if (preg_match('/(\d+)h\s*(\d+)?m?/i', $value, $matches)) {
                $hours = intval($matches[1]);
                $minutes = isset($matches[2]) ? intval($matches[2]) : 0;

                return ($hours * 60) + $minutes;
            }

            // Format: "1:30" (hours:minutes)
            if (preg_match('/(\d+):(\d+)/', $value, $matches)) {
                $hours = intval($matches[1]);
                $minutes = intval($matches[2]);

                return ($hours * 60) + $minutes;
            }

            // Format: "90m" or "90 minutes"
            if (preg_match('/(\d+)\s*m(in|inutes)?/i', $value, $matches)) {
                return intval($matches[1]);
            }

            // Format: "1.5h" (decimal hours)
            if (preg_match('/(\d*\.?\d+)h/i', $value, $matches)) {
                return round(floatval($matches[1]) * 60);
            }
        }

        return null;
    }

    /**
     * Standardize duration input formats.
     */
    protected function standardizeDuration($value): ?int
    {
        return $this->convertTimeToMinutes($value);
    }

    /**
     * Calculate duration between start and end times.
     */
    protected function calculateDuration($startTime, $endTime): ?int
    {
        if (! $startTime || ! $endTime) {
            return null;
        }

        try {
            $start = \Carbon\Carbon::parse($startTime);
            $end = \Carbon\Carbon::parse($endTime);

            if ($end <= $start) {
                return null;
            }

            return $end->diffInMinutes($start);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Resolve billing rate for a user/account combination.
     */
    protected function resolveBillingRate($userId, $accountId, array $rule): ?string
    {
        // This would integrate with Service Vault's billing rate system
        // For now, return null to use system defaults

        // Priority: Account-specific rate → Global default rate → null
        if ($accountId) {
            // Look for account-specific rates for this user type
            $accountRate = DB::table('billing_rates')
                ->where('account_id', $accountId)
                ->where('is_default', true)
                ->first();

            if ($accountRate) {
                return $accountRate->id;
            }
        }

        // Fallback to global default rate
        $globalRate = DB::table('billing_rates')
            ->whereNull('account_id')
            ->where('is_default', true)
            ->first();

        return $globalRate?->id;
    }

    /**
     * Resolve account ID from ticket ID.
     */
    protected function resolveAccountFromTicket($ticketId): ?string
    {
        if (! $ticketId) {
            return null;
        }

        // Look up the account from the ticket
        $ticket = DB::table('tickets')
            ->where('id', $ticketId)
            ->first();

        return $ticket?->account_id;
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
                        if ($value && ! filter_var($value, FILTER_VALIDATE_EMAIL)) {
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

                        // Time entry specific validations
                    case 'min_value':
                        $minValue = $rule['value'] ?? 0;
                        if ($value !== null && $value < $minValue) {
                            throw new Exception("Field '{$field}' must be at least {$minValue}");
                        }
                        break;

                    case 'max_value':
                        $maxValue = $rule['value'] ?? PHP_INT_MAX;
                        if ($value !== null && $value > $maxValue) {
                            throw new Exception("Field '{$field}' cannot exceed {$maxValue}");
                        }
                        break;

                    case 'duration_range':
                        $minMinutes = $rule['min_minutes'] ?? 1;
                        $maxMinutes = $rule['max_minutes'] ?? 1440; // 24 hours
                        if ($value !== null && ($value < $minMinutes || $value > $maxMinutes)) {
                            throw new Exception("Duration must be between {$minMinutes} and {$maxMinutes} minutes");
                        }
                        break;

                    case 'time_range_valid':
                        $this->validateTimeRange($data, $rule);
                        break;

                    case 'user_exists':
                        if ($value && ! DB::table('users')->where('id', $value)->exists()) {
                            throw new Exception("User with ID '{$value}' does not exist");
                        }
                        break;

                    case 'account_exists':
                        if ($value && ! DB::table('accounts')->where('id', $value)->exists()) {
                            throw new Exception("Account with ID '{$value}' does not exist");
                        }
                        break;

                    case 'ticket_exists':
                        if ($value && ! DB::table('tickets')->where('id', $value)->exists()) {
                            throw new Exception("Ticket with ID '{$value}' does not exist");
                        }
                        break;

                    case 'account_ticket_match':
                        $this->validateAccountTicketMatch($data);
                        break;

                    case 'no_duplicate_time':
                        $this->validateNoDuplicateTimeEntry($data, $query);
                        break;
                }
            }
        }
    }

    /**
     * Validate that start time is before end time.
     */
    protected function validateTimeRange(array $data, array $rule): void
    {
        $startField = $rule['start_field'] ?? 'started_at';
        $endField = $rule['end_field'] ?? 'ended_at';

        $startTime = $data[$startField] ?? null;
        $endTime = $data[$endField] ?? null;

        if ($startTime && $endTime) {
            try {
                $start = \Carbon\Carbon::parse($startTime);
                $end = \Carbon\Carbon::parse($endTime);

                if ($end <= $start) {
                    throw new Exception('End time must be after start time');
                }

                // Check if time range is reasonable (not more than 24 hours)
                $maxHours = $rule['max_hours'] ?? 24;
                if ($end->diffInHours($start) > $maxHours) {
                    throw new Exception("Time range cannot exceed {$maxHours} hours");
                }

            } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                throw new Exception('Invalid date/time format');
            }
        }
    }

    /**
     * Validate that account and ticket match.
     */
    protected function validateAccountTicketMatch(array $data): void
    {
        $accountId = $data['account_id'] ?? null;
        $ticketId = $data['ticket_id'] ?? null;

        if ($accountId && $ticketId) {
            $ticket = DB::table('tickets')->where('id', $ticketId)->first();

            if ($ticket && $ticket->account_id !== $accountId) {
                throw new Exception('Ticket does not belong to the specified account');
            }
        }
    }

    /**
     * Check for duplicate time entries.
     */
    protected function validateNoDuplicateTimeEntry(array $data, ImportQuery $query): void
    {
        if ($query->destination_table !== 'time_entries') {
            return;
        }

        $userId = $data['user_id'] ?? null;
        $startTime = $data['started_at'] ?? null;
        $description = $data['description'] ?? null;

        if ($userId && $startTime) {
            $existing = DB::table('time_entries')
                ->where('user_id', $userId)
                ->where('started_at', $startTime)
                ->when($description, function ($query, $description) {
                    return $query->where('description', $description);
                })
                ->exists();

            if ($existing) {
                throw new Exception('Duplicate time entry detected for user at this time');
            }
        }
    }

    /**
     * Insert a record into the destination table.
     */
    protected function insertRecord(string $table, array $data): void
    {
        // Add timestamps if not present
        if (! isset($data['created_at'])) {
            $data['created_at'] = now();
        }
        if (! isset($data['updated_at'])) {
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
