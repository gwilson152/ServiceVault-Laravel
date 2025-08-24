<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessIncomingEmail implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 300; // 5 minutes
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    private array $emailData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $emailData)
    {
        $this->emailData = $emailData;
        
        // Set queue based on priority or account
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        Log::info('Processing incoming email', [
            'email_id' => $this->emailData['email_id'] ?? 'unknown',
            'from' => $this->emailData['from'] ?? 'unknown',
            'subject' => $this->emailData['subject'] ?? 'no subject',
        ]);

        try {
            $processingLog = $emailService->processIncomingEmail($this->emailData);

            if ($processingLog->wasSuccessful()) {
                Log::info('Successfully processed incoming email', [
                    'email_id' => $processingLog->email_id,
                    'ticket_id' => $processingLog->ticket_id,
                    'actions_taken' => $processingLog->actions_taken,
                ]);
            } else {
                Log::warning('Email processing completed with issues', [
                    'email_id' => $processingLog->email_id,
                    'status' => $processingLog->status,
                    'error_message' => $processingLog->error_message,
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Failed to process incoming email', [
                'email_id' => $this->emailData['email_id'] ?? 'unknown',
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'attempt' => $this->attempts(),
            ]);

            // Re-throw to trigger retry mechanism
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessIncomingEmail job failed permanently', [
            'email_id' => $this->emailData['email_id'] ?? 'unknown',
            'from' => $this->emailData['from'] ?? 'unknown',
            'subject' => $this->emailData['subject'] ?? 'no subject',
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Send notification to admin about failed email processing
        // This could be implemented as a separate notification system
    }

    /**
     * Determine which queue to use based on email priority
     */
    private function determineQueue(): string
    {
        $subject = strtolower($this->emailData['subject'] ?? '');
        
        // High priority keywords
        $highPriorityKeywords = ['urgent', 'emergency', 'critical', 'down', 'outage'];
        
        foreach ($highPriorityKeywords as $keyword) {
            if (strpos($subject, $keyword) !== false) {
                return 'email-high-priority';
            }
        }

        // Check for priority command in subject
        if (preg_match('/priority:(high|urgent)/i', $subject)) {
            return 'email-high-priority';
        }

        return 'email-incoming';
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'email',
            'incoming',
            'email_id:' . ($this->emailData['email_id'] ?? 'unknown'),
            'from:' . ($this->emailData['from'] ?? 'unknown'),
        ];
    }
}
