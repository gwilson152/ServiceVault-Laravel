<?php

namespace App\Services;

use App\Jobs\ProcessIncomingEmail;
use App\Models\EmailSystemConfig;
use App\Models\EmailProcessingLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailRetrievalService
{
    private EmailParser $parser;
    private IncomingEmailHandler $handler;

    public function __construct(EmailParser $parser, IncomingEmailHandler $handler)
    {
        $this->parser = $parser;
        $this->handler = $handler;
    }

    /**
     * Retrieve emails from configured email source
     */
    public function retrieveEmails(array $options = []): array
    {
        $mode = $options['mode'] ?? 'process'; // 'test' or 'process'
        $limit = min((int) ($options['limit'] ?? 10), 50);
        
        try {
            // Get email system configuration
            $config = EmailSystemConfig::first();
            if (!$config || !$config->system_active || !$config->incoming_enabled) {
                throw new \Exception('Email system is not configured or disabled');
            }

            Log::info('Starting email retrieval', [
                'mode' => $mode,
                'limit' => $limit,
                'provider' => $config->email_provider,
            ]);

            // Connect to email server and fetch emails
            $rawEmails = $this->fetchEmailsFromServer($config, $limit);

            $results = [
                'success' => true,
                'mode' => $mode,
                'test_mode' => $mode === 'test',
                'emails_retrieved' => count($rawEmails),
                'emails_processed' => 0,
                'duplicates_skipped' => 0,
                'processing_errors' => 0,
                'details' => [
                    'new_emails' => 0,
                    'duplicates_skipped' => 0,
                    'processing_errors' => 0,
                    'error_details' => [],
                ],
            ];

            foreach ($rawEmails as $rawEmail) {
                try {
                    if ($mode === 'test') {
                        // Test mode: parse and log but don't process
                        $parsed = $this->parser->parse($rawEmail);
                        $this->logTestEmail($parsed);
                        $results['details']['new_emails']++;
                    } else {
                        // Process mode: handle through normal pipeline
                        $result = $this->handler->handleIncomingEmail($rawEmail);
                        
                        if ($result['success']) {
                            if ($result['duplicate'] ?? false) {
                                $results['duplicates_skipped']++;
                                $results['details']['duplicates_skipped']++;
                            } else {
                                $results['emails_processed']++;
                                $results['details']['new_emails']++;
                            }
                        } else {
                            $results['processing_errors']++;
                            $results['details']['processing_errors']++;
                            $results['details']['error_details'][] = $result['error'] ?? 'Unknown error';
                        }
                    }
                } catch (\Exception $e) {
                    $results['processing_errors']++;
                    $results['details']['processing_errors']++;
                    $results['details']['error_details'][] = $e->getMessage();
                    
                    Log::error('Error processing retrieved email', [
                        'error' => $e->getMessage(),
                        'mode' => $mode,
                    ]);
                }
            }

            $results['message'] = $this->buildResultMessage($results);

            Log::info('Email retrieval completed', $results);
            
            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to retrieve emails', [
                'error' => $e->getMessage(),
                'mode' => $mode,
                'limit' => $limit,
            ]);

            return [
                'success' => false,
                'error' => 'Email retrieval failed',
                'message' => $e->getMessage(),
                'mode' => $mode,
            ];
        }
    }

    /**
     * Reprocess existing emails using the same pipeline
     */
    public function reprocessEmails(array $options = []): array
    {
        $mode = $options['mode'] ?? 'process';
        $target = $options['target'] ?? 'failed'; // 'failed', 'pending', 'all'

        try {
            // Get emails to reprocess
            $emails = $this->getEmailsForReprocessing($target);

            $results = [
                'success' => true,
                'operation' => 'reprocess',
                'mode' => $mode,
                'target' => $target,
                'emails_found' => count($emails),
                'emails_processed' => 0,
                'processing_errors' => 0,
                'details' => [
                    'tickets_created' => 0,
                    'comments_added' => 0,
                    'errors' => [],
                ],
            ];

            foreach ($emails as $emailLog) {
                try {
                    if ($mode === 'test') {
                        // Test mode: just validate the email data
                        $this->validateEmailForReprocessing($emailLog);
                        $results['emails_processed']++;
                    } else {
                        // Process mode: reprocess through normal pipeline
                        $emailData = $this->prepareEmailDataForReprocessing($emailLog);
                        
                        // Update log status
                        $emailLog->update([
                            'status' => 'pending',
                            'retry_count' => $emailLog->retry_count + 1,
                            'next_retry_at' => null,
                            'error_message' => null,
                            'error_details' => null,
                        ]);

                        // Dispatch to queue using the same mechanism as scheduled jobs
                        ProcessIncomingEmail::dispatch($emailData);
                        
                        $results['emails_processed']++;
                        
                        // Track what type of processing was done
                        if ($emailLog->ticket_id) {
                            $results['details']['comments_added']++;
                        } else {
                            $results['details']['tickets_created']++;
                        }
                    }
                } catch (\Exception $e) {
                    $results['processing_errors']++;
                    $results['details']['errors'][] = [
                        'email_id' => $emailLog->email_id,
                        'error' => $e->getMessage(),
                    ];
                    
                    Log::error('Error reprocessing email', [
                        'email_id' => $emailLog->email_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $results['message'] = $this->buildReprocessResultMessage($results);
            
            Log::info('Email reprocessing completed', $results);
            
            return $results;

        } catch (\Exception $e) {
            Log::error('Failed to reprocess emails', [
                'error' => $e->getMessage(),
                'target' => $target,
                'mode' => $mode,
            ]);

            return [
                'success' => false,
                'error' => 'Email reprocessing failed',
                'message' => $e->getMessage(),
                'operation' => 'reprocess',
                'mode' => $mode,
                'target' => $target,
            ];
        }
    }

    /**
     * Fetch emails from configured email server
     */
    private function fetchEmailsFromServer(EmailSystemConfig $config, int $limit): array
    {
        // This would integrate with the actual email server connection
        // For now, return empty array as placeholder
        
        switch ($config->email_provider) {
            case 'outlook':
            case 'm365':
                return $this->fetchFromM365($config, $limit);
            
            case 'imap':
                return $this->fetchFromIMAP($config, $limit);
            
            case 'gmail':
                return $this->fetchFromGmail($config, $limit);
            
            default:
                throw new \Exception("Unsupported email provider: {$config->email_provider}");
        }
    }

    /**
     * Fetch emails from Microsoft 365
     */
    private function fetchFromM365(EmailSystemConfig $config, int $limit): array
    {
        // Placeholder implementation
        // In real implementation, this would:
        // 1. Authenticate with M365 using stored credentials
        // 2. Connect to specified folder
        // 3. Fetch unread emails up to limit
        // 4. Mark as read if configured
        // 5. Return array of raw email strings
        
        Log::info('Fetching emails from Microsoft 365', [
            'folder' => $config->incoming_folder ?? 'INBOX',
            'limit' => $limit,
        ]);
        
        return []; // Placeholder - no emails retrieved
    }

    /**
     * Fetch emails from IMAP server
     */
    private function fetchFromIMAP(EmailSystemConfig $config, int $limit): array
    {
        // Placeholder implementation
        // In real implementation, this would:
        // 1. Connect to IMAP server using stored credentials
        // 2. Select folder
        // 3. Search for unread emails
        // 4. Fetch email content up to limit
        // 5. Return array of raw email strings
        
        Log::info('Fetching emails from IMAP', [
            'host' => $config->incoming_host,
            'port' => $config->incoming_port,
            'folder' => $config->incoming_folder ?? 'INBOX',
            'limit' => $limit,
        ]);
        
        return []; // Placeholder - no emails retrieved
    }

    /**
     * Fetch emails from Gmail API
     */
    private function fetchFromGmail(EmailSystemConfig $config, int $limit): array
    {
        // Placeholder implementation for Gmail API
        Log::info('Fetching emails from Gmail', [
            'limit' => $limit,
        ]);
        
        return []; // Placeholder - no emails retrieved
    }

    /**
     * Get emails that need reprocessing
     */
    private function getEmailsForReprocessing(string $target): array
    {
        $query = EmailProcessingLog::query();

        switch ($target) {
            case 'failed':
                $query->where('status', 'failed');
                break;
            case 'pending':
                $query->where('status', 'pending');
                break;
            case 'retry':
                $query->where('status', 'retry');
                break;
            case 'all':
                $query->whereIn('status', ['failed', 'pending', 'retry']);
                break;
            default:
                throw new \Exception("Invalid target: {$target}");
        }

        return $query->orderBy('created_at', 'desc')
                    ->limit(50)
                    ->get()
                    ->toArray();
    }

    /**
     * Prepare email data from processing log for reprocessing
     */
    private function prepareEmailDataForReprocessing(EmailProcessingLog $emailLog): array
    {
        return [
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
    }

    /**
     * Validate email data for reprocessing
     */
    private function validateEmailForReprocessing(EmailProcessingLog $emailLog): void
    {
        if (empty($emailLog->from_address)) {
            throw new \Exception('Email missing from address');
        }
        
        if (empty($emailLog->subject) && empty($emailLog->parsed_body_text)) {
            throw new \Exception('Email missing subject and body content');
        }
        
        // Additional validation as needed
    }

    /**
     * Log email details in test mode
     */
    private function logTestEmail(array $parsedEmail): void
    {
        Log::info('Test mode: Email parsed successfully', [
            'from' => $parsedEmail['from'] ?? 'unknown',
            'subject' => $parsedEmail['subject'] ?? 'no subject',
            'message_id' => $parsedEmail['message_id'] ?? 'no message id',
            'body_length' => strlen($parsedEmail['body_text'] ?? ''),
            'attachments' => count($parsedEmail['attachments'] ?? []),
        ]);
    }

    /**
     * Build result message for email retrieval
     */
    private function buildResultMessage(array $results): string
    {
        $mode = $results['mode'];
        $retrieved = $results['emails_retrieved'];
        $processed = $results['emails_processed'];
        $duplicates = $results['duplicates_skipped'];
        $errors = $results['processing_errors'];

        if ($mode === 'test') {
            return "Email retrieval (test mode) completed: {$retrieved} emails retrieved and analyzed";
        }

        $message = "Email retrieval (process mode) completed: {$retrieved} emails retrieved";
        if ($processed > 0) $message .= ", {$processed} processed";
        if ($duplicates > 0) $message .= ", {$duplicates} duplicates skipped";
        if ($errors > 0) $message .= ", {$errors} errors occurred";
        
        return $message;
    }

    /**
     * Build result message for email reprocessing
     */
    private function buildReprocessResultMessage(array $results): string
    {
        $mode = $results['mode'];
        $target = $results['target'];
        $found = $results['emails_found'];
        $processed = $results['emails_processed'];
        $errors = $results['processing_errors'];

        if ($mode === 'test') {
            return "Email reprocessing (test mode) completed: {$found} {$target} emails validated";
        }

        $message = "Email reprocessing completed: {$found} {$target} emails found, {$processed} requeued";
        if ($errors > 0) $message .= ", {$errors} errors occurred";
        
        return $message;
    }
}