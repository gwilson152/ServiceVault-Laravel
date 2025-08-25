<?php

namespace App\Console\Commands;

use App\Models\ImportProfile;
use App\Services\ImportService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunImportSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:sync 
                            {--profile=* : Specific profile IDs to sync}
                            {--force : Force sync even if not scheduled}
                            {--dry-run : Show what would be synced without executing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled import syncs for configured profiles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Import Sync Command...');
        
        $specificProfiles = $this->option('profile');
        $force = $this->option('force');
        $dryRun = $this->option('dry-run');

        // Get profiles that need syncing
        $query = ImportProfile::where('sync_enabled', true)
            ->where('is_active', true);

        if (!empty($specificProfiles)) {
            $query->whereIn('id', $specificProfiles);
        } elseif (!$force) {
            // Only get profiles that are due for sync
            $query->where(function ($q) {
                $q->whereNull('next_sync_at')
                  ->orWhere('next_sync_at', '<=', now());
            });
        }

        $profiles = $query->get();

        if ($profiles->isEmpty()) {
            $this->info('No profiles need syncing at this time.');
            return 0;
        }

        $this->info("Found {$profiles->count()} profile(s) to sync.");

        $syncCount = 0;
        $errorCount = 0;

        foreach ($profiles as $profile) {
            $this->line('');
            $this->info("Processing: {$profile->name} (ID: {$profile->id})");
            
            if ($dryRun) {
                $this->line("  [DRY RUN] Would sync profile: {$profile->name}");
                $this->line("  Next scheduled: " . ($profile->next_sync_at ? $profile->next_sync_at->format('Y-m-d H:i:s T') : 'Not scheduled'));
                continue;
            }

            try {
                $this->syncProfile($profile, $force);
                $syncCount++;
                $this->info("  ✓ Sync completed successfully");
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("  ✗ Sync failed: " . $e->getMessage());
                
                Log::error('Import sync failed', [
                    'profile_id' => $profile->id,
                    'profile_name' => $profile->name,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        $this->line('');
        $this->info("Sync completed: {$syncCount} successful, {$errorCount} failed");
        
        return $errorCount > 0 ? 1 : 0;
    }

    /**
     * Sync a single import profile
     */
    private function syncProfile(ImportProfile $profile, bool $force = false): void
    {
        // Check if sync is due (unless forced)
        if (!$force && $profile->next_sync_at && $profile->next_sync_at->isFuture()) {
            $this->line("  Skipping: Next sync not due until {$profile->next_sync_at->format('Y-m-d H:i:s')}");
            return;
        }

        $startTime = microtime(true);
        
        // Update last sync time
        $profile->last_sync_at = now();
        $profile->next_sync_at = $this->calculateNextSyncTime($profile);
        
        // Execute the import
        $importService = app(ImportService::class);
        
        $syncOptions = $profile->sync_options ?? [];
        $importOptions = [
            'selected_tables' => ['accounts', 'users', 'tickets'], // Default tables
            'import_filters' => $syncOptions['import_filters'] ?? [],
            'overwrite_existing' => $syncOptions['update_existing'] ?? $profile->shouldUpdateDuplicates(),
            'batch_size' => $syncOptions['batch_size'] ?? 100,
            'max_records' => $syncOptions['max_records_per_run'] ?? null,
        ];

        // Create import job
        $importJob = $importService->createImportJob($profile, $importOptions);
        
        // Execute the import
        $importService->executeImport($importJob);
        
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime), 2);
        
        // Update sync statistics
        $syncStats = $profile->sync_stats ?? [];
        $syncStats['last_sync'] = [
            'timestamp' => now()->toISOString(),
            'duration_seconds' => $duration,
            'job_id' => $importJob->id,
            'records_processed' => $importJob->records_processed ?? 0,
            'records_imported' => $importJob->records_imported ?? 0,
            'records_failed' => $importJob->records_failed ?? 0,
        ];
        
        // Keep last 10 sync results
        if (!isset($syncStats['history'])) {
            $syncStats['history'] = [];
        }
        array_unshift($syncStats['history'], $syncStats['last_sync']);
        $syncStats['history'] = array_slice($syncStats['history'], 0, 10);
        
        $profile->sync_stats = $syncStats;
        $profile->save();
        
        $this->line("  Duration: {$duration}s");
        $this->line("  Records processed: {$importJob->records_processed}");
        $this->line("  Records imported: {$importJob->records_imported}");
        if ($importJob->records_failed > 0) {
            $this->line("  Records failed: {$importJob->records_failed}");
        }
    }

    /**
     * Calculate the next sync time based on profile configuration
     */
    private function calculateNextSyncTime(ImportProfile $profile): Carbon
    {
        $timezone = $profile->sync_timezone ?? 'UTC';
        $syncTime = $profile->sync_time ?? '02:00';
        
        $now = now()->setTimezone($timezone);
        
        switch ($profile->sync_frequency) {
            case 'hourly':
                return $now->addHour();
                
            case 'daily':
                $next = $now->copy()->addDay();
                [$hour, $minute] = explode(':', $syncTime);
                return $next->setTime((int)$hour, (int)$minute, 0);
                
            case 'weekly':
                $next = $now->copy()->addWeek()->startOfWeek();
                [$hour, $minute] = explode(':', $syncTime);
                return $next->setTime((int)$hour, (int)$minute, 0);
                
            case 'monthly':
                $next = $now->copy()->addMonth()->startOfMonth();
                [$hour, $minute] = explode(':', $syncTime);
                return $next->setTime((int)$hour, (int)$minute, 0);
                
            case 'custom':
                if ($profile->sync_cron_expression) {
                    // This would require a cron expression parser library
                    // For now, fall back to daily
                    $this->warn("Custom cron expressions not fully implemented, falling back to daily");
                }
                return $now->copy()->addDay();
                
            default:
                return $now->copy()->addDay();
        }
    }
}
