<?php

namespace App\Jobs;

use App\Events\ImportJobStatusChanged;
use App\Models\ImportJob;
use App\Models\ImportProfile;
use App\Services\FreescoutImportService;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessFreescoutImportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 3600; // 1 hour timeout
    public $tries = 1; // Don't retry import jobs automatically
    
    protected ImportJob $importJob;
    protected ImportProfile $profile;
    protected array $config;

    /**
     * Create a new job instance.
     */
    public function __construct(ImportJob $importJob, ImportProfile $profile, array $config)
    {
        $this->importJob = $importJob;
        $this->profile = $profile;
        $this->config = $config;
        
        // Set the queue name for import jobs
        $this->onQueue('import');
    }

    /**
     * Execute the job.
     */
    public function handle(FreescoutImportService $freescoutImportService): void
    {
        Log::info('Starting async FreeScout import job', [
            'job_id' => $this->importJob->id,
            'profile_id' => $this->profile->id
        ]);

        try {
            // Mark job as started
            $this->importJob->markAsStarted();
            $this->importJob->updateProgress(0, 'Starting FreeScout import...');

            // Process the import (transactions handled internally by the service)
            $freescoutImportService->processFreescoutImport($this->importJob, $this->profile, $this->config);

            // Mark job as completed
            $this->importJob->markAsCompleted();
            
            // Comprehensive success logging
            Log::info('Async FreeScout import completed successfully', [
                'job_id' => $this->importJob->id,
                'profile_id' => $this->profile->id,
                'profile_name' => $this->profile->name,
                'records_imported' => $this->importJob->records_imported,
                'records_processed' => $this->importJob->records_processed,
                'duration_seconds' => $this->importJob->started_at ? $this->importJob->started_at->diffInSeconds(now()) : null,
                'config_summary' => [
                    'account_strategy' => $this->config['account_strategy'],
                    'agent_access' => $this->config['agent_access'],
                    'billing_rate_strategy' => $this->config['billing_rate_strategy'] ?? 'auto_select',
                    'sync_strategy' => $this->config['sync_strategy'],
                    'duplicate_detection' => $this->config['duplicate_detection']
                ]
            ]);

            // Broadcast completion event
            event(new ImportJobStatusChanged($this->importJob, 'completed'));

        } catch (Exception $e) {
            // Ensure the import job is marked as failed even if there are issues
            try {
                $this->importJob->markAsFailed($e->getMessage());
                
                // Broadcast failure event
                event(new ImportJobStatusChanged($this->importJob, 'failed'));
            } catch (Exception $statusException) {
                // If we can't update the job status, log the error but continue
                Log::error('Failed to update import job status during error handling', [
                    'job_id' => $this->importJob->id,
                    'original_error' => $e->getMessage(),
                    'status_error' => $statusException->getMessage()
                ]);
            }
            
            // Comprehensive error logging
            Log::error('Async FreeScout import failed', [
                'job_id' => $this->importJob->id,
                'profile_id' => $this->profile->id,
                'profile_name' => $this->profile->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Re-throw the exception to mark the job as failed in the queue
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Exception $exception): void
    {
        Log::error('FreeScout import job failed permanently', [
            'job_id' => $this->importJob->id,
            'profile_id' => $this->profile->id,
            'error' => $exception->getMessage()
        ]);

        // Ensure the import job is marked as failed (with additional protection)
        try {
            $this->importJob->markAsFailed($exception->getMessage());
            
            // Broadcast failure event
            event(new ImportJobStatusChanged($this->importJob, 'failed'));
        } catch (Exception $e) {
            // Last resort - force update the status directly
            Log::error('Failed to mark import job as failed in failed() method', [
                'job_id' => $this->importJob->id,
                'failed_error' => $e->getMessage()
            ]);
            
            // Direct database update as fallback
            DB::table('import_jobs')
                ->where('id', $this->importJob->id)
                ->update([
                    'status' => 'failed',
                    'completed_at' => now(),
                    'errors' => json_encode([$exception->getMessage(), 'Status update failed, forced via direct DB update'])
                ]);
        }
    }
}