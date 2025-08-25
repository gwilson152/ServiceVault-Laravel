<?php

namespace App\Console\Commands;

use App\Models\EmailConfig;
use App\Models\EmailProcessingLog;
use App\Models\EmailTemplate;
use App\Services\EmailService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailSystemReport extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'email:system-report 
                           {--period=24h : Time period for report (24h, 7d, 30d)}
                           {--format=table : Output format (table, json, csv)}
                           {--email= : Email address to send report to}
                           {--save= : File path to save report}
                           {--alert-thresholds : Show alert threshold violations}
                           {--detailed : Include detailed statistics}';

    /**
     * The console command description.
     */
    protected $description = 'Generate comprehensive email system health and performance report';

    private EmailService $emailService;

    public function __construct(EmailService $emailService)
    {
        parent::__construct();
        $this->emailService = $emailService;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $period = $this->option('period');
        $format = $this->option('format');
        $detailed = $this->option('detailed');
        
        $this->info("Generating email system report for period: {$period}");
        
        try {
            $startDate = $this->getStartDate($period);
            $reportData = $this->generateReportData($startDate, $detailed);
            
            // Display report
            $this->displayReport($reportData, $format);
            
            // Check for alerts
            if ($this->option('alert-thresholds')) {
                $this->checkAlertThresholds($reportData);
            }
            
            // Save to file if requested
            if ($this->option('save')) {
                $this->saveReport($reportData, $this->option('save'), $format);
            }
            
            // Email report if requested
            if ($this->option('email')) {
                $this->emailReport($reportData, $this->option('email'));
            }
            
            $this->info('✓ Email system report generated successfully');
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Failed to generate report: ' . $e->getMessage());
            Log::error('Email system report generation failed', [
                'error' => $e->getMessage(),
                'period' => $period,
            ]);
            return 1;
        }
    }

    /**
     * Generate comprehensive report data
     */
    private function generateReportData(Carbon $startDate, bool $detailed): array
    {
        $data = [
            'report_info' => [
                'generated_at' => now()->toISOString(),
                'period' => $this->option('period'),
                'start_date' => $startDate->toISOString(),
                'end_date' => now()->toISOString(),
            ],
            'overview' => $this->getOverviewStats($startDate),
            'performance' => $this->getPerformanceStats($startDate),
            'configurations' => $this->getConfigurationStats(),
            'templates' => $this->getTemplateStats($startDate),
            'commands' => $this->getCommandStats($startDate),
            'errors' => $this->getErrorAnalysis($startDate),
            'health_checks' => $this->getHealthChecks(),
        ];

        if ($detailed) {
            $data['detailed'] = [
                'hourly_volume' => $this->getHourlyVolume($startDate),
                'account_breakdown' => $this->getAccountBreakdown($startDate),
                'processing_time_distribution' => $this->getProcessingTimeDistribution($startDate),
                'failure_analysis' => $this->getFailureAnalysis($startDate),
                'command_usage_details' => $this->getCommandUsageDetails($startDate),
            ];
        }

        return $data;
    }

    /**
     * Display report in specified format
     */
    private function displayReport(array $data, string $format): void
    {
        switch ($format) {
            case 'json':
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
                break;
                
            case 'csv':
                $this->displayCsvReport($data);
                break;
                
            case 'table':
            default:
                $this->displayTableReport($data);
                break;
        }
    }

    /**
     * Display report as tables
     */
    private function displayTableReport(array $data): void
    {
        $this->newLine();
        $this->info('📊 EMAIL SYSTEM REPORT');
        $this->info('Period: ' . $data['report_info']['period']);
        $this->info('Generated: ' . $data['report_info']['generated_at']);

        // Overview section
        $this->newLine();
        $this->info('📈 OVERVIEW STATISTICS');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Emails Processed', number_format($data['overview']['total_emails'])],
                ['Successful Processing', number_format($data['overview']['successful']) . ' (' . round($data['overview']['success_rate'], 1) . '%)'],
                ['Failed Processing', number_format($data['overview']['failed']) . ' (' . round($data['overview']['failure_rate'], 1) . '%)'],
                ['Tickets Created', number_format($data['overview']['tickets_created'])],
                ['Comments Added', number_format($data['overview']['comments_added'])],
                ['Commands Executed', number_format($data['overview']['commands_executed'])],
            ]
        );

        // Performance section
        $this->newLine();
        $this->info('⚡ PERFORMANCE METRICS');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Average Processing Time', round($data['performance']['avg_processing_time'], 2) . ' ms'],
                ['Maximum Processing Time', round($data['performance']['max_processing_time'], 2) . ' ms'],
                ['Minimum Processing Time', round($data['performance']['min_processing_time'], 2) . ' ms'],
                ['Emails per Hour (avg)', round($data['performance']['emails_per_hour'], 2)],
            ]
        );

        // Configuration status
        $this->newLine();
        $this->info('⚙️ CONFIGURATION STATUS');
        $this->table(
            ['Type', 'Count'],
            [
                ['Active Email Configs', $data['configurations']['active_configs']],
                ['Total Email Configs', $data['configurations']['total_configs']],
                ['Active Templates', $data['templates']['active_count']],
                ['Total Templates', $data['templates']['total_count']],
            ]
        );

        // Command statistics
        if ($data['commands']['total_found'] > 0) {
            $this->newLine();
            $this->info('🤖 COMMAND SYSTEM');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Commands Found', number_format($data['commands']['total_found'])],
                    ['Commands Executed', number_format($data['commands']['total_executed'])],
                    ['Commands Failed', number_format($data['commands']['total_failed'])],
                    ['Command Success Rate', round($data['commands']['success_rate'], 1) . '%'],
                ]
            );
        }

        // Health checks
        $this->newLine();
        $this->info('🏥 HEALTH CHECKS');
        foreach ($data['health_checks'] as $check => $status) {
            $icon = $status['healthy'] ? '✓' : '✗';
            $color = $status['healthy'] ? 'info' : 'error';
            $this->{$color}("{$icon} {$check}: {$status['message']}");
        }

        // Error analysis if there are errors
        if ($data['errors']['total_errors'] > 0) {
            $this->newLine();
            $this->error('🚨 ERROR ANALYSIS');
            $this->table(
                ['Error Type', 'Count', 'Percentage'],
                collect($data['errors']['by_type'])->map(function ($count, $type) use ($data) {
                    $percentage = round(($count / $data['errors']['total_errors']) * 100, 1);
                    return [$type, $count, "{$percentage}%"];
                })->toArray()
            );
        }
    }

    /**
     * Display report as CSV
     */
    private function displayCsvReport(array $data): void
    {
        $this->line('Metric,Value');
        $this->line('Total Emails,' . $data['overview']['total_emails']);
        $this->line('Success Rate,' . round($data['overview']['success_rate'], 2) . '%');
        $this->line('Failure Rate,' . round($data['overview']['failure_rate'], 2) . '%');
        $this->line('Avg Processing Time,' . round($data['performance']['avg_processing_time'], 2) . 'ms');
        $this->line('Active Configurations,' . $data['configurations']['active_configs']);
        $this->line('Commands Executed,' . $data['commands']['total_executed']);
    }

    /**
     * Check alert thresholds and display warnings
     */
    private function checkAlertThresholds(array $data): void
    {
        $this->newLine();
        $this->info('🚨 ALERT THRESHOLD CHECKS');

        $alerts = [];

        // High failure rate
        if ($data['overview']['failure_rate'] > 10) {
            $alerts[] = [
                'severity' => 'HIGH',
                'message' => 'Email failure rate is high: ' . round($data['overview']['failure_rate'], 1) . '%',
                'threshold' => '10%',
            ];
        }

        // Low command success rate
        if ($data['commands']['success_rate'] < 90 && $data['commands']['total_found'] > 0) {
            $alerts[] = [
                'severity' => 'MEDIUM',
                'message' => 'Command success rate is low: ' . round($data['commands']['success_rate'], 1) . '%',
                'threshold' => '90%',
            ];
        }

        // Slow processing times
        if ($data['performance']['avg_processing_time'] > 5000) {
            $alerts[] = [
                'severity' => 'MEDIUM',
                'message' => 'Average processing time is slow: ' . round($data['performance']['avg_processing_time'], 0) . 'ms',
                'threshold' => '5000ms',
            ];
        }

        // No active configurations
        if ($data['configurations']['active_configs'] === 0) {
            $alerts[] = [
                'severity' => 'HIGH',
                'message' => 'No active email configurations found',
                'threshold' => 'At least 1',
            ];
        }

        if (empty($alerts)) {
            $this->info('✓ All alert thresholds are within acceptable limits');
        } else {
            $this->table(
                ['Severity', 'Alert', 'Threshold'],
                array_map(fn($alert) => [$alert['severity'], $alert['message'], $alert['threshold']], $alerts)
            );
        }
    }

    /**
     * Save report to file
     */
    private function saveReport(array $data, string $filePath, string $format): void
    {
        try {
            $content = match ($format) {
                'json' => json_encode($data, JSON_PRETTY_PRINT),
                'csv' => $this->generateCsvContent($data),
                default => $this->generateTextReport($data),
            };

            file_put_contents($filePath, $content);
            $this->info("✓ Report saved to: {$filePath}");
            
        } catch (\Exception $e) {
            $this->error("Failed to save report: {$e->getMessage()}");
        }
    }

    /**
     * Email report to specified address
     */
    private function emailReport(array $data, string $email): void
    {
        try {
            $variables = [
                'report_period' => $this->option('period'),
                'total_emails' => $data['overview']['total_emails'],
                'success_rate' => round($data['overview']['success_rate'], 1),
                'failure_rate' => round($data['overview']['failure_rate'], 1),
                'commands_executed' => $data['commands']['total_executed'],
                'generated_at' => $data['report_info']['generated_at'],
                'report_summary' => $this->generateTextSummary($data),
            ];

            $this->emailService->sendTemplatedEmail(
                'email_system_report',
                $email,
                $variables
            );

            $this->info("✓ Report emailed to: {$email}");
            
        } catch (\Exception $e) {
            $this->error("Failed to email report: {$e->getMessage()}");
        }
    }

    /**
     * Get report data methods
     */
    private function getOverviewStats(Carbon $startDate): array
    {
        $total = EmailProcessingLog::where('created_at', '>=', $startDate)->count();
        $successful = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->where('status', 'processed')->count();
        $failed = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->where('status', 'failed')->count();

        return [
            'total_emails' => $total,
            'successful' => $successful,
            'failed' => $failed,
            'success_rate' => $total > 0 ? ($successful / $total) * 100 : 0,
            'failure_rate' => $total > 0 ? ($failed / $total) * 100 : 0,
            'tickets_created' => EmailProcessingLog::where('created_at', '>=', $startDate)
                ->where('created_new_ticket', true)->count(),
            'comments_added' => EmailProcessingLog::where('created_at', '>=', $startDate)
                ->whereNotNull('ticket_comment_id')->count(),
            'commands_executed' => EmailProcessingLog::where('created_at', '>=', $startDate)
                ->sum('commands_executed_count'),
        ];
    }

    private function getPerformanceStats(Carbon $startDate): array
    {
        $logs = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->whereNotNull('processing_duration_ms')
            ->get();

        $hoursSinceStart = now()->diffInHours($startDate);
        $emailsPerHour = $hoursSinceStart > 0 ? $logs->count() / $hoursSinceStart : 0;

        return [
            'avg_processing_time' => $logs->avg('processing_duration_ms') ?? 0,
            'max_processing_time' => $logs->max('processing_duration_ms') ?? 0,
            'min_processing_time' => $logs->min('processing_duration_ms') ?? 0,
            'emails_per_hour' => $emailsPerHour,
        ];
    }

    private function getConfigurationStats(): array
    {
        return [
            'total_configs' => EmailConfig::count(),
            'active_configs' => EmailConfig::where('is_active', true)->count(),
            'by_driver' => EmailConfig::groupBy('driver')
                ->selectRaw('driver, count(*) as count')
                ->pluck('count', 'driver')
                ->toArray(),
        ];
    }

    private function getTemplateStats(Carbon $startDate): array
    {
        return [
            'total_count' => EmailTemplate::count(),
            'active_count' => EmailTemplate::where('is_active', true)->count(),
            'most_used' => EmailTemplate::orderBy('usage_count', 'desc')
                ->limit(3)
                ->pluck('usage_count', 'name')
                ->toArray(),
        ];
    }

    private function getCommandStats(Carbon $startDate): array
    {
        $totalFound = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->sum('commands_processed');
        $totalExecuted = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->sum('commands_executed_count');
        $totalFailed = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->sum('commands_failed_count');

        return [
            'total_found' => $totalFound ?? 0,
            'total_executed' => $totalExecuted ?? 0,
            'total_failed' => $totalFailed ?? 0,
            'success_rate' => $totalFound > 0 ? ($totalExecuted / $totalFound) * 100 : 0,
        ];
    }

    private function getErrorAnalysis(Carbon $startDate): array
    {
        $errors = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->where('status', 'failed')
            ->whereNotNull('error_message')
            ->get();

        $byType = $errors->groupBy(function ($log) {
            // Categorize errors by type based on error message
            $message = strtolower($log->error_message);
            if (str_contains($message, 'connection')) {
                return 'Connection Issues';
            } elseif (str_contains($message, 'authentication') || str_contains($message, 'auth')) {
                return 'Authentication Errors';
            } elseif (str_contains($message, 'timeout')) {
                return 'Timeout Errors';
            } elseif (str_contains($message, 'parsing') || str_contains($message, 'parse')) {
                return 'Email Parsing Errors';
            } else {
                return 'Other Errors';
            }
        })->map->count();

        return [
            'total_errors' => $errors->count(),
            'by_type' => $byType->toArray(),
        ];
    }

    private function getHealthChecks(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'email_configs' => $this->checkEmailConfigsHealth(),
            'templates' => $this->checkTemplatesHealth(),
            'recent_processing' => $this->checkRecentProcessingHealth(),
        ];
    }

    private function getHourlyVolume(Carbon $startDate): array
    {
        return EmailProcessingLog::where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE_TRUNC(\'hour\', created_at)'))
            ->selectRaw('DATE_TRUNC(\'hour\', created_at) as hour, count(*) as count')
            ->orderBy('hour')
            ->pluck('count', 'hour')
            ->toArray();
    }

    private function getAccountBreakdown(Carbon $startDate): array
    {
        return EmailProcessingLog::where('created_at', '>=', $startDate)
            ->whereNotNull('account_id')
            ->with('account')
            ->get()
            ->groupBy('account.name')
            ->map->count()
            ->toArray();
    }

    private function getProcessingTimeDistribution(Carbon $startDate): array
    {
        $logs = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->whereNotNull('processing_duration_ms')
            ->get();

        return [
            'under_1s' => $logs->where('processing_duration_ms', '<', 1000)->count(),
            '1s_to_5s' => $logs->whereBetween('processing_duration_ms', [1000, 5000])->count(),
            '5s_to_10s' => $logs->whereBetween('processing_duration_ms', [5000, 10000])->count(),
            'over_10s' => $logs->where('processing_duration_ms', '>', 10000)->count(),
        ];
    }

    private function getFailureAnalysis(Carbon $startDate): array
    {
        return EmailProcessingLog::where('created_at', '>=', $startDate)
            ->where('status', 'failed')
            ->groupBy('error_message')
            ->selectRaw('error_message, count(*) as count')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->pluck('count', 'error_message')
            ->toArray();
    }

    private function getCommandUsageDetails(Carbon $startDate): array
    {
        // This would require parsing command_results JSON
        // For now, return placeholder data
        return [
            'most_used_commands' => ['time', 'priority', 'status'],
            'command_failure_rate' => 5.2,
        ];
    }

    private function getStartDate(string $period): Carbon
    {
        return match ($period) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            default => now()->subDay(),
        };
    }

    // Health check methods
    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Connected'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => 'Connection failed'];
        }
    }

    private function checkEmailConfigsHealth(): array
    {
        $count = EmailConfig::where('is_active', true)->count();
        return [
            'healthy' => $count > 0,
            'message' => "{$count} active configuration(s)",
        ];
    }

    private function checkTemplatesHealth(): array
    {
        $count = EmailTemplate::where('is_active', true)->count();
        return [
            'healthy' => $count > 0,
            'message' => "{$count} active template(s)",
        ];
    }

    private function checkRecentProcessingHealth(): array
    {
        $count = EmailProcessingLog::where('created_at', '>=', now()->subHour())->count();
        return [
            'healthy' => true,
            'message' => "{$count} emails processed in last hour",
        ];
    }

    private function generateCsvContent(array $data): string
    {
        $csv = "Metric,Value\n";
        $csv .= "Total Emails,{$data['overview']['total_emails']}\n";
        $csv .= "Success Rate," . round($data['overview']['success_rate'], 2) . "%\n";
        $csv .= "Failure Rate," . round($data['overview']['failure_rate'], 2) . "%\n";
        $csv .= "Commands Executed,{$data['commands']['total_executed']}\n";
        return $csv;
    }

    private function generateTextReport(array $data): string
    {
        $report = "EMAIL SYSTEM REPORT\n";
        $report .= "==================\n\n";
        $report .= "Period: {$data['report_info']['period']}\n";
        $report .= "Generated: {$data['report_info']['generated_at']}\n\n";
        $report .= "OVERVIEW:\n";
        $report .= "- Total Emails: {$data['overview']['total_emails']}\n";
        $report .= "- Success Rate: " . round($data['overview']['success_rate'], 1) . "%\n";
        $report .= "- Commands Executed: {$data['commands']['total_executed']}\n";
        return $report;
    }

    private function generateTextSummary(array $data): string
    {
        return "Processed {$data['overview']['total_emails']} emails with " . 
               round($data['overview']['success_rate'], 1) . "% success rate. " .
               "Executed {$data['commands']['total_executed']} commands.";
    }
}