<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ImportJob;
use App\Models\ImportProfile;
use App\Models\ImportRecord;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ImportAnalyticsController extends Controller
{
    /**
     * Get comprehensive import dashboard analytics
     */
    public function dashboard(Request $request)
    {
        $request->validate([
            'period' => 'in:7d,30d,90d,1y,all',
            'profile_id' => 'uuid|exists:import_profiles,id',
        ]);

        $period = $request->get('period', '30d');
        $profileId = $request->get('profile_id');

        // Calculate date range
        $dateFrom = $this->getDateFromPeriod($period);

        // Base queries with optional profile filtering
        $jobsQuery = ImportJob::query();
        $recordsQuery = ImportRecord::query();

        if ($profileId) {
            $jobsQuery->where('profile_id', $profileId);
            $recordsQuery->where('import_profile_id', $profileId);
        }

        if ($dateFrom) {
            $jobsQuery->where('created_at', '>=', $dateFrom);
            $recordsQuery->where('created_at', '>=', $dateFrom);
        }

        return response()->json([
            'overview' => $this->getOverviewStats($jobsQuery, $recordsQuery),
            'job_statistics' => $this->getJobStatistics($jobsQuery, $dateFrom),
            'record_statistics' => $this->getRecordStatistics($recordsQuery, $dateFrom),
            'profile_performance' => $this->getProfilePerformance($dateFrom),
            'trend_data' => $this->getTrendData($dateFrom, $profileId),
            'duplicate_insights' => $this->getDuplicateInsights($recordsQuery),
            'error_analysis' => $this->getErrorAnalysis($jobsQuery, $recordsQuery),
        ]);
    }

    /**
     * Get job performance details for a specific job
     */
    public function jobDetails(ImportJob $job)
    {
        $job->load(['profile', 'creator']);

        // Get record breakdown
        $recordStats = ImportRecord::where('import_job_id', $job->id)
            ->selectRaw('
                import_action,
                target_type,
                COUNT(*) as count,
                AVG(CASE WHEN matching_fields IS NOT NULL THEN array_length(matching_fields::json, 1) ELSE 0 END) as avg_match_fields
            ')
            ->groupBy(['import_action', 'target_type'])
            ->get();

        // Get duplicate analysis
        $duplicateStats = ImportRecord::where('import_job_id', $job->id)
            ->whereNotNull('duplicate_of')
            ->selectRaw('
                target_type,
                COUNT(*) as duplicate_count,
                COUNT(DISTINCT duplicate_of) as unique_originals
            ')
            ->groupBy('target_type')
            ->get();

        // Get errors breakdown
        $errorStats = ImportRecord::where('import_job_id', $job->id)
            ->where('import_action', 'failed')
            ->selectRaw('
                target_type,
                error_message,
                COUNT(*) as count
            ')
            ->groupBy(['target_type', 'error_message'])
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'job' => $job,
            'record_breakdown' => $recordStats,
            'duplicate_analysis' => $duplicateStats,
            'error_breakdown' => $errorStats,
            'performance_metrics' => [
                'duration_seconds' => $job->duration,
                'records_per_second' => $job->duration ? round($job->records_processed / $job->duration, 2) : 0,
                'success_rate' => $job->success_rate,
                'duplicate_rate' => $job->records_processed ? round(($duplicateStats->sum('duplicate_count') / $job->records_processed) * 100, 2) : 0,
            ],
        ]);
    }

    /**
     * Get profile analytics for comparison and optimization
     */
    public function profileAnalytics(Request $request)
    {
        $request->validate([
            'period' => 'in:7d,30d,90d,1y,all',
        ]);

        $period = $request->get('period', '30d');
        $dateFrom = $this->getDateFromPeriod($period);

        $profiles = ImportProfile::withCount([
            'importJobs' => function ($query) use ($dateFrom) {
                if ($dateFrom) {
                    $query->where('created_at', '>=', $dateFrom);
                }
            },
        ])->with(['importJobs' => function ($query) use ($dateFrom) {
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            $query->selectRaw('
                profile_id,
                COUNT(*) as total_jobs,
                SUM(records_processed) as total_records,
                SUM(records_imported) as total_imported,
                SUM(records_updated) as total_updated,
                SUM(records_skipped) as total_skipped,
                SUM(records_failed) as total_failed,
                AVG(EXTRACT(EPOCH FROM (completed_at - started_at))) as avg_duration,
                COUNT(CASE WHEN status = "completed" THEN 1 END) as successful_jobs,
                COUNT(CASE WHEN status = "failed" THEN 1 END) as failed_jobs
            ')->groupBy('profile_id');
        }])->get();

        return response()->json([
            'profiles' => $profiles->map(function ($profile) {
                $jobStats = $profile->importJobs->first();

                return [
                    'id' => $profile->id,
                    'name' => $profile->name,
                    'database_type' => $profile->database_type,
                    'import_mode' => $profile->import_mode,
                    'is_active' => $profile->is_active,
                    'statistics' => [
                        'total_jobs' => $jobStats->total_jobs ?? 0,
                        'total_records' => $jobStats->total_records ?? 0,
                        'total_imported' => $jobStats->total_imported ?? 0,
                        'total_updated' => $jobStats->total_updated ?? 0,
                        'total_skipped' => $jobStats->total_skipped ?? 0,
                        'total_failed' => $jobStats->total_failed ?? 0,
                        'avg_duration' => $jobStats->avg_duration ?? 0,
                        'success_rate' => $jobStats->total_jobs ? round(($jobStats->successful_jobs / $jobStats->total_jobs) * 100, 2) : 0,
                        'records_per_second' => $jobStats->avg_duration ? round($jobStats->total_records / $jobStats->avg_duration, 2) : 0,
                    ],
                    'last_import' => $profile->import_stats['last_import'] ?? null,
                ];
            }),
        ]);
    }

    /**
     * Get detailed record analysis with filtering
     */
    public function recordAnalysis(Request $request)
    {
        $request->validate([
            'period' => 'in:7d,30d,90d,1y,all',
            'profile_id' => 'uuid|exists:import_profiles,id',
            'target_type' => 'string',
            'import_action' => 'in:created,updated,skipped,failed',
            'limit' => 'integer|min:1|max:1000',
        ]);

        $period = $request->get('period', '30d');
        $profileId = $request->get('profile_id');
        $targetType = $request->get('target_type');
        $importAction = $request->get('import_action');
        $limit = $request->get('limit', 100);

        $dateFrom = $this->getDateFromPeriod($period);

        $query = ImportRecord::with(['importProfile', 'importJob'])
            ->orderBy('created_at', 'desc');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($profileId) {
            $query->where('import_profile_id', $profileId);
        }

        if ($targetType) {
            $query->where('target_type', $targetType);
        }

        if ($importAction) {
            $query->where('import_action', $importAction);
        }

        $records = $query->paginate($limit);

        // Get aggregated statistics for the filtered results
        $statsQuery = clone $query;
        $stats = $statsQuery->selectRaw('
            import_action,
            COUNT(*) as count,
            COUNT(CASE WHEN duplicate_of IS NOT NULL THEN 1 END) as duplicate_count
        ')->groupBy('import_action')->get();

        return response()->json([
            'records' => $records,
            'statistics' => $stats,
            'filters_applied' => [
                'period' => $period,
                'profile_id' => $profileId,
                'target_type' => $targetType,
                'import_action' => $importAction,
            ],
        ]);
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats($jobsQuery, $recordsQuery)
    {
        $totalJobs = $jobsQuery->count();
        $successfulJobs = $jobsQuery->where('status', 'completed')->count();
        $runningJobs = $jobsQuery->where('status', 'running')->count();

        $recordCounts = $recordsQuery->selectRaw('
            COUNT(*) as total,
            COUNT(CASE WHEN import_action = "created" THEN 1 END) as created,
            COUNT(CASE WHEN import_action = "updated" THEN 1 END) as updated,
            COUNT(CASE WHEN import_action = "skipped" THEN 1 END) as skipped,
            COUNT(CASE WHEN import_action = "failed" THEN 1 END) as failed,
            COUNT(CASE WHEN duplicate_of IS NOT NULL THEN 1 END) as duplicates
        ')->first();

        return [
            'total_jobs' => $totalJobs,
            'successful_jobs' => $successfulJobs,
            'running_jobs' => $runningJobs,
            'job_success_rate' => $totalJobs ? round(($successfulJobs / $totalJobs) * 100, 2) : 0,
            'total_records' => $recordCounts->total ?? 0,
            'records_created' => $recordCounts->created ?? 0,
            'records_updated' => $recordCounts->updated ?? 0,
            'records_skipped' => $recordCounts->skipped ?? 0,
            'records_failed' => $recordCounts->failed ?? 0,
            'duplicate_records' => $recordCounts->duplicates ?? 0,
        ];
    }

    /**
     * Get job statistics
     */
    private function getJobStatistics($jobsQuery, $dateFrom)
    {
        return $jobsQuery->selectRaw('
            status,
            COUNT(*) as count,
            AVG(records_processed) as avg_records,
            AVG(EXTRACT(EPOCH FROM (completed_at - started_at))) as avg_duration
        ')->groupBy('status')->get();
    }

    /**
     * Get record statistics
     */
    private function getRecordStatistics($recordsQuery, $dateFrom)
    {
        return $recordsQuery->selectRaw('
            target_type,
            import_action,
            COUNT(*) as count
        ')->groupBy(['target_type', 'import_action'])->get();
    }

    /**
     * Get profile performance comparison
     */
    private function getProfilePerformance($dateFrom)
    {
        $query = ImportProfile::selectRaw('
            import_profiles.id,
            import_profiles.name,
            import_profiles.import_mode,
            COUNT(import_jobs.id) as job_count,
            SUM(import_jobs.records_processed) as total_records,
            AVG(EXTRACT(EPOCH FROM (import_jobs.completed_at - import_jobs.started_at))) as avg_duration
        ')->leftJoin('import_jobs', 'import_profiles.id', '=', 'import_jobs.profile_id');

        if ($dateFrom) {
            $query->where('import_jobs.created_at', '>=', $dateFrom);
        }

        return $query->groupBy('import_profiles.id', 'import_profiles.name', 'import_profiles.import_mode')
            ->orderBy('total_records', 'desc')
            ->limit(10)
            ->get();
    }

    /**
     * Get trend data for charts
     */
    private function getTrendData($dateFrom, $profileId)
    {
        $query = ImportJob::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as jobs,
            SUM(records_processed) as records,
            SUM(records_imported) as imported,
            SUM(records_failed) as failed
        ');

        if ($dateFrom) {
            $query->where('created_at', '>=', $dateFrom);
        }

        if ($profileId) {
            $query->where('profile_id', $profileId);
        }

        return $query->groupBy('date')
            ->orderBy('date')
            ->get();
    }

    /**
     * Get duplicate detection insights
     */
    private function getDuplicateInsights($recordsQuery)
    {
        return $recordsQuery->whereNotNull('duplicate_of')
            ->selectRaw('
                target_type,
                COUNT(*) as duplicate_count,
                COUNT(DISTINCT duplicate_of) as unique_originals,
                AVG(array_length(matching_fields::json, 1)) as avg_matching_fields
            ')
            ->groupBy('target_type')
            ->get();
    }

    /**
     * Get error analysis
     */
    private function getErrorAnalysis($jobsQuery, $recordsQuery)
    {
        $jobErrors = $jobsQuery->where('status', 'failed')
            ->selectRaw('errors, COUNT(*) as count')
            ->whereNotNull('errors')
            ->groupBy('errors')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        $recordErrors = $recordsQuery->where('import_action', 'failed')
            ->selectRaw('error_message, target_type, COUNT(*) as count')
            ->whereNotNull('error_message')
            ->groupBy(['error_message', 'target_type'])
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        return [
            'job_errors' => $jobErrors,
            'record_errors' => $recordErrors,
        ];
    }

    /**
     * Convert period string to Carbon date
     */
    private function getDateFromPeriod($period)
    {
        return match ($period) {
            '7d' => Carbon::now()->subDays(7),
            '30d' => Carbon::now()->subDays(30),
            '90d' => Carbon::now()->subDays(90),
            '1y' => Carbon::now()->subYear(),
            'all' => null,
            default => Carbon::now()->subDays(30),
        };
    }
}
