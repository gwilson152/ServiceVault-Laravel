<?php

namespace App\Jobs;

use App\Services\EmailRetrievalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEmailPostActions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The cache key for post-processing data
     */
    public string $cacheKey;

    /**
     * Create a new job instance
     */
    public function __construct(string $cacheKey)
    {
        $this->cacheKey = $cacheKey;
        
        // Set job properties
        $this->onQueue('email-processing');
        $this->tries = 3;
        $this->timeout = 300; // 5 minutes
    }

    /**
     * Execute the job
     */
    public function handle(EmailRetrievalService $emailService): void
    {
        Log::info('Starting email post-processing job', [
            'cache_key' => $this->cacheKey,
            'attempt' => $this->attempts(),
        ]);

        try {
            $result = $emailService->executePostProcessing($this->cacheKey);
            
            if ($result['success']) {
                Log::info('Email post-processing completed successfully', [
                    'cache_key' => $this->cacheKey,
                    'results' => $result,
                ]);
            } else {
                Log::error('Email post-processing failed', [
                    'cache_key' => $this->cacheKey,
                    'error' => $result['error'] ?? 'Unknown error',
                    'results' => $result['results'] ?? [],
                ]);
                
                // Mark job as failed
                $this->fail($result['error'] ?? 'Unknown error');
            }
            
        } catch (\Exception $e) {
            Log::error('Email post-processing job exception', [
                'cache_key' => $this->cacheKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'attempt' => $this->attempts(),
            ]);
            
            // Re-throw to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Email post-processing job failed permanently', [
            'cache_key' => $this->cacheKey,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
        
        // Could send notification to admin about persistent failure
        // For now, just log it
    }

    /**
     * Get the tags that should be assigned to the job
     */
    public function tags(): array
    {
        return ['email-processing', 'post-actions', $this->cacheKey];
    }
}