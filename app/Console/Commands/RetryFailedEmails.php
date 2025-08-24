<?php

namespace App\Console\Commands;

use App\Jobs\ProcessIncomingEmail;
use App\Models\EmailProcessingLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RetryFailedEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'email:retry-failed 
                           {--limit=10 : Maximum number of emails to retry}
                           {--older-than=1 : Only retry emails older than X hours}
                           {--max-attempts=3 : Maximum retry attempts per email}
                           {--force : Force retry even if max attempts reached}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed email processing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        $olderThanHours = (int) $this->option('older-than');
        $maxAttempts = (int) $this->option('max-attempts');
        $force = $this->option('force');

        $this->info("Retrying failed emails...");
        $this->info("Limit: {$limit}");
        $this->info("Older than: {$olderThanHours} hours");
        $this->info("Max attempts: {$maxAttempts}");
        $this->info("Force retry: " . ($force ? 'Yes' : 'No'));

        // Get failed emails that need retry
        $query = EmailProcessingLog::needsRetry()
            ->where('created_at', '<=', now()->subHours($olderThanHours))
            ->orderBy('next_retry_at', 'asc');

        if (!$force) {
            $query->where('retry_count', '<', $maxAttempts);
        }

        $failedEmails = $query->limit($limit)->get();

        if ($failedEmails->isEmpty()) {
            $this->info('No failed emails found that need retry.');
            return 0;
        }

        $this->info("Found {$failedEmails->count()} emails to retry.");

        $retried = 0;
        $errors = 0;

        foreach ($failedEmails as $emailLog) {
            try {
                $this->line("Retrying email {$emailLog->email_id} (attempt {$emailLog->retry_count})...");

                // Prepare email data for reprocessing
                $emailData = [
                    'email_id' => $emailLog->email_id,
                    'direction' => $emailLog->direction,
                    'from' => $emailLog->from_address,
                    'to' => $emailLog->to_addresses ?? [],
                    'cc' => $emailLog->cc_addresses ?? [],
                    'bcc' => $emailLog->bcc_addresses ?? [],
                    'subject' => $emailLog->subject,
                    'message_id' => $emailLog->message_id,
                    'in_reply_to' => $emailLog->in_reply_to,
                    'references' => $emailLog->references ?? [],
                    'received_at' => $emailLog->received_at,
                    'body_text' => $emailLog->parsed_body_text,
                    'body_html' => $emailLog->parsed_body_html,
                    'attachments' => $emailLog->attachments ?? [],
                    'raw_content' => $emailLog->raw_email_content,
                ];

                // Update retry status
                $emailLog->update([
                    'status' => 'pending',
                    'next_retry_at' => null,
                ]);

                // Dispatch to queue
                ProcessIncomingEmail::dispatch($emailData);

                $retried++;
                $this->info("  ✓ Queued for retry");

            } catch (\Exception $e) {
                $errors++;
                $this->error("  ✗ Error: {$e->getMessage()}");
                
                Log::error('Failed to retry email', [
                    'email_id' => $emailLog->email_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("\n--- Retry Summary ---");
        $this->info("Emails retried: {$retried}");
        if ($errors > 0) {
            $this->warn("Errors encountered: {$errors}");
        }

        return 0;
    }
}
