<?php

namespace App\Jobs;

use App\Services\EmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOutgoingEmail implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $maxExceptions = 3;
    public $timeout = 120; // 2 minutes
    public $backoff = [30, 120, 300]; // 30 sec, 2 min, 5 min

    private string $to;
    private string $subject;
    private string $bodyText;
    private ?string $bodyHtml;
    private ?string $accountId;
    private array $attachments;
    private array $options;

    /**
     * Create a new job instance.
     */
    public function __construct(
        string $to,
        string $subject,
        string $bodyText,
        ?string $bodyHtml = null,
        ?string $accountId = null,
        array $attachments = [],
        array $options = []
    ) {
        $this->to = $to;
        $this->subject = $subject;
        $this->bodyText = $bodyText;
        $this->bodyHtml = $bodyHtml;
        $this->accountId = $accountId;
        $this->attachments = $attachments;
        $this->options = $options;
        
        // Set queue based on priority
        $this->onQueue($this->determineQueue());
    }

    /**
     * Execute the job.
     */
    public function handle(EmailService $emailService): void
    {
        Log::info('Processing outgoing email', [
            'to' => $this->to,
            'subject' => $this->subject,
            'account_id' => $this->accountId,
        ]);

        try {
            $success = $emailService->sendEmail(
                $this->to,
                $this->subject,
                $this->bodyText,
                $this->bodyHtml,
                $this->accountId,
                $this->attachments,
                $this->options
            );

            if ($success) {
                Log::info('Successfully sent outgoing email', [
                    'to' => $this->to,
                    'subject' => $this->subject,
                    'account_id' => $this->accountId,
                ]);
            } else {
                Log::warning('Failed to send outgoing email', [
                    'to' => $this->to,
                    'subject' => $this->subject,
                    'account_id' => $this->accountId,
                ]);

                throw new \Exception('Email sending failed without specific error');
            }

        } catch (\Exception $e) {
            Log::error('Failed to send outgoing email', [
                'to' => $this->to,
                'subject' => $this->subject,
                'account_id' => $this->accountId,
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
        Log::error('ProcessOutgoingEmail job failed permanently', [
            'to' => $this->to,
            'subject' => $this->subject,
            'account_id' => $this->accountId,
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);

        // Could send notification to admin about failed outgoing email
        // Or add to a "failed emails" dashboard for manual review
    }

    /**
     * Determine which queue to use based on email priority
     */
    private function determineQueue(): string
    {
        $subject = strtolower($this->subject);
        $priority = $this->options['priority'] ?? 'normal';

        // High priority emails
        if ($priority === 'high' || $priority === 'urgent') {
            return 'email-outgoing-priority';
        }

        // Critical system notifications
        $criticalKeywords = ['password reset', 'account locked', 'security alert', 'payment failed'];
        
        foreach ($criticalKeywords as $keyword) {
            if (strpos($subject, $keyword) !== false) {
                return 'email-outgoing-priority';
            }
        }

        // Notification emails (medium priority)
        $notificationKeywords = ['ticket created', 'ticket updated', 'comment added', 'assigned'];
        
        foreach ($notificationKeywords as $keyword) {
            if (strpos($subject, $keyword) !== false) {
                return 'email-outgoing-notifications';
            }
        }

        // Default queue for regular emails
        return 'email-outgoing';
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'email',
            'outgoing',
            'to:' . $this->to,
            'account:' . ($this->accountId ?? 'global'),
        ];
    }
}
