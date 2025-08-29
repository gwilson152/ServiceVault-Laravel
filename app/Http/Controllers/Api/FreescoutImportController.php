<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportProfile;
use App\Services\FreescoutImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class FreescoutImportController extends Controller
{
    protected FreescoutImportService $freescoutImportService;

    public function __construct(FreescoutImportService $freescoutImportService)
    {
        $this->freescoutImportService = $freescoutImportService;
    }

    /**
     * Validate import configuration before execution.
     */
    public function validateConfig(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:import_profiles,id',
            'config' => 'required|array',
            
            // Date range filtering for conversations
            'config.date_range' => 'required|array',
            'config.date_range.start_date' => 'nullable|date',
            'config.date_range.end_date' => 'nullable|date|after_or_equal:config.date_range.start_date',
            
            // Simplified configuration options  
            'config.account_strategy' => ['required', Rule::in(['mailbox_per_account', 'single_account', 'domain_mapping', 'domain_mapping_strict'])],
            'config.agent_import_strategy' => ['required', Rule::in(['create_new', 'match_existing', 'skip'])],
            'config.continue_on_error' => 'required|boolean'
            // Note: duplicate detection is always by external_id - no configuration needed
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = ImportProfile::find($request->profile_id);
        $config = $request->config;

        try {
            // Validate FreeScout API connection
            $connectionTest = $this->freescoutImportService->testConnection($profile);
            if (!$connectionTest['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'FreeScout API connection failed',
                    'error' => $connectionTest['error']
                ], 400);
            }

            // Validate role templates exist (if creating new agents)
            if ($config['agent_import_strategy'] === 'create_new') {
                $roleValidation = $this->freescoutImportService->validateRoleTemplates();
                if (!$roleValidation['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Required role templates not found for agent creation',
                        'missing_roles' => $roleValidation['missing_roles']
                    ], 400);
                }
            }

            // Validate domain mappings exist (if using domain mapping strategies)
            if (in_array($config['account_strategy'], ['domain_mapping', 'domain_mapping_strict'])) {
                $domainValidation = $this->freescoutImportService->validateDomainMappings();
                if (!$domainValidation['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Domain mapping strategy requires existing domain mappings',
                        'error' => $domainValidation['error'],
                        'mappings_count' => $domainValidation['mappings_count'] ?? 0
                    ], 400);
                }
            }

            // Estimate import counts
            $estimates = $this->freescoutImportService->estimateImportCounts($profile, $config);

            return response()->json([
                'success' => true,
                'message' => 'Configuration validated successfully',
                'validation' => [
                    'api_connection' => $connectionTest,
                    'role_templates' => $config['import_agents'] ? ($roleValidation ?? ['success' => true]) : ['success' => true, 'message' => 'Agent import disabled'],
                    'domain_mappings' => in_array($config['account_strategy'], ['domain_mapping', 'domain_mapping_strict']) 
                        ? ($domainValidation ?? ['success' => true]) 
                        : ['success' => true, 'message' => 'Domain mapping not required for this strategy'],
                ],
                'estimates' => $estimates
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Preview import data based on configuration.
     */
    public function previewImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:import_profiles,id',
            'config' => 'required|array',
            'sample_size' => 'integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = ImportProfile::find($request->profile_id);
        $config = $request->config;
        $sampleSize = $request->input('sample_size', 10);

        try {
            $preview = $this->freescoutImportService->previewImport($profile, $config, $sampleSize);

            return response()->json([
                'success' => true,
                'preview' => $preview
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Execute FreeScout import with given configuration.
     */
    public function executeImport(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:import_profiles,id',
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = ImportProfile::find($request->profile_id);
        $config = $request->config;

        try {
            // Start the import job asynchronously
            $job = $this->freescoutImportService->executeImportAsync($profile, $config);

            return response()->json([
                'success' => true,
                'message' => 'Import job started successfully',
                'job' => [
                    'id' => $job->id,
                    'status' => $job->status,
                    'progress' => $job->progress,
                    'message' => $job->status_message,
                    'created_at' => $job->created_at
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start import',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import job status and progress.
     */
    public function getImportStatus(Request $request, $jobId): JsonResponse
    {
        $job = $this->freescoutImportService->getImportJobStatus($jobId);

        if (!$job) {
            return response()->json([
                'success' => false,
                'message' => 'Import job not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'job' => $job
        ]);
    }

    /**
     * Get import relationship analysis.
     */
    public function analyzeRelationships(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:import_profiles,id',
            'config' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $profile = ImportProfile::find($request->profile_id);
        $config = $request->config;

        try {
            $analysis = $this->freescoutImportService->analyzeRelationships($profile, $config);

            return response()->json([
                'success' => true,
                'analysis' => $analysis
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Relationship analysis failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import statistics for the dashboard.
     */
    public function getImportStats(): JsonResponse
    {
        try {
            $stats = [
                'total_imports' => \App\Models\ImportJob::count(),
                'successful' => \App\Models\ImportJob::where('status', 'completed')->count(),
                'failed' => \App\Models\ImportJob::where('status', 'failed')->count(),
                'in_progress' => \App\Models\ImportJob::where('status', 'running')->count(),
                'pending' => \App\Models\ImportJob::where('status', 'pending')->count(),
            ];

            return response()->json([
                'success' => true,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load import statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent import activity.
     */
    public function getRecentActivity(): JsonResponse
    {
        try {
            $recentJobs = \App\Models\ImportJob::with(['profile', 'startedByUser'])
                ->latest()
                ->limit(10)
                ->get()
                ->map(function ($job) {
                    return [
                        'id' => $job->id,
                        'action' => $this->getActionDescription($job),
                        'profile' => $job->profile->name ?? 'Unknown Profile',
                        'status' => $job->status,
                        'timestamp' => $job->created_at->diffForHumans(),
                        'records_processed' => $job->records_processed ?? 0,
                        'records_imported' => $job->records_imported ?? 0,
                        'duration' => $job->completed_at && $job->started_at 
                            ? $job->started_at->diffInSeconds($job->completed_at) 
                            : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'activity' => $recentJobs
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load recent activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get import job logs with details.
     */
    public function getImportLogs(Request $request): JsonResponse
    {
        try {
            $query = \App\Models\ImportJob::with(['profile', 'startedByUser'])
                ->latest();

            // Filter by status if provided
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by profile if provided
            if ($request->has('profile_id')) {
                $query->where('profile_id', $request->profile_id);
            }

            $perPage = $request->input('per_page', 15);
            $jobs = $query->paginate($perPage);

            $formattedJobs = $jobs->getCollection()->map(function ($job) {
                return [
                    'id' => $job->id,
                    'profile_name' => $job->profile->name ?? 'Unknown',
                    'status' => $job->status,
                    'mode' => $job->mode,
                    'progress_percentage' => $job->progress_percentage ?? 0,
                    'current_operation' => $job->current_operation,
                    'records_processed' => $job->records_processed ?? 0,
                    'records_imported' => $job->records_imported ?? 0,
                    'records_updated' => $job->records_updated ?? 0,
                    'records_skipped' => $job->records_skipped ?? 0,
                    'records_failed' => $job->records_failed ?? 0,
                    'started_at' => $job->started_at,
                    'completed_at' => $job->completed_at,
                    'duration' => $job->completed_at && $job->started_at 
                        ? $job->started_at->diffInSeconds($job->completed_at) 
                        : null,
                    'started_by' => $job->startedByUser->name ?? 'System',
                    'errors' => $job->errors,
                    'summary' => $job->summary,
                ];
            });

            return response()->json([
                'success' => true,
                'jobs' => [
                    'data' => $formattedJobs,
                    'current_page' => $jobs->currentPage(),
                    'last_page' => $jobs->lastPage(),
                    'per_page' => $jobs->perPage(),
                    'total' => $jobs->total(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load import logs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get action description for job status.
     */
    private function getActionDescription(\App\Models\ImportJob $job): string
    {
        switch ($job->status) {
            case 'pending':
                return 'Import queued';
            case 'running':
                return 'Import in progress';
            case 'completed':
                $imported = $job->records_imported ?? 0;
                return "Import completed ($imported records)";
            case 'failed':
                return 'Import failed';
            default:
                return 'Import status unknown';
        }
    }
}