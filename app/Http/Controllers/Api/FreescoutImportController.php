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
            // Start the import job
            $job = $this->freescoutImportService->executeImport($profile, $config);

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
}