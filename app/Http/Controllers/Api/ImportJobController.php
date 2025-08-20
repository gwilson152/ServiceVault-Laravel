<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Models\ImportProfile;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ImportJobController extends Controller
{
    protected ImportService $importService;

    public function __construct(ImportService $importService)
    {
        $this->importService = $importService;
    }

    /**
     * Display a listing of import jobs.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ImportJob::class);

        $query = ImportJob::with(['profile', 'creator']);

        // Filter by profile
        if ($request->filled('profile_id')) {
            $query->where('profile_id', $request->profile_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $statuses = is_array($request->status) ? $request->status : [$request->status];
            $query->whereIn('status', $statuses);
        }

        // Filter by date range
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        // Search by profile name
        if ($request->filled('search')) {
            $query->whereHas('profile', function($q) use ($request) {
                $q->where('name', 'ILIKE', '%' . $request->search . '%');
            });
        }

        $jobs = $query->orderBy('created_at', 'desc')
                     ->paginate($request->get('per_page', 15));

        return response()->json($jobs);
    }

    /**
     * Start a new import job.
     */
    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', ImportJob::class);

        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:import_profiles,id',
            'options' => 'array',
            'options.overwrite_existing' => 'boolean',
            'options.skip_validation' => 'boolean',
            'options.batch_size' => 'integer|between:10,1000',
            'options.selected_tables' => 'array',
            'options.selected_tables.*' => 'string',
            'options.import_filters' => 'array',
            'options.import_filters.date_from' => 'nullable|date',
            'options.import_filters.date_to' => 'nullable|date',
            'options.import_filters.ticket_status' => 'nullable|string|in:1,2,3',
            'options.import_filters.limit' => 'nullable|integer|min:1',
            'options.import_filters.active_users_only' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = ImportProfile::findOrFail($request->profile_id);

        // Check if profile is active
        if (!$profile->is_active) {
            return response()->json([
                'message' => 'Import profile is not active',
            ], 400);
        }

        // Check for existing running jobs on this profile
        $runningJobs = ImportJob::where('profile_id', $profile->id)
                                ->where('status', 'running')
                                ->count();

        if ($runningJobs > 0) {
            return response()->json([
                'message' => 'There is already a running import job for this profile',
                'running_jobs' => $runningJobs,
            ], 400);
        }

        try {
            // Start the import job
            $job = $this->importService->startImport($profile, $request->get('options', []));

            return response()->json([
                'message' => 'Import job started successfully',
                'job' => $job->load(['profile', 'creator']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start import job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified import job.
     */
    public function show(ImportJob $importJob): JsonResponse
    {
        $this->authorize('view', $importJob);

        $importJob->load(['profile', 'creator']);

        return response()->json($importJob);
    }

    /**
     * Cancel a running import job.
     */
    public function destroy(ImportJob $importJob): JsonResponse
    {
        $this->authorize('cancel', $importJob);

        if (!$importJob->isRunning()) {
            return response()->json([
                'message' => 'Only running jobs can be cancelled',
                'current_status' => $importJob->status,
            ], 400);
        }

        try {
            $this->importService->cancelImport($importJob);

            return response()->json([
                'message' => 'Import job cancelled successfully',
                'job' => $importJob->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel import job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get import job statistics and summary.
     */
    public function stats(Request $request): JsonResponse
    {
        $this->authorize('monitor', ImportJob::class);

        $query = ImportJob::query();

        // Filter by date range if provided
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $stats = [
            'total_jobs' => $query->count(),
            'completed_jobs' => (clone $query)->where('status', 'completed')->count(),
            'failed_jobs' => (clone $query)->where('status', 'failed')->count(),
            'running_jobs' => (clone $query)->where('status', 'running')->count(),
            'cancelled_jobs' => (clone $query)->where('status', 'cancelled')->count(),
            'total_records_imported' => (clone $query)->sum('records_imported'),
            'total_records_failed' => (clone $query)->sum('records_failed'),
            'average_duration' => (clone $query)
                ->whereNotNull('completed_at')
                ->selectRaw('AVG(EXTRACT(EPOCH FROM (completed_at - started_at))) as avg_duration')
                ->value('avg_duration'),
        ];

        // Recent jobs summary
        $recentJobs = ImportJob::with(['profile'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function($job) {
                return [
                    'id' => $job->id,
                    'profile_name' => $job->profile->name,
                    'status' => $job->status,
                    'records_imported' => $job->records_imported,
                    'records_failed' => $job->records_failed,
                    'started_at' => $job->started_at,
                    'completed_at' => $job->completed_at,
                    'duration' => $job->duration,
                ];
            });

        return response()->json([
            'stats' => $stats,
            'recent_jobs' => $recentJobs,
        ]);
    }

    /**
     * Preview import data before execution.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('execute', ImportJob::class);

        $validator = Validator::make($request->all(), [
            'profile_id' => 'required|exists:import_profiles,id',
            'limit' => 'integer|between:1,50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $profile = ImportProfile::findOrFail($request->profile_id);

        try {
            $preview = $this->importService->previewImport(
                $profile, 
                $request->get('limit', 10)
            );

            return response()->json([
                'profile' => [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'type' => $profile->type,
                ],
                'preview' => $preview,
                'limit' => $request->get('limit', 10),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to preview import data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time progress for a running job.
     */
    public function progress(ImportJob $importJob): JsonResponse
    {
        $this->authorize('getStatus', $importJob);

        return response()->json([
            'job_id' => $importJob->id,
            'status' => $importJob->status,
            'progress_percentage' => $importJob->progress_percentage,
            'current_operation' => $importJob->current_operation,
            'records_processed' => $importJob->records_processed,
            'records_imported' => $importJob->records_imported,
            'records_skipped' => $importJob->records_skipped,
            'records_failed' => $importJob->records_failed,
            'started_at' => $importJob->started_at,
            'duration' => $importJob->duration,
            'estimated_completion' => $this->estimateCompletion($importJob),
        ]);
    }

    /**
     * Retry a failed import job.
     */
    public function retry(ImportJob $importJob): JsonResponse
    {
        $this->authorize('execute', ImportJob::class);

        if (!in_array($importJob->status, ['failed', 'cancelled'])) {
            return response()->json([
                'message' => 'Only failed or cancelled jobs can be retried',
                'current_status' => $importJob->status,
            ], 400);
        }

        try {
            // Create a new job with the same configuration
            $newJob = $this->importService->startImport(
                $importJob->profile, 
                $importJob->import_options ?? []
            );

            return response()->json([
                'message' => 'Import job retry started successfully',
                'original_job_id' => $importJob->id,
                'new_job' => $newJob->load(['profile', 'creator']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to retry import job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed error log for a failed job.
     */
    public function errors(ImportJob $importJob): JsonResponse
    {
        $this->authorize('view', $importJob);

        if (empty($importJob->errors)) {
            return response()->json([
                'message' => 'No errors found for this job',
                'errors' => [],
            ]);
        }

        // Parse errors (assuming they're stored as line-separated text)
        $errors = array_filter(explode("\n", $importJob->errors));

        return response()->json([
            'job_id' => $importJob->id,
            'total_errors' => count($errors),
            'errors' => array_map(function($error, $index) {
                return [
                    'line' => $index + 1,
                    'error' => $error,
                    'timestamp' => null, // Could be enhanced to include timestamps
                ];
            }, $errors, array_keys($errors)),
        ]);
    }

    /**
     * Execute import for a profile.
     */
    public function executeImport(ImportProfile $profile): JsonResponse
    {
        $this->authorize('execute', ImportJob::class);

        // Check if profile is active
        if (!$profile->is_active) {
            return response()->json([
                'message' => 'Import profile is not active',
            ], 400);
        }

        // Check for existing running jobs on this profile
        $runningJobs = ImportJob::where('profile_id', $profile->id)
                                ->where('status', 'running')
                                ->count();

        if ($runningJobs > 0) {
            return response()->json([
                'message' => 'There is already a running import job for this profile',
                'running_jobs' => $runningJobs,
            ], 400);
        }

        try {
            // Start the import job
            $job = $this->importService->startImport($profile, []);

            return response()->json([
                'message' => 'Import job started successfully',
                'job' => $job->load(['profile', 'creator']),
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to start import job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel a running import job.
     */
    public function cancel(ImportJob $importJob): JsonResponse
    {
        $this->authorize('cancel', $importJob);

        if (!$importJob->isRunning()) {
            return response()->json([
                'message' => 'Only running jobs can be cancelled',
                'current_status' => $importJob->status,
            ], 400);
        }

        try {
            $this->importService->cancelImport($importJob);

            return response()->json([
                'message' => 'Import job cancelled successfully',
                'job' => $importJob->fresh(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to cancel import job',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get real-time status for a job.
     */
    public function status(ImportJob $importJob): JsonResponse
    {
        return $this->getStatus($importJob);
    }

    /**
     * Get real-time status for a job.
     */
    public function getStatus(ImportJob $importJob): JsonResponse
    {
        $this->authorize('getStatus', $importJob);

        return response()->json([
            'job_id' => $importJob->id,
            'status' => $importJob->status,
            'progress_percentage' => $importJob->progress_percentage,
            'current_operation' => $importJob->current_operation,
            'records_processed' => $importJob->records_processed,
            'records_imported' => $importJob->records_imported,
            'records_skipped' => $importJob->records_skipped,
            'records_failed' => $importJob->records_failed,
            'started_at' => $importJob->started_at,
            'completed_at' => $importJob->completed_at,
            'duration' => $importJob->duration,
            'estimated_completion' => $this->estimateCompletion($importJob),
        ]);
    }

    /**
     * Estimate job completion time based on current progress.
     */
    protected function estimateCompletion(ImportJob $importJob): ?string
    {
        if (!$importJob->isRunning() || !$importJob->started_at) {
            return null;
        }

        $progress = $importJob->progress_percentage;
        if ($progress <= 0) {
            return null;
        }

        $elapsed = now()->diffInSeconds($importJob->started_at);
        $totalEstimated = ($elapsed / $progress) * 100;
        $remaining = max(0, $totalEstimated - $elapsed);

        return now()->addSeconds($remaining)->toISOString();
    }
}
