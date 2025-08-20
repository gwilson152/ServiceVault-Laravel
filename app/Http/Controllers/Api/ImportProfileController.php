<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportProfile;
use App\Services\PostgreSQLConnectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Filter by type if specified
        if ($request->filled('type')) {
            $query->where('type', $request->type);
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
            'type' => 'required|string|in:freescout-postgres,custom-postgres',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|between:1,65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'required|string',
            'ssl_mode' => 'required|string|in:disable,allow,prefer,require,verify-ca,verify-full',
            'description' => 'nullable|string',
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
            'type' => $request->type,
            'host' => $request->host,
            'port' => $request->port,
            'database' => $request->database,
            'username' => $request->username,
            'password' => $request->password, // Will be encrypted by the model
            'ssl_mode' => $request->ssl_mode,
            'description' => $request->description,
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
            'type' => 'required|string|in:freescout-postgres,custom-postgres',
            'host' => 'required|string|max:255',
            'port' => 'required|integer|between:1,65535',
            'database' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'password' => 'nullable|string', // Optional on update - keep existing if not provided
            'ssl_mode' => 'required|string|in:disable,allow,prefer,require,verify-ca,verify-full',
            'description' => 'nullable|string',
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
            'type' => $request->type,
            'host' => $request->host,
            'port' => $request->port,
            'database' => $request->database,
            'username' => $request->username,
            'ssl_mode' => $request->ssl_mode,
            'description' => $request->description,
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
                $tables[] = [
                    'name' => $tableName,
                    'table_name' => $tableName,
                    'table_comment' => $tableData['table_comment'],
                    'columns' => $tableData['columns'],
                    'foreign_keys' => $tableData['foreign_keys'],
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
}
