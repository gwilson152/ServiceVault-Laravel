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
            'config.limits' => 'required|array',
            'config.limits.conversations' => 'nullable|integer|min:1',
            'config.limits.time_entries' => 'nullable|integer|min:1',
            'config.limits.customers' => 'nullable|integer|min:1',
            'config.limits.mailboxes' => 'nullable|integer|min:1',
            'config.account_strategy' => ['required', Rule::in(['map_mailboxes', 'domain_mapping'])],
            'config.agent_access' => ['required', Rule::in(['all_accounts', 'primary_account'])],
            'config.unmapped_users' => [Rule::in(['auto_create', 'skip', 'default_account'])],
            'config.time_entry_defaults' => 'required|array',
            'config.time_entry_defaults.billable' => 'required|boolean',
            'config.time_entry_defaults.approved' => 'required|boolean',
            'config.billing_rate_strategy' => ['required', Rule::in(['auto_select', 'no_rate', 'fixed_rate'])],
            'config.fixed_billing_rate_id' => 'nullable|exists:billing_rates,id',
            'config.comment_processing' => 'required|array',
            'config.comment_processing.preserve_html' => 'required|boolean',
            'config.comment_processing.extract_attachments' => 'required|boolean',
            'config.comment_processing.add_context_prefix' => 'required|boolean',
            'config.sync_strategy' => ['required', Rule::in(['create_only', 'update_only', 'upsert'])],
            'config.sync_mode' => ['required', Rule::in(['incremental', 'full_scan', 'hybrid'])],
            'config.duplicate_detection' => ['required', Rule::in(['external_id', 'content_match'])],
            'config.excluded_mailboxes' => 'array',
            'config.excluded_mailboxes.*' => 'integer',
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

            // Validate role templates exist
            $roleValidation = $this->freescoutImportService->validateRoleTemplates();
            if (!$roleValidation['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Required role templates not found',
                    'missing_roles' => $roleValidation['missing_roles']
                ], 400);
            }

            // Validate domain mappings if using domain mapping strategy
            if ($config['account_strategy'] === 'domain_mapping') {
                $domainValidation = $this->freescoutImportService->validateDomainMappings();
                if (!$domainValidation['success']) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Domain mapping validation failed',
                        'error' => $domainValidation['error']
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
                    'role_templates' => $roleValidation,
                    'domain_mappings' => $domainValidation ?? null,
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