<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportProfile;
use App\Services\PostgreSQLConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class ImportProfileController extends Controller
{
    protected PostgreSQLConnectionService $connectionService;

    public function __construct(PostgreSQLConnectionService $connectionService)
    {
        $this->connectionService = $connectionService;
    }

    /**
     * Display a listing of import profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImportProfile::class);

        $query = ImportProfile::with(['creator', 'importJobs' => function($q) {
            $q->latest()->take(3); // Get last 3 jobs for each profile
        }]);

        // Filter by database type if specified
        if ($request->filled('database_type')) {
            $query->where('database_type', $request->database_type);
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%' . $request->search . '%');
        }

        $profiles = $query->orderBy('name')->paginate($request->get('per_page', 15));

        return response()->json($profiles);
    }

    /**
     * Store a newly created import profile.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ImportProfile::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:import_profiles,name',
            'database_type' => 'required|string|in:postgresql,mysql,sqlite',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|between:1,65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'ssl_mode' => 'required|string|in:disable,allow,prefer,require,verify-ca,verify-full',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'connection_options' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Test connection before saving
        $connectionConfig = [
            'database_type' => $request->database_type,
            'host' => $request->host,
            'port' => $request->port,
            'database' => $request->database,
            'username' => $request->username,
            'password' => $request->password,
            'sslmode' => $request->ssl_mode,
        ];

        $testResult = $this->connectionService->testConnection($connectionConfig);
        
        if (!$testResult['success']) {
            return response()->json([
                'message' => 'Connection test failed',
                'connection_error' => $testResult,
            ], 400);
        }

        // Create the profile
        $profile = ImportProfile::create([
            'name' => $request->name,
            'database_type' => $request->database_type,
            'host' => $request->host,
            'port' => $request->port,
            'database' => $request->database,
            'username' => $request->username,
            'password' => $request->password, // Will be encrypted by the model
            'ssl_mode' => $request->ssl_mode,
            'description' => $request->description,
            'notes' => $request->notes,
            'connection_options' => $request->connection_options,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'last_tested_at' => now(),
            'last_test_result' => $testResult,
        ]);

        $profile->load('creator');

        return response()->json([
            'message' => 'Import profile created successfully',
            'profile' => $profile,
        ], 201);
    }

    /**
     * Display the specified import profile.
     */
    public function show(ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        $profile->load(['creator', 'importJobs', 'importMappings']);

        return response()->json($profile);
    }

    /**
     * Update the specified import profile.
     */
    public function update(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('import_profiles', 'name')->ignore($profile->id)],
            'database_type' => 'required|string|in:postgresql,mysql,sqlite',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|between:1,65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string', // Optional on update - keep existing if not provided
            'ssl_mode' => 'required|string|in:disable,allow,prefer,require,verify-ca,verify-full',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
            'connection_options' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Prepare update data
        $updateData = [
            'name' => $request->name,
            'database_type' => $request->database_type,
            'host' => $request->host,
            'port' => $request->port,
            'database' => $request->database,
            'username' => $request->username,
            'ssl_mode' => $request->ssl_mode,
            'description' => $request->description,
            'notes' => $request->notes,
            'connection_options' => $request->connection_options,
            'is_active' => $request->boolean('is_active', true),
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = $request->password;
        }

        // Update the profile
        $profile->update($updateData);
        $profile->load('creator');

        return response()->json([
            'message' => 'Import profile updated successfully',
            'profile' => $profile,
        ]);
    }

    /**
     * Remove the specified import profile.
     */
    public function destroy(ImportProfile $profile): JsonResponse
    {
        $this->authorize('delete', $profile);

        // Check if profile has any running jobs
        $runningJobs = $profile->importJobs()->where('status', 'running')->count();
        
        if ($runningJobs > 0) {
            return response()->json([
                'message' => 'Cannot delete profile with running import jobs',
                'running_jobs' => $runningJobs,
            ], 400);
        }

        $profile->delete();

        return response()->json([
            'message' => 'Import profile deleted successfully',
        ]);
    }

    /**
     * Test connection for an import profile.
     */
    public function testConnection(Request $request, ImportProfile $profile = null): JsonResponse
    {
        $this->authorize('testConnection', ImportProfile::class);

        // Use existing profile or test parameters from request
        if ($profile) {
            $connectionConfig = $profile->getConnectionConfig();
        } else {
            $validator = Validator::make($request->all(), [
                'database_type' => 'required|string|in:postgresql,mysql,sqlite',
                'host' => 'required|string|max:255',
                'port' => 'required|integer|between:1,65535',
                'database' => 'required|string|max:255',
                'username' => 'required|string|max:255',
                'password' => 'required|string',
                'ssl_mode' => 'required|string|in:disable,allow,prefer,require,verify-ca,verify-full',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $connectionConfig = [
                'database_type' => $request->database_type,
                'host' => $request->host,
                'port' => $request->port,
                'database' => $request->database,
                'username' => $request->username,
                'password' => $request->password,
                'sslmode' => $request->ssl_mode,
            ];
        }

        $testResult = $this->connectionService->testConnection($connectionConfig);

        // Update profile's test results if testing existing profile
        if ($profile && $testResult['success']) {
            $profile->update([
                'last_tested_at' => now(),
                'last_test_result' => $testResult,
            ]);
        }

        return response()->json([
            'connection_test' => $testResult,
        ], $testResult['success'] ? 200 : 400);
    }

    /**
     * Introspect FreeScout database schema.
     */
    public function introspectSchema($importProfileId): JsonResponse
    {
        // Find the profile explicitly
        $importProfile = ImportProfile::findOrFail($importProfileId);
        
        $this->authorize('view', $importProfile);

        try {
            $connectionName = $this->connectionService->createConnection($importProfile);
            
            // Get all tables in the database
            $allTables = DB::connection($connectionName)
                ->select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE' ORDER BY table_name");
            
            $schema = [];
            
            foreach ($allTables as $table) {
                $tableName = $table->table_name;
                
                // Get columns for each table
                $columns = DB::connection($connectionName)
                    ->select("
                        SELECT 
                            column_name,
                            data_type,
                            is_nullable,
                            column_default,
                            character_maximum_length
                        FROM information_schema.columns 
                        WHERE table_name = ? AND table_schema = 'public'
                        ORDER BY ordinal_position
                    ", [$tableName]);
                
                // Get row count
                try {
                    $rowCount = DB::connection($connectionName)
                        ->selectOne("SELECT COUNT(*) as count FROM {$tableName}")->count;
                } catch (\Exception $e) {
                    $rowCount = 0;
                }
                
                // Get sample data (first 3 rows)
                try {
                    $sampleData = DB::connection($connectionName)
                        ->select("SELECT * FROM {$tableName} LIMIT 3");
                } catch (\Exception $e) {
                    $sampleData = [];
                }
                
                $schema[$tableName] = [
                    'columns' => $columns,
                    'row_count' => $rowCount,
                    'sample_data' => $sampleData,
                ];
            }
            
            $this->connectionService->closeConnection($connectionName);
            
            return response()->json([
                'schema' => $schema,
                'total_tables' => count($schema),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to introspect database schema',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get schema information for an import profile.
     */
    public function getSchema(ImportProfile $profile): JsonResponse
    {
        $this->authorize('getSchema', $profile);

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            $schemaInfo = $this->connectionService->getSchemaInfo($connectionName);
            $serverInfo = $this->connectionService->getServerInfo($connectionName);
            $this->connectionService->closeConnection($connectionName);
            
            // Transform schema info to expected format for frontend
            $tables = [];
            foreach ($schemaInfo as $tableName => $tableData) {
                // Transform columns to have 'name' property for frontend compatibility
                $transformedColumns = array_map(function ($column) {
                    return [
                        'name' => $column->column_name,
                        'column_name' => $column->column_name,
                        'data_type' => $column->data_type,
                        'is_nullable' => $column->is_nullable === 'YES',
                        'column_default' => $column->column_default,
                        'character_maximum_length' => $column->character_maximum_length,
                        'numeric_precision' => $column->numeric_precision,
                        'numeric_scale' => $column->numeric_scale,
                        'column_comment' => $column->column_comment,
                        // Add metadata for join suggestions
                        'is_primary_key' => false, // Will be determined by foreign keys
                        'is_foreign_key' => false, // Will be determined by foreign keys
                    ];
                }, $tableData['columns']);

                // Mark primary and foreign keys
                foreach ($tableData['foreign_keys'] as $fk) {
                    foreach ($transformedColumns as &$column) {
                        if ($column['name'] === $fk->column_name) {
                            $column['is_foreign_key'] = true;
                        }
                        if ($column['name'] === 'id') {
                            $column['is_primary_key'] = true;
                        }
                    }
                }

                // Get row count for this table
                $rowCount = 0;
                try {
                    $rowCountResult = DB::connection($connectionName)
                        ->selectOne("SELECT COUNT(*) as count FROM {$tableName}");
                    $rowCount = $rowCountResult->count ?? 0;
                } catch (\Exception $e) {
                    // Ignore count errors for tables without access
                    $rowCount = 0;
                }

                $tables[] = [
                    'name' => $tableName,
                    'table_name' => $tableName,
                    'table_comment' => $tableData['table_comment'],
                    'columns' => $transformedColumns,
                    'foreign_keys' => $tableData['foreign_keys'],
                    'row_count' => $rowCount,
                ];
            }

            return response()->json([
                'schema' => [
                    'tables' => $tables
                ],
                'server_info' => $serverInfo,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retrieve schema',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview import data for FreeScout profiles.
     */
    public function preview($importProfileId): JsonResponse
    {
        // Find the profile explicitly
        $importProfile = ImportProfile::findOrFail($importProfileId);
        
        $this->authorize('preview', $importProfile);

        // Debug: Log the profile data
        \Log::info('ImportProfile preview debug', [
            'id' => $importProfile->id,
            'name' => $importProfile->name,
            'type' => $importProfile->type,
            'host' => $importProfile->host,
            'port' => $importProfile->port,
            'database' => $importProfile->database,
            'username' => $importProfile->username,
            'ssl_mode' => $importProfile->ssl_mode,
            'loaded_correctly' => !is_null($importProfile->host),
        ]);

        try {
            if ($importProfile->type === 'freescout-postgres') {
                // Use the FreeScout import service for specialized preview
                $freeScoutService = app(\App\Services\FreeScoutImportService::class);
                $preview = $freeScoutService->getImportPreview($importProfile);
            } else {
                // For custom PostgreSQL profiles, provide generic preview
                $preview = $this->getGenericPreview($importProfile);
            }

            return response()->json($preview);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to preview import data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get generic preview for custom PostgreSQL profiles.
     */
    private function getGenericPreview(ImportProfile $profile): array
    {
        $connectionName = $this->connectionService->createConnection($profile);
        
        try {
            // Get list of tables directly
            $tables = $this->connectionService->getTables($connectionName);
            
            $preview = [];
            
            // Get preview data for each table
            foreach ($tables as $table) {
                $tableName = $table->table_name ?? $table['table_name'];
                
                try {
                    // Get row count for this table
                    $rowCount = $this->connectionService->getRowCount($connectionName, $tableName);
                    
                    // Get sample data (first 5 rows)
                    $sampleData = $this->connectionService->previewTableData($connectionName, $tableName, 5);
                    
                    $preview[$tableName] = [
                        'title' => ucfirst(str_replace('_', ' ', $tableName)),
                        'description' => "Table: {$tableName} ({$rowCount} records)",
                        'sample_data' => $sampleData,
                        'total_count' => $rowCount,
                    ];
                } catch (\Exception $e) {
                    // If there's an error with this specific table, include it but note the issue
                    $preview[$tableName] = [
                        'title' => ucfirst(str_replace('_', ' ', $tableName)),
                        'description' => "Table: {$tableName} (Error accessing data)",
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
     * Preview data from a specific table.
     */
    public function previewTable(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('preview', $profile);

        $validator = Validator::make($request->all(), [
            'table_name' => 'required|string|max:255',
            'limit' => 'integer|between:1,100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            $data = $this->connectionService->previewTableData(
                $connectionName,
                $request->table_name,
                $request->get('limit', 10)
            );
            $rowCount = $this->connectionService->getRowCount($connectionName, $request->table_name);
            $this->connectionService->closeConnection($connectionName);

            return response()->json([
                'table_name' => $request->table_name,
                'data' => $data,
                'row_count' => $rowCount,
                'preview_limit' => $request->get('limit', 10),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to preview table data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Preview complex query with joins and field mappings.
     */
    public function previewQuery(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('preview', $profile);

        $validator = Validator::make($request->all(), [
            'base_table' => 'required|string|max:255',
            'joins' => 'sometimes|array',
            'joins.*.table' => 'required_with:joins|string|max:255',
            'joins.*.type' => 'required_with:joins|string|in:INNER,LEFT,RIGHT,FULL',
            'joins.*.leftColumn' => 'required_with:joins|string|max:255',
            'joins.*.rightColumn' => 'required_with:joins|string|max:255',
            'fields' => 'sometimes|array',
            'filters' => 'sometimes|array',
            'limit' => 'integer|between:1,100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            
            // Build the query based on configuration
            $query = $this->buildPreviewQuery($request->all());
            
            // Execute query for sample data
            $sampleData = $this->connectionService->executeQuerySample(
                $connectionName,
                $query,
                [],
                $request->get('limit', 10)
            );
            
            // Get estimated record count
            $estimatedCount = $this->connectionService->getEstimatedRecordCount(
                $connectionName,
                $request->base_table,
                $request->get('joins', []),
                $request->get('filters', [])
            );
            
            $this->connectionService->closeConnection($connectionName);

            return response()->json([
                'sample_data' => $sampleData,
                'estimated_records' => $estimatedCount,
                'generated_query' => $query,
                'columns' => !empty($sampleData) ? array_keys((array) $sampleData[0]) : [],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Failed to preview query',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Build a preview query from configuration.
     */
    private function buildPreviewQuery(array $config): string
    {
        $baseTable = $config['base_table'];
        $joins = $config['joins'] ?? [];
        $fields = $config['fields'] ?? [];
        $filters = $config['filters'] ?? [];

        // Build SELECT clause
        if (!empty($fields)) {
            $selectFields = array_map(function ($field) {
                return $field['source'] ?? '*';
            }, $fields);
            $selectClause = implode(', ', $selectFields);
        } else {
            $selectClause = '*';
        }

        $query = "SELECT {$selectClause} FROM {$baseTable}";

        // Add JOINs
        foreach ($joins as $join) {
            if (!empty($join['table']) && !empty($join['leftColumn']) && !empty($join['rightColumn'])) {
                $joinType = $join['type'] ?? 'LEFT';
                $query .= " {$joinType} JOIN {$join['table']} ON {$join['leftColumn']} = {$join['table']}.{$join['rightColumn']}";
            }
        }

        // Add WHERE clauses
        if (!empty($filters)) {
            $whereClauses = [];
            foreach ($filters as $filter) {
                if (!empty($filter['condition'])) {
                    $whereClauses[] = $filter['condition'];
                }
            }
            if (!empty($whereClauses)) {
                $query .= " WHERE " . implode(' AND ', $whereClauses);
            }
        }

        return $query;
    }

    /**
     * Get field mappings for an import profile.
     */
    public function getMappings(ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        $mappings = $profile->mappings()
            ->where('is_active', true)
            ->orderBy('import_order')
            ->get();

        return response()->json($mappings);
    }

    /**
     * Save field mappings for an import profile.
     */
    public function saveMappings(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        $validator = Validator::make($request->all(), [
            'mappings' => 'required|array',
            'mappings.*.source_table' => 'required|string',
            'mappings.*.destination_table' => 'required|string',
            'mappings.*.field_mappings' => 'required|array',
            'mappings.*.import_order' => 'integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Clear existing mappings for this profile
            $profile->mappings()->delete();

            // Create new mappings
            foreach ($request->mappings as $mappingData) {
                $profile->mappings()->create([
                    'source_table' => $mappingData['source_table'],
                    'destination_table' => $mappingData['destination_table'],
                    'field_mappings' => $mappingData['field_mappings'],
                    'import_order' => $mappingData['import_order'] ?? 0,
                    'is_active' => true,
                ]);
            }

            return response()->json([
                'message' => 'Field mappings saved successfully',
                'mappings' => $profile->mappings()->orderBy('import_order')->get(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to save field mappings',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Introspect FreeScout database for email structure
     */
    public function introspectEmails(ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            $result = $this->connectionService->introspectFreeScoutEmails($connectionName);
            $this->connectionService->closeConnection($connectionName);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to introspect database: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Introspect FreeScout database for time tracking structure
     */
    public function introspectTimeTracking(ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            $result = $this->connectionService->introspectFreeScoutTimeTracking($connectionName);
            $this->connectionService->closeConnection($connectionName);

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to introspect time tracking: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Apply a template to an import profile.
     */
    public function applyTemplate(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        $validator = Validator::make($request->all(), [
            'template_id' => 'required|uuid|exists:import_templates,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = \App\Models\ImportTemplate::findOrFail($request->template_id);
        
        // Update the profile with the template
        $profile->update([
            'template_id' => $template->id,
        ]);

        // Load the profile with template relationship
        $profile->load('template');

        return response()->json([
            'message' => 'Template applied successfully',
            'profile' => $profile,
        ]);
    }

    /**
     * Validate a query configuration for the visual query builder.
     */
    public function validateQuery(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        $validator = Validator::make($request->all(), [
            'base_table' => 'required|string',
            'joins' => 'array',
            'joins.*.type' => 'required|string|in:INNER,LEFT,RIGHT,FULL',
            'joins.*.table' => 'required|string',
            'joins.*.on' => 'required|string',
            'joins.*.condition' => 'nullable|string',
            'fields' => 'array',
            'fields.*.source' => 'required|string',
            'fields.*.target' => 'required|string',
            'filters' => 'array',
            'filters.*.field' => 'required|string',
            'filters.*.operator' => 'required|string',
            'filters.*.value' => 'nullable',
            'filters.*.value2' => 'nullable',
            'target_type' => 'required|string|in:customer_users,tickets,time_entries,agents,accounts',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Query validation failed',
                'errors' => $validator->errors()->all(),
                'valid' => false,
            ], 422);
        }

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            
            // Generate SQL
            $sql = $this->generateSQL($request->all());
            
            // Estimate record count
            $estimatedRecords = $this->connectionService->getEstimatedRecordCount(
                $connectionName, 
                $request->base_table, 
                $request->joins ?? [], 
                $request->filters ?? []
            );

            $this->connectionService->closeConnection($connectionName);

            return response()->json([
                'valid' => true,
                'generated_sql' => $sql,
                'estimated_records' => $estimatedRecords,
                'errors' => [],
                'warnings' => $this->generateWarnings($request->all()),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'errors' => ['Query validation failed: ' . $e->getMessage()],
                'warnings' => [],
            ], 400);
        }
    }

    /**
     * Save a custom query configuration.
     */
    public function saveQuery(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        $validator = Validator::make($request->all(), [
            'base_table' => 'required|string',
            'joins' => 'array',
            'fields' => 'required|array|min:1',
            'filters' => 'array',
            'target_type' => 'required|string|in:customer_users,tickets,time_entries,agents,accounts',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Store the query configuration in the profile
        // For now, we'll store it as JSON in a configuration column
        // In a full implementation, you might want a separate import_queries table
        $profile->update([
            'configuration' => $request->all(),
        ]);

        return response()->json([
            'message' => 'Query saved successfully',
            'profile' => $profile,
        ]);
    }

    /**
     * Get JOIN analysis for query builder.
     */
    public function analyzeJoins(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        try {
            $connectionName = $this->connectionService->createConnection($profile);
            
            $joinAnalysis = [];
            $joins = $request->joins ?? [];
            
            foreach ($joins as $join) {
                $analysis = [
                    'join_table' => $join['table'],
                    'impact' => 'low', // Default impact
                    'matched_records' => 0,
                    'description' => "Joining with {$join['table']} using {$join['type']} JOIN",
                ];

                // Try to get actual join statistics
                try {
                    $baseTable = $request->base_table;
                    $joinCondition = $join['on'];
                    
                    // Simple query to estimate join impact
                    $query = "SELECT COUNT(*) as count FROM {$baseTable} {$join['type']} JOIN {$join['table']} ON {$joinCondition}";
                    if (!empty($join['condition'])) {
                        $query .= " AND {$join['condition']}";
                    }
                    
                    $result = $this->connectionService->executeQuery($connectionName, $query);
                    $analysis['matched_records'] = $result[0]['count'] ?? 0;
                    
                    // Determine impact based on record count
                    if ($analysis['matched_records'] > 10000) {
                        $analysis['impact'] = 'high';
                    } elseif ($analysis['matched_records'] > 1000) {
                        $analysis['impact'] = 'medium';
                    }
                    
                } catch (\Exception $e) {
                    // If we can't analyze, that's ok - we'll use defaults
                    $analysis['description'] .= ' (Unable to analyze impact)';
                }
                
                $joinAnalysis[] = $analysis;
            }

            $this->connectionService->closeConnection($connectionName);

            return response()->json([
                'join_analysis' => $joinAnalysis,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'JOIN analysis failed: ' . $e->getMessage(),
                'join_analysis' => [],
            ], 500);
        }
    }

    /**
     * Generate SQL from query configuration.
     */
    private function generateSQL(array $config): string
    {
        $sql = "SELECT ";
        
        // Fields
        if (empty($config['fields'])) {
            $sql .= "*";
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
                $sql .= "\n{$join['type']} JOIN {$join['table']} ON {$join['on']}";
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
                $sql .= "\nWHERE " . implode(' AND ', $whereConditions);
            }
        }
        
        return $sql;
    }

    /**
     * Format a filter condition for SQL.
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
                $quotedValues = array_map(function($v) {
                    return "'" . str_replace("'", "''", $v) . "'";
                }, $values);
                return "{$field} {$operator} (" . implode(', ', $quotedValues) . ")";

            case 'LIKE':
            case 'ILIKE':
            case 'NOT LIKE':
                // Add wildcards if not present
                if (strpos($value, '%') === false) {
                    $value = "%{$value}%";
                }
                return "{$field} {$operator} '" . str_replace("'", "''", $value) . "'";

            case 'REGEXP':
                return "{$field} ~ '" . str_replace("'", "''", $value) . "'";

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
     * Generate warnings for query configuration.
     */
    private function generateWarnings(array $config): array
    {
        $warnings = [];
        
        // Check for potential performance issues
        if (empty($config['filters'])) {
            $warnings[] = 'No filters specified - this may import a large amount of data';
        }
        
        if (!empty($config['joins']) && count($config['joins']) > 3) {
            $warnings[] = 'Complex query with multiple JOINs may be slow on large datasets';
        }
        
        return $warnings;
    }
}
