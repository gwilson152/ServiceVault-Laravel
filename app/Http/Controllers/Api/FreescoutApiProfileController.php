<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportProfile;
use App\Services\FreescoutApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FreescoutApiProfileController extends Controller
{
    protected FreescoutApiService $freescoutApiService;

    public function __construct(FreescoutApiService $freescoutApiService)
    {
        $this->freescoutApiService = $freescoutApiService;
    }

    /**
     * Display a listing of FreeScout API profiles.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImportProfile::class);

        $query = ImportProfile::with(['creator', 'importJobs' => function ($q) {
            $q->latest()->take(3);
        }])
        ->where('database_type', 'freescout_api')
        ->orderBy('name');

        // Search by name
        if ($request->filled('search')) {
            $query->where('name', 'ILIKE', '%'.$request->search.'%');
        }

        // Filter by active status
        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $profiles = $query->paginate($request->get('per_page', 15));

        // Transform profiles for frontend
        $profiles->getCollection()->transform(function ($profile) {
            return [
                'id' => $profile->id,
                'name' => $profile->name,
                'instance_url' => $profile->host,
                'api_key_masked' => $this->maskApiKey($profile->getConnectionConfig()['password'] ?? ''),
                'status' => $this->getConnectionStatus($profile),
                'last_tested' => $this->formatLastTested($profile->last_tested_at),
                'stats' => $this->getProfileStats($profile),
                'created_at' => $profile->created_at->toISOString(),
                'updated_at' => $profile->updated_at->toISOString(),
                'creator' => $profile->creator ? [
                    'id' => $profile->creator->id,
                    'name' => $profile->creator->name,
                ] : null,
            ];
        });

        return response()->json($profiles);
    }

    /**
     * Store a newly created FreeScout API profile.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ImportProfile::class);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:import_profiles,name',
            'instance_url' => 'required|url|max:255',
            'api_key' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'account_strategy' => ['nullable', Rule::in(['map_mailboxes', 'domain_mapping'])],
            'agent_access' => ['nullable', Rule::in(['all_accounts', 'primary_account'])],
            'configuration' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Test FreeScout API connection before saving
        $testResult = $this->freescoutApiService->testConnection([
            'instance_url' => $request->instance_url,
            'api_key' => $request->api_key,
        ]);

        if (!$testResult['success']) {
            return response()->json([
                'success' => false,
                'message' => 'FreeScout API connection test failed',
                'connection_error' => $testResult,
            ], 400);
        }

        // Create the profile with FreeScout API specific settings
        $profile = ImportProfile::create([
            'name' => $request->name,
            'database_type' => 'freescout_api', // Special type for API profiles
            'host' => $request->instance_url,
            'port' => 443, // HTTPS default
            'database' => 'freescout_api', // Not used but required by model
            'username' => 'api_key', // Not used but required by model
            'password' => $request->api_key, // Will be encrypted by the model
            'ssl_mode' => 'require', // Always use SSL for API
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => Auth::id(),
            'last_tested_at' => now(),
            'last_test_result' => $testResult,
            'configuration' => array_merge([
                'account_strategy' => $request->account_strategy ?? 'map_mailboxes',
                'agent_access' => $request->agent_access ?? 'all_accounts',
            ], $request->configuration ?? []),
        ]);

        // Get fresh stats after creation
        $profile->load('creator');
        $transformedProfile = $this->transformProfileForResponse($profile);

        return response()->json([
            'success' => true,
            'message' => 'FreeScout API profile created successfully',
            'profile' => $transformedProfile,
        ], 201);
    }

    /**
     * Display the specified FreeScout API profile.
     */
    public function show(ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        if ($profile->database_type !== 'freescout_api') {
            return response()->json([
                'success' => false,
                'message' => 'Profile is not a FreeScout API profile',
            ], 400);
        }

        $profile->load(['creator', 'importJobs']);
        $transformedProfile = $this->transformProfileForResponse($profile);

        return response()->json([
            'success' => true,
            'profile' => $transformedProfile,
        ]);
    }

    /**
     * Update the specified FreeScout API profile.
     */
    public function update(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('update', $profile);

        if ($profile->database_type !== 'freescout_api') {
            return response()->json([
                'success' => false,
                'message' => 'Profile is not a FreeScout API profile',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255', Rule::unique('import_profiles', 'name')->ignore($profile->id)],
            'instance_url' => 'required|url|max:255',
            'api_key' => 'nullable|string|max:255', // Optional on update
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
            'account_strategy' => ['nullable', Rule::in(['map_mailboxes', 'domain_mapping'])],
            'agent_access' => ['nullable', Rule::in(['all_accounts', 'primary_account'])],
            'configuration' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $updateData = [
            'name' => $request->name,
            'host' => $request->instance_url,
            'description' => $request->description,
            'is_active' => $request->boolean('is_active', true),
            'configuration' => array_merge($profile->configuration ?? [], [
                'account_strategy' => $request->account_strategy ?? 'map_mailboxes',
                'agent_access' => $request->agent_access ?? 'all_accounts',
            ], $request->configuration ?? []),
        ];

        // Only update API key if provided
        if ($request->filled('api_key')) {
            $updateData['password'] = $request->api_key;

            // Test new API key
            $testResult = $this->freescoutApiService->testConnection([
                'instance_url' => $request->instance_url,
                'api_key' => $request->api_key,
            ]);

            if (!$testResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'FreeScout API connection test failed',
                    'connection_error' => $testResult,
                ], 400);
            }

            $updateData['last_tested_at'] = now();
            $updateData['last_test_result'] = $testResult;
        }

        $profile->update($updateData);
        $profile->load('creator');
        $transformedProfile = $this->transformProfileForResponse($profile);

        return response()->json([
            'success' => true,
            'message' => 'FreeScout API profile updated successfully',
            'profile' => $transformedProfile,
        ]);
    }

    /**
     * Remove the specified FreeScout API profile.
     */
    public function destroy(ImportProfile $profile): JsonResponse
    {
        $this->authorize('delete', $profile);

        if ($profile->database_type !== 'freescout_api') {
            return response()->json([
                'success' => false,
                'message' => 'Profile is not a FreeScout API profile',
            ], 400);
        }

        // Check if profile has any running jobs
        $runningJobs = $profile->importJobs()->whereIn('status', ['running', 'pending'])->count();

        if ($runningJobs > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete profile with running import jobs',
                'running_jobs' => $runningJobs,
            ], 400);
        }

        $profile->delete();

        return response()->json([
            'success' => true,
            'message' => 'FreeScout API profile deleted successfully',
        ]);
    }

    /**
     * Test connection for a FreeScout API profile.
     */
    public function testConnection(Request $request, ?ImportProfile $profile = null): JsonResponse
    {
        $this->authorize('testConnection', ImportProfile::class);

        if ($profile) {
            // Test existing profile
            if ($profile->database_type !== 'freescout_api') {
                return response()->json([
                    'success' => false,
                    'message' => 'Profile is not a FreeScout API profile',
                ], 400);
            }

            $connectionConfig = [
                'instance_url' => $profile->host,
                'api_key' => $profile->getConnectionConfig()['password'] ?? '',
            ];
        } else {
            // Test new connection parameters
            $validator = Validator::make($request->all(), [
                'instance_url' => 'required|url|max:255',
                'api_key' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $connectionConfig = [
                'instance_url' => $request->instance_url,
                'api_key' => $request->api_key,
            ];
        }

        $testResult = $this->freescoutApiService->testConnection($connectionConfig);

        // Update profile's test results if testing existing profile
        if ($profile && $testResult['success']) {
            $profile->update([
                'last_tested_at' => now(),
                'last_test_result' => $testResult,
            ]);
        }

        return response()->json([
            'success' => $testResult['success'],
            'connection_test' => $testResult,
        ], $testResult['success'] ? 200 : 400);
    }

    /**
     * Get FreeScout data statistics for a profile.
     */
    public function getStats(ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        if ($profile->database_type !== 'freescout_api') {
            return response()->json([
                'success' => false,
                'message' => 'Profile is not a FreeScout API profile',
            ], 400);
        }

        try {
            $stats = $this->freescoutApiService->getDataStatistics([
                'instance_url' => $profile->host,
                'api_key' => $profile->getConnectionConfig()['password'] ?? '',
            ]);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve FreeScout statistics',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get preview data for import configuration.
     */
    public function getPreviewData(Request $request, ImportProfile $profile): JsonResponse
    {
        $this->authorize('view', $profile);

        if ($profile->database_type !== 'freescout_api') {
            return response()->json([
                'success' => false,
                'message' => 'Profile is not a FreeScout API profile',
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'sample_size' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $previewData = $this->freescoutApiService->getPreviewData([
                'instance_url' => $profile->host,
                'api_key' => $profile->getConnectionConfig()['password'] ?? '',
            ], [
                'sample_size' => $request->get('sample_size', 10),
            ]);

            if (!$previewData['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve preview data',
                    'error' => $previewData['error'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'preview_data' => $previewData['data'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve preview data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Transform profile for frontend response.
     */
    private function transformProfileForResponse(ImportProfile $profile): array
    {
        return [
            'id' => $profile->id,
            'name' => $profile->name,
            'instance_url' => $profile->host,
            'api_key_masked' => $this->maskApiKey($profile->getConnectionConfig()['password'] ?? ''),
            'status' => $this->getConnectionStatus($profile),
            'last_tested' => $this->formatLastTested($profile->last_tested_at),
            'stats' => $this->getProfileStats($profile),
            'description' => $profile->description,
            'is_active' => $profile->is_active,
            'configuration' => $profile->configuration,
            'created_at' => $profile->created_at->toISOString(),
            'updated_at' => $profile->updated_at->toISOString(),
            'creator' => $profile->creator ? [
                'id' => $profile->creator->id,
                'name' => $profile->creator->name,
            ] : null,
            'import_jobs' => $profile->importJobs->map(function ($job) {
                return [
                    'id' => $job->id,
                    'status' => $job->status,
                    'progress' => $job->progress,
                    'created_at' => $job->created_at->toISOString(),
                ];
            }),
        ];
    }

    /**
     * Mask API key for display.
     */
    private function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) <= 8) {
            return '****-****';
        }

        return substr($apiKey, 0, 4) . '-****-****-' . substr($apiKey, -4);
    }

    /**
     * Get connection status based on last test result.
     */
    private function getConnectionStatus(ImportProfile $profile): string
    {
        if (!$profile->last_test_result) {
            return 'pending';
        }

        if ($profile->last_test_result['success'] ?? false) {
            // Check if test is recent (within 24 hours)
            if ($profile->last_tested_at && $profile->last_tested_at->gt(now()->subHours(24))) {
                return 'connected';
            } else {
                return 'pending'; // Needs retest
            }
        }

        return 'error';
    }

    /**
     * Format last tested timestamp.
     */
    private function formatLastTested($lastTested): string
    {
        if (!$lastTested) {
            return 'never';
        }

        return $lastTested->diffForHumans();
    }

    /**
     * Get profile statistics.
     */
    private function getProfileStats(ImportProfile $profile): array
    {
        $defaultStats = [
            'conversations' => 'N/A',
            'customers' => 'N/A',
            'mailboxes' => 'N/A',
        ];

        // If we have a successful test result, try to get stats from it
        if ($profile->last_test_result && ($profile->last_test_result['success'] ?? false)) {
            $testResult = $profile->last_test_result;
            
            // Check if we have stats in the test result
            if (isset($testResult['stats'])) {
                return [
                    'conversations' => number_format($testResult['stats']['conversations'] ?? 0),
                    'customers' => number_format($testResult['stats']['customers'] ?? 0),
                    'mailboxes' => number_format($testResult['stats']['mailboxes'] ?? 0),
                ];
            }
        }

        // If no cached stats and profile is active, try to fetch fresh stats
        if ($profile->is_active) {
            try {
                $stats = $this->freescoutApiService->getDataStatistics([
                    'instance_url' => $profile->host,
                    'api_key' => $profile->getConnectionConfig()['password'] ?? '',
                ]);

                if (!isset($stats['error'])) {
                    // Cache the stats in the test result for next time
                    $updatedTestResult = $profile->last_test_result ?: ['success' => true];
                    $updatedTestResult['stats'] = $stats;
                    $profile->update(['last_test_result' => $updatedTestResult]);

                    return [
                        'conversations' => number_format($stats['conversations'] ?? 0),
                        'customers' => number_format($stats['customers'] ?? 0),
                        'mailboxes' => number_format($stats['mailboxes'] ?? 0),
                    ];
                }
            } catch (\Exception $e) {
                // If fresh fetch fails, fall back to defaults
                Log::warning('Failed to fetch fresh stats for profile', [
                    'profile_id' => $profile->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $defaultStats;
    }
}