<?php

namespace App\Console\Commands;

use App\Models\ImportJob;
use Illuminate\Console\Command;

class CleanupStaleImportJobs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cleanup-stale {--dry-run : Show what would be cleaned up without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up stale import jobs that have been running for more than 2 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for stale import jobs...');

        $staleJobs = ImportJob::where('status', 'running')
            ->where('started_at', '<', now()->subHours(2))
            ->get();

        if ($staleJobs->isEmpty()) {
            $this->info('No stale import jobs found.');
            return Command::SUCCESS;
        }

        $this->warn("Found {$staleJobs->count()} stale import jobs:");
        
        $headers = ['ID', 'Started At', 'Duration (hours)', 'Current Operation'];
        $rows = [];
        
        foreach ($staleJobs as $job) {
            $duration = $job->started_at ? round($job->started_at->diffInHours(now()), 1) : 'Unknown';
            $rows[] = [
                substr($job->id, 0, 8) . '...',
                $job->started_at?->format('Y-m-d H:i:s') ?? 'Unknown',
                $duration,
                $job->current_operation ?? 'No operation details'
            ];
        }

        $this->table($headers, $rows);

        if ($this->option('dry-run')) {
            $this->info('Dry run mode - no changes made.');
            return Command::SUCCESS;
        }

        if (!$this->confirm('Mark these jobs as failed?', true)) {
            $this->info('Operation cancelled.');
            return Command::SUCCESS;
        }

        $cleanedCount = ImportJob::cleanupStaleJobs();
        
        $this->info("Successfully marked {$cleanedCount} stale import jobs as failed.");
        
        return Command::SUCCESS;
    }
}
