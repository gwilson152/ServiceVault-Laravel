<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSystemConfig;
use App\Models\EmailDomainMapping;
use App\Models\EmailProcessingLog;
use App\Models\Account;
use App\Models\User;
use App\Jobs\ProcessIncomingEmail;
use App\Jobs\ProcessOutgoingEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailAdminController extends Controller
{
    /**
     * Get comprehensive email system dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Check if user can access email system administration
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access email administration');
        }

        $timeRange = $request->get('time_range', '24h');
        $startDate = $this->getStartDate($timeRange);

        try {
            $dashboardData = [
                // System health and configuration
                'system_health' => $this->getSystemHealth(),
                
                // Overview statistics
                'overview' => $this->getOverviewStats($startDate),
                
                // Processing performance
                'performance' => $this->getPerformanceStats($startDate),
                
                // Domain mapping statistics
                'domain_mappings' => $this->getDomainMappingStats(),
                
                // Queue health
                'queue_health' => $this->getQueueHealth(),
                
                // Recent activity
                'recent_activity' => $this->getRecentActivity(),
                
                // System alerts
                'alerts' => $this->getSystemAlerts($startDate),
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'time_range' => $timeRange,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Email admin dashboard error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard data',
                'message' => 'An error occurred while loading the email administration dashboard',
            ], 500);
        }
    }

    /**
     * Get email processing logs with advanced filtering
     */
    public function getProcessingLogs(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access email processing logs');
        }

        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'status' => 'nullable|in:pending,processing,processed,failed,retry',
            'direction' => 'nullable|in:incoming,outgoing',
            'account_id' => 'nullable|exists:accounts,id',
            'has_commands' => 'nullable|boolean',
            'command_success' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = EmailProcessingLog::with(['account', 'ticket'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('direction')) {
                $query->where('direction', $request->direction);
            }

            if ($request->filled('account_id')) {
                $query->where('account_id', $request->account_id);
            }

            if ($request->filled('has_commands')) {
                if ($request->boolean('has_commands')) {
                    $query->where('commands_processed', '>', 0);
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('commands_processed')->orWhere('commands_processed', 0);
                    });
                }
            }

            if ($request->filled('command_success')) {
                $query->where('command_processing_success', $request->boolean('command_success'));
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'ILIKE', "%{$search}%")
                      ->orWhere('from_address', 'ILIKE', "%{$search}%")
                      ->orWhere('email_id', 'ILIKE', "%{$search}%");
                });
            }

            $logs = $query->paginate($request->get('per_page', 25));

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch email processing logs', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch processing logs',
            ], 500);
        }
    }

    /**
     * Get detailed processing log information
     */
    public function getProcessingLogDetail(string $emailId): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access email processing details');
        }

        try {
            $log = EmailProcessingLog::where('email_id', $emailId)
                ->with(['account', 'ticket.comments'])
                ->first();

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'error' => 'Processing log not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch processing log detail', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch processing log details',
            ], 500);
        }
    }

    /**
     * Retry failed email processing
     */
    public function retryProcessing(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to retry email processing');
        }

        $validator = Validator::make($request->all(), [
            'email_ids' => 'required|array|min:1|max:50',
            'email_ids.*' => 'required|string',
            'force' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $emailIds = $request->email_ids;
            $force = $request->boolean('force', false);
            $retryCount = 0;
            $failedCount = 0;

            foreach ($emailIds as $emailId) {
                $log = EmailProcessingLog::where('email_id', $emailId)->first();
                
                if (!$log) {
                    $failedCount++;
                    continue;
                }

                // Check if retry is allowed
                if (!$force && ($log->retry_count >= 3 || $log->status === 'processed')) {
                    $failedCount++;
                    continue;
                }

                // Reset status and dispatch job
                $log->update([
                    'status' => 'pending',
                    'next_retry_at' => null,
                    'error_message' => null,
                ]);

                // Reconstruct email data for reprocessing
                $emailData = [
                    'email_id' => $log->email_id,
                    'direction' => $log->direction,
                    'from' => $log->from_address,
                    'to' => $log->to_addresses ?? [],
                    'subject' => $log->subject,
                    'message_id' => $log->message_id,
                    'raw_content' => $log->raw_email_content,
                    'received_at' => $log->received_at,
                ];

                ProcessIncomingEmail::dispatch($emailData);
                $retryCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Retry initiated for {$retryCount} emails",
                'retried' => $retryCount,
                'failed' => $failedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk retry processing failed', [
                'error' => $e->getMessage(),
                'email_ids' => $request->email_ids,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Retry processing failed',
            ], 500);
        }
    }

    /**
     * Get queue monitoring information
     */
    public function getQueueStatus(): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access queue status');
        }

        try {
            $queueData = [
                'queues' => [
                    'email-incoming' => $this->getQueueInfo('email-incoming'),
                    'email-outgoing' => $this->getQueueInfo('email-outgoing'),
                    'email-processing' => $this->getQueueInfo('email-processing'),
                    'default' => $this->getQueueInfo('default'),
                ],
                'failed_jobs' => $this->getFailedJobsInfo(),
                'workers' => $this->getWorkerStatus(),
                'health_check' => $this->performQueueHealthCheck(),
            ];

            return response()->json([
                'success' => true,
                'data' => $queueData,
                'checked_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Queue status check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to check queue status',
            ], 500);
        }
    }

    /**
     * Get system health metrics (private method for dashboard)
     */
    private function getSystemHealth(): array
    {
        try {
            $emailConfig = EmailSystemConfig::getConfig();
            
            $health = [
                'system_active' => $emailConfig->system_active ?? false,
                'fully_configured' => $emailConfig->isFullyConfigured() ?? false,
                'incoming_enabled' => $emailConfig->incoming_enabled ?? false,
                'outgoing_enabled' => $emailConfig->outgoing_enabled ?? false,
                'incoming_provider' => $emailConfig->incoming_provider ?? 'none',
                'outgoing_provider' => $emailConfig->outgoing_provider ?? 'none',
                'domain_mappings_count' => EmailDomainMapping::count(),
                'active_domain_mappings' => EmailDomainMapping::where('is_active', true)->count(),
                'last_updated' => $emailConfig->updated_at,
            ];

            return $health;

        } catch (\Exception $e) {
            Log::error('System health check failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'system_active' => false,
                'fully_configured' => false,
                'incoming_enabled' => false,
                'outgoing_enabled' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Clear failed jobs from queue
     */
    public function clearFailedJobs(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to clear failed jobs');
        }

        $validator = Validator::make($request->all(), [
            'older_than_hours' => 'integer|min:1|max:720', // Max 30 days
            'queue' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $olderThanHours = $request->get('older_than_hours', 24);
            $queue = $request->get('queue');
            
            // This would need to be implemented based on your queue driver
            // For database queue driver:
            $query = DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subHours($olderThanHours));
                
            if ($queue) {
                $query->where('queue', $queue);
            }
            
            $deletedCount = $query->count();
            $query->delete();

            Log::info('Failed jobs cleared', [
                'count' => $deletedCount,
                'older_than_hours' => $olderThanHours,
                'queue' => $queue,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} failed jobs",
                'cleared_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear failed jobs',
            ], 500);
        }
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(Carbon $startDate): array
    {
        return [
            'total_emails_processed' => EmailProcessingLog::where('created_at', '>=', $startDate)->count(),
            'successful_processing' => EmailProcessingLog::where('created_at', '>=', $startDate)->where('status', 'processed')->count(),
            'failed_processing' => EmailProcessingLog::where('created_at', '>=', $startDate)->where('status', 'failed')->count(),
            'tickets_created' => EmailProcessingLog::where('created_at', '>=', $startDate)->where('created_new_ticket', true)->count(),
            'comments_added' => EmailProcessingLog::where('created_at', '>=', $startDate)->whereNotNull('ticket_comment_id')->count(),
            'commands_executed' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_executed_count'),
            'system_configured' => EmailSystemConfig::getConfig()->isFullyConfigured(),
            'domain_mappings_active' => EmailDomainMapping::where('is_active', true)->count(),
        ];
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats(Carbon $startDate): array
    {
        $logs = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->whereNotNull('processing_duration_ms')
            ->get();

        return [
            'avg_processing_time_ms' => $logs->avg('processing_duration_ms'),
            'max_processing_time_ms' => $logs->max('processing_duration_ms'),
            'min_processing_time_ms' => $logs->min('processing_duration_ms'),
            'success_rate' => $logs->count() > 0 ? ($logs->where('status', 'processed')->count() / $logs->count()) * 100 : 0,
            'hourly_volume' => $this->getHourlyVolume($startDate),
        ];
    }

    /**
     * Get command statistics
     */
    private function getCommandStats(Carbon $startDate): array
    {
        return [
            'total_commands_found' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_processed'),
            'total_commands_executed' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_executed_count'),
            'total_commands_failed' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_failed_count'),
            'command_success_rate' => $this->getCommandSuccessRate($startDate),
            'popular_commands' => $this->getPopularCommands($startDate),
        ];
    }

    /**
     * Get domain mapping statistics
     */
    private function getDomainMappingStats(): array
    {
        return [
            'total_mappings' => EmailDomainMapping::count(),
            'active_mappings' => EmailDomainMapping::where('is_active', true)->count(),
            'by_type' => EmailDomainMapping::groupBy('pattern_type')
                ->selectRaw('pattern_type, count(*) as count')
                ->pluck('count', 'pattern_type')->toArray(),
            'recent_mappings' => EmailDomainMapping::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Get queue health information
     */
    private function getQueueHealth(): array
    {
        return [
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'processed_today' => $this->getProcessedTodayCount(),
            'oldest_pending' => $this->getOldestPendingJob(),
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity(): array
    {
        return EmailProcessingLog::with(['account', 'ticket'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($log) {
                return [
                    'email_id' => $log->email_id,
                    'status' => $log->status,
                    'from_address' => $log->from_address,
                    'subject' => $log->subject,
                    'created_at' => $log->created_at,
                    'ticket_number' => $log->ticket?->ticket_number,
                    'account_name' => $log->account?->name,
                ];
            });
    }

    /**
     * Get system alerts
     */
    private function getSystemAlerts(Carbon $startDate): array
    {
        $alerts = [];

        $emailConfig = EmailSystemConfig::getConfig();

        // Check if system is not configured
        if (!$emailConfig->system_active) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Email system is not active',
                'details' => 'Configure and activate the email system to start processing emails',
            ];
        }

        // Check if system is not fully configured
        if (!$emailConfig->isFullyConfigured()) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'Email system configuration incomplete',
                'details' => 'Complete incoming and outgoing email service configuration',
            ];
        }

        // Check for high failure rate
        $totalEmails = EmailProcessingLog::where('created_at', '>=', $startDate)->count();
        if ($totalEmails > 0) {
            $failedEmails = EmailProcessingLog::where('created_at', '>=', $startDate)->where('status', 'failed')->count();
            if (($failedEmails / $totalEmails) > 0.1) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'High email processing failure rate detected',
                    'details' => "Failed: {$failedEmails}/{$totalEmails} (" . round(($failedEmails / $totalEmails) * 100, 1) . "%)",
                ];
            }
        }

        // Check for domain mappings
        $activeMappings = EmailDomainMapping::where('is_active', true)->count();
        if ($activeMappings === 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'No active domain mappings configured',
                'details' => 'Set up domain mappings to route incoming emails to accounts',
            ];
        }

        return $alerts;
    }


    /**
     * Helper methods for statistics
     */
    private function getStartDate(string $range): Carbon
    {
        return match ($range) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            default => now()->subWeek(),
        };
    }

    private function getHourlyVolume(Carbon $startDate): array
    {
        return EmailProcessingLog::where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE_TRUNC(\'hour\', created_at)'))
            ->selectRaw('DATE_TRUNC(\'hour\', created_at) as hour, count(*) as count')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();
    }

    private function getCommandSuccessRate(Carbon $startDate): float
    {
        $totalCommands = EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_processed');
        $executedCommands = EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_executed_count');
        
        return $totalCommands > 0 ? ($executedCommands / $totalCommands) * 100 : 0;
    }

    private function getPopularCommands(Carbon $startDate): array
    {
        // This would require parsing command_results JSON to get specific command usage
        // For now, return placeholder data
        return [
            'time' => 150,
            'priority' => 89,
            'status' => 67,
            'assign' => 45,
            'due' => 23,
        ];
    }

    private function getPendingJobsCount(): int
    {
        return DB::table('jobs')->count();
    }

    private function getFailedJobsCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    private function getProcessedTodayCount(): int
    {
        return EmailProcessingLog::whereDate('created_at', today())->where('status', 'processed')->count();
    }

    private function getOldestPendingJob(): ?array
    {
        $job = DB::table('jobs')->orderBy('created_at')->first();
        
        if ($job) {
            return [
                'id' => $job->id,
                'queue' => $job->queue,
                'created_at' => $job->created_at,
            ];
        }
        
        return null;
    }

    private function getQueueInfo(string $queueName): array
    {
        $pending = DB::table('jobs')->where('queue', $queueName)->count();
        $failed = DB::table('failed_jobs')->where('queue', $queueName)->count();
        
        return [
            'pending' => $pending,
            'failed' => $failed,
            'status' => $pending > 100 ? 'warning' : 'healthy',
        ];
    }

    private function getFailedJobsInfo(): array
    {
        return [
            'total' => DB::table('failed_jobs')->count(),
            'by_queue' => DB::table('failed_jobs')
                ->groupBy('queue')
                ->selectRaw('queue, count(*) as count')
                ->pluck('count', 'queue')
                ->toArray(),
            'oldest' => DB::table('failed_jobs')->orderBy('failed_at')->first(),
        ];
    }

    private function getWorkerStatus(): array
    {
        // This would need to be implemented based on your worker monitoring system
        return [
            'active_workers' => 0, // Placeholder
            'status' => 'unknown',
        ];
    }

    private function performQueueHealthCheck(): array
    {
        $healthy = true;
        $issues = [];
        
        $pendingJobs = $this->getPendingJobsCount();
        if ($pendingJobs > 500) {
            $healthy = false;
            $issues[] = "High pending job count: {$pendingJobs}";
        }
        
        $failedJobs = $this->getFailedJobsCount();
        if ($failedJobs > 50) {
            $healthy = false;
            $issues[] = "High failed job count: {$failedJobs}";
        }
        
        return [
            'healthy' => $healthy,
            'issues' => $issues,
        ];
    }

    // Health check methods
    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkEmailSystemHealth(): array
    {
        $emailConfig = EmailSystemConfig::getConfig();
        return [
            'healthy' => $emailConfig->system_active && $emailConfig->isFullyConfigured(),
            'message' => $emailConfig->system_active ? 
                ($emailConfig->isFullyConfigured() ? 'Email system active and configured' : 'Email system active but not fully configured') :
                'Email system inactive',
            'system_active' => $emailConfig->system_active,
            'fully_configured' => $emailConfig->isFullyConfigured(),
        ];
    }

    private function checkDomainMappingsHealth(): array
    {
        $activeMappings = EmailDomainMapping::where('is_active', true)->count();
        return [
            'healthy' => $activeMappings > 0,
            'message' => "Active domain mappings: {$activeMappings}",
            'count' => $activeMappings,
        ];
    }

    private function checkQueueSystemHealth(): array
    {
        $health = $this->performQueueHealthCheck();
        return [
            'healthy' => $health['healthy'],
            'message' => $health['healthy'] ? 'Queue system healthy' : implode(', ', $health['issues']),
        ];
    }

    private function checkProcessingLogsHealth(): array
    {
        $recentFailures = EmailProcessingLog::where('created_at', '>=', now()->subHour())
            ->where('status', 'failed')
            ->count();
            
        return [
            'healthy' => $recentFailures < 10,
            'message' => "Recent failures (1h): {$recentFailures}",
            'count' => $recentFailures,
        ];
    }

    private function checkRecentProcessingHealth(): array
    {
        $recentProcessing = EmailProcessingLog::where('created_at', '>=', now()->subMinutes(15))->count();
        
        return [
            'healthy' => true, // Always healthy for now
            'message' => "Recent emails (15m): {$recentProcessing}",
            'count' => $recentProcessing,
        ];
    }

    private function getSystemUptime(): string
    {
        // This would need to be implemented based on your system monitoring
        return 'Unknown';
    }

    private function getMemoryUsage(): array
    {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function getDiskUsage(): array
    {
        $path = storage_path();
        return [
            'total' => disk_total_space($path),
            'free' => disk_free_space($path),
            'used' => disk_total_space($path) - disk_free_space($path),
        ];
    }
}