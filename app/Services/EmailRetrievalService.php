<?php

namespace App\Services;

use App\Jobs\ProcessIncomingEmail;
use App\Models\EmailSystemConfig;
use App\Models\EmailProcessingLog;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
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
                                
                                // Schedule post-processing for successfully processed email
                                $this->schedulePostProcessing($rawEmail, $config);
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
        
        switch ($config->incoming_provider) {
            case 'outlook':
            case 'm365':
                return $this->fetchFromM365($config, $limit);
            
            case 'imap':
                return $this->fetchFromIMAP($config, $limit);
            
            case 'gmail':
                return $this->fetchFromGmail($config, $limit);
            
            default:
                throw new \Exception("Unsupported email provider: {$config->incoming_provider}");
        }
    }

    /**
     * Fetch emails from Microsoft 365
     */
    private function fetchFromM365(EmailSystemConfig $config, int $limit): array
    {
        $settings = $config->incoming_settings;
        $tenantId = $settings['tenant_id'] ?? null;
        $clientId = $settings['client_id'] ?? null;
        $clientSecret = $settings['client_secret'] ?? null;
        $mailbox = $settings['mailbox'] ?? null;
        $folderId = $settings['folder_id'] ?? null;
        
        if (!$tenantId || !$clientId || !$clientSecret || !$mailbox) {
            throw new \Exception('Microsoft 365 configuration is incomplete');
        }
        
        Log::info('Fetching emails from Microsoft 365', [
            'mailbox' => $mailbox,
            'folder_id' => $folderId,
            'limit' => $limit,
        ]);
        
        try {
            // Step 1: Get OAuth2 token
            $tokenResponse = Http::asForm()->post("https://login.microsoftonline.com/{$tenantId}/oauth2/v2.0/token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'https://graph.microsoft.com/.default',
                'grant_type' => 'client_credentials'
            ]);
            
            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to get M365 access token: ' . $tokenResponse->body());
            }
            
            $token = $tokenResponse->json()['access_token'];
            
            // Step 2: Get emails from specified folder
            $messagesUrl = $folderId 
                ? "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders/{$folderId}/messages"
                : "https://graph.microsoft.com/v1.0/users/{$mailbox}/messages";
            
            // Determine email filter based on retrieval mode
            $retrievalMode = $config->email_retrieval_mode ?? 'unread_only';
            $queryParams = [
                '$top' => $limit,
                '$orderby' => 'receivedDateTime desc',
                '$select' => 'id,subject,from,toRecipients,ccRecipients,bccRecipients,receivedDateTime,body,internetMessageId,conversationId,isRead,hasAttachments,attachments'
            ];

            // Apply filter based on retrieval mode
            switch ($retrievalMode) {
                case 'all':
                    // No filter - get all emails
                    break;
                case 'recent':
                    // Get emails from last 7 days
                    $since = now()->subDays(7)->toIso8601String();
                    $queryParams['$filter'] = "receivedDateTime ge {$since}";
                    break;
                case 'unread_only':
                default:
                    // Default behavior - only unread emails
                    $queryParams['$filter'] = 'isRead eq false';
                    break;
            }

            $messagesResponse = Http::withToken($token)->get($messagesUrl, $queryParams);
            
            if (!$messagesResponse->successful()) {
                throw new \Exception('Failed to get M365 messages: ' . $messagesResponse->body());
            }
            
            $messages = $messagesResponse->json()['value'] ?? [];
            
            Log::info('Retrieved messages from M365', [
                'count' => count($messages),
                'mailbox' => $mailbox,
            ]);
            
            // Step 3: Convert messages to raw email format and store message info for post-processing
            $rawEmails = [];
            $messageInfos = [];
            
            foreach ($messages as $message) {
                try {
                    $rawEmail = $this->convertM365MessageToRawEmail($message, $token, $mailbox);
                    $rawEmails[] = $rawEmail;
                    
                    // Store message info for potential post-processing
                    $messageInfos[] = [
                        'id' => $message['id'],
                        'raw_email' => $rawEmail,
                        'is_read' => $message['isRead'] ?? false,
                    ];
                } catch (\Exception $e) {
                    Log::warning('Failed to convert M365 message to raw email', [
                        'message_id' => $message['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Store message info for post-processing
            $this->storeMessageInfoForPostProcessing($config, $token, $mailbox, $messageInfos);
            
            return $rawEmails;
            
        } catch (\Exception $e) {
            Log::error('M365 email retrieval failed', [
                'mailbox' => $mailbox,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
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

    /**
     * Convert Microsoft 365 Graph API message to raw email format
     */
    private function convertM365MessageToRawEmail(array $message, string $token, string $mailbox): string
    {
        // Build RFC 2822 compliant email headers and body
        $headers = [];
        
        // Add basic headers
        $headers[] = 'Message-ID: ' . ($message['internetMessageId'] ?? '<' . $message['id'] . '@graph.microsoft.com>');
        $headers[] = 'Date: ' . date('r', strtotime($message['receivedDateTime']));
        $headers[] = 'Subject: ' . ($message['subject'] ?? '(No Subject)');
        
        // From header
        if (!empty($message['from']['emailAddress'])) {
            $from = $message['from']['emailAddress'];
            $fromHeader = $from['name'] ?? $from['address'];
            if ($from['name'] && $from['name'] !== $from['address']) {
                $fromHeader = '"' . $from['name'] . '" <' . $from['address'] . '>';
            } else {
                $fromHeader = $from['address'];
            }
            $headers[] = 'From: ' . $fromHeader;
        }
        
        // To header
        if (!empty($message['toRecipients'])) {
            $toList = [];
            foreach ($message['toRecipients'] as $recipient) {
                $email = $recipient['emailAddress'];
                if ($email['name'] && $email['name'] !== $email['address']) {
                    $toList[] = '"' . $email['name'] . '" <' . $email['address'] . '>';
                } else {
                    $toList[] = $email['address'];
                }
            }
            $headers[] = 'To: ' . implode(', ', $toList);
        }
        
        // CC header
        if (!empty($message['ccRecipients'])) {
            $ccList = [];
            foreach ($message['ccRecipients'] as $recipient) {
                $email = $recipient['emailAddress'];
                if ($email['name'] && $email['name'] !== $email['address']) {
                    $ccList[] = '"' . $email['name'] . '" <' . $email['address'] . '>';
                } else {
                    $ccList[] = $email['address'];
                }
            }
            $headers[] = 'Cc: ' . implode(', ', $ccList);
        }
        
        // BCC header (if available)
        if (!empty($message['bccRecipients'])) {
            $bccList = [];
            foreach ($message['bccRecipients'] as $recipient) {
                $email = $recipient['emailAddress'];
                if ($email['name'] && $email['name'] !== $email['address']) {
                    $bccList[] = '"' . $email['name'] . '" <' . $email['address'] . '>';
                } else {
                    $bccList[] = $email['address'];
                }
            }
            $headers[] = 'Bcc: ' . implode(', ', $bccList);
        }
        
        // Content type
        $contentType = 'text/plain';
        if ($message['body']['contentType'] === 'html') {
            $contentType = 'text/html; charset="utf-8"';
        }
        $headers[] = 'Content-Type: ' . $contentType;
        $headers[] = 'Content-Transfer-Encoding: 8bit';
        
        // Build the raw email
        $rawEmail = implode("\r\n", $headers) . "\r\n\r\n";
        
        // Add body content
        $body = $message['body']['content'] ?? '';
        $rawEmail .= $body;
        
        // Handle attachments if present
        if (!empty($message['hasAttachments']) && $message['hasAttachments']) {
            try {
                $attachments = $this->getM365MessageAttachments($message['id'], $token, $mailbox);
                if (!empty($attachments)) {
                    // Convert to multipart message if attachments exist
                    $rawEmail = $this->convertToMultipartWithAttachments($rawEmail, $attachments);
                }
            } catch (\Exception $e) {
                Log::warning('Failed to retrieve M365 attachments', [
                    'message_id' => $message['id'],
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $rawEmail;
    }

    /**
     * Get attachments for a Microsoft 365 message
     */
    private function getM365MessageAttachments(string $messageId, string $token, string $mailbox): array
    {
        $attachmentsResponse = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/users/{$mailbox}/messages/{$messageId}/attachments");
        
        if (!$attachmentsResponse->successful()) {
            Log::warning('Failed to get M365 attachments', [
                'message_id' => $messageId,
                'response' => $attachmentsResponse->body(),
            ]);
            return [];
        }
        
        return $attachmentsResponse->json()['value'] ?? [];
    }

    /**
     * Convert simple email to multipart with attachments
     */
    private function convertToMultipartWithAttachments(string $rawEmail, array $attachments): string
    {
        $boundary = 'boundary_' . uniqid();
        
        // Split headers and body
        $parts = explode("\r\n\r\n", $rawEmail, 2);
        $headers = $parts[0];
        $body = $parts[1] ?? '';
        
        // Update content-type header to multipart
        $headers = preg_replace('/^Content-Type:.*/m', "Content-Type: multipart/mixed; boundary=\"{$boundary}\"", $headers);
        
        // Build multipart body
        $multipartBody = "--{$boundary}\r\n";
        $multipartBody .= "Content-Type: text/plain; charset=\"utf-8\"\r\n";
        $multipartBody .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $multipartBody .= $body . "\r\n";
        
        // Add attachments
        foreach ($attachments as $attachment) {
            if ($attachment['@odata.type'] === '#microsoft.graph.fileAttachment') {
                $multipartBody .= "--{$boundary}\r\n";
                $multipartBody .= "Content-Type: {$attachment['contentType']}\r\n";
                $multipartBody .= "Content-Disposition: attachment; filename=\"{$attachment['name']}\"\r\n";
                $multipartBody .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $multipartBody .= $attachment['contentBytes'] . "\r\n";
            }
        }
        
        $multipartBody .= "--{$boundary}--\r\n";
        
        return $headers . "\r\n\r\n" . $multipartBody;
    }

    /**
     * Store message information for post-processing
     */
    private function storeMessageInfoForPostProcessing(EmailSystemConfig $config, string $token, string $mailbox, array $messageInfos): void
    {
        if (empty($messageInfos) || $config->post_processing_action === 'none') {
            return;
        }

        // Store message info in cache for post-processing after successful email processing
        $cacheKey = "email_post_processing:" . md5($mailbox . time());
        
        $postProcessingData = [
            'config' => [
                'action' => $config->post_processing_action,
                'move_to_folder_id' => $config->move_to_folder_id,
                'move_to_folder_name' => $config->move_to_folder_name,
            ],
            'provider' => 'm365',
            'token' => $token,
            'mailbox' => $mailbox,
            'messages' => $messageInfos,
            'created_at' => now(),
        ];

        Cache::put($cacheKey, $postProcessingData, now()->addHours(6));
        
        Log::info('Stored message info for post-processing', [
            'cache_key' => $cacheKey,
            'action' => $config->post_processing_action,
            'message_count' => count($messageInfos),
        ]);

        // Schedule post-processing to run after email processing completes
        $this->schedulePostProcessing($cacheKey, 5); // 5 minute delay to allow processing
    }

    /**
     * Schedule post-processing of emails
     */
    private function schedulePostProcessing(string $cacheKey, int $delayMinutes = 5): void
    {
        // Use Laravel's delayed job scheduling
        \App\Jobs\ProcessEmailPostActions::dispatch($cacheKey)
            ->delay(now()->addMinutes($delayMinutes));
            
        Log::info('Scheduled post-processing job', [
            'cache_key' => $cacheKey,
            'delay_minutes' => $delayMinutes,
        ]);
    }

    /**
     * Execute post-processing actions on emails (called by background job)
     */
    public function executePostProcessing(string $cacheKey): array
    {
        $data = Cache::get($cacheKey);
        
        if (!$data) {
            Log::warning('Post-processing data not found', ['cache_key' => $cacheKey]);
            return ['success' => false, 'error' => 'Post-processing data not found'];
        }

        $config = $data['config'];
        $provider = $data['provider'];
        $results = ['processed' => 0, 'errors' => 0, 'action' => $config['action']];

        try {
            switch ($provider) {
                case 'm365':
                    $results = $this->executeM365PostProcessing($data);
                    break;
                case 'imap':
                    $results = $this->executeImapPostProcessing($data);
                    break;
                default:
                    Log::warning('Unsupported provider for post-processing', ['provider' => $provider]);
                    return ['success' => false, 'error' => 'Unsupported provider'];
            }

            // Clean up cache after processing
            Cache::forget($cacheKey);
            
            Log::info('Post-processing completed', [
                'cache_key' => $cacheKey,
                'results' => $results,
            ]);

            return array_merge(['success' => true], $results);

        } catch (\Exception $e) {
            Log::error('Post-processing failed', [
                'cache_key' => $cacheKey,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return [
                'success' => false, 
                'error' => $e->getMessage(),
                'results' => $results
            ];
        }
    }

    /**
     * Execute M365-specific post-processing actions
     */
    private function executeM365PostProcessing(array $data): array
    {
        $config = $data['config'];
        $token = $data['token'];
        $mailbox = $data['mailbox'];
        $messages = $data['messages'];
        
        $results = ['processed' => 0, 'errors' => 0, 'action' => $config['action']];

        foreach ($messages as $messageInfo) {
            try {
                switch ($config['action']) {
                    case 'mark_read':
                        $this->markM365MessageAsRead($messageInfo['id'], $token, $mailbox);
                        break;
                    case 'move_folder':
                        $this->moveM365MessageToFolder($messageInfo['id'], $token, $mailbox, $config);
                        break;
                    case 'delete':
                        $this->deleteM365Message($messageInfo['id'], $token, $mailbox);
                        break;
                }
                $results['processed']++;
            } catch (\Exception $e) {
                Log::error('M365 post-processing action failed', [
                    'message_id' => $messageInfo['id'],
                    'action' => $config['action'],
                    'error' => $e->getMessage(),
                ]);
                $results['errors']++;
            }
        }

        return $results;
    }

    /**
     * Execute IMAP-specific post-processing actions
     */
    private function executeImapPostProcessing(array $data): array
    {
        // Placeholder for IMAP post-processing
        Log::info('IMAP post-processing not yet implemented');
        return ['processed' => 0, 'errors' => 0, 'action' => $data['config']['action']];
    }

    /**
     * Mark M365 message as read
     */
    private function markM365MessageAsRead(string $messageId, string $token, string $mailbox): void
    {
        $response = Http::withToken($token)
            ->patch("https://graph.microsoft.com/v1.0/users/{$mailbox}/messages/{$messageId}", [
                'isRead' => true
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to mark message as read: " . $response->body());
        }

        Log::debug('Marked M365 message as read', [
            'message_id' => $messageId,
            'mailbox' => $mailbox,
        ]);
    }

    /**
     * Move M365 message to folder
     */
    private function moveM365MessageToFolder(string $messageId, string $token, string $mailbox, array $config): void
    {
        $folderId = $config['move_to_folder_id'];
        $folderName = $config['move_to_folder_name'];

        // If no folder ID, try to find by name
        if (!$folderId && $folderName) {
            $folderId = $this->findM365FolderByName($folderName, $token, $mailbox);
        }

        if (!$folderId) {
            throw new \Exception("Target folder not found: {$folderName}");
        }

        $response = Http::withToken($token)
            ->post("https://graph.microsoft.com/v1.0/users/{$mailbox}/messages/{$messageId}/move", [
                'destinationId' => $folderId
            ]);

        if (!$response->successful()) {
            throw new \Exception("Failed to move message to folder: " . $response->body());
        }

        Log::debug('Moved M365 message to folder', [
            'message_id' => $messageId,
            'folder_id' => $folderId,
            'folder_name' => $folderName,
        ]);
    }

    /**
     * Delete M365 message
     */
    private function deleteM365Message(string $messageId, string $token, string $mailbox): void
    {
        $response = Http::withToken($token)
            ->delete("https://graph.microsoft.com/v1.0/users/{$mailbox}/messages/{$messageId}");

        if (!$response->successful()) {
            throw new \Exception("Failed to delete message: " . $response->body());
        }

        Log::debug('Deleted M365 message', [
            'message_id' => $messageId,
            'mailbox' => $mailbox,
        ]);
    }

    /**
     * Find M365 folder by name
     */
    private function findM365FolderByName(string $folderName, string $token, string $mailbox): ?string
    {
        $response = Http::withToken($token)
            ->get("https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders", [
                '$filter' => "displayName eq '{$folderName}'"
            ]);

        if (!$response->successful()) {
            Log::warning('Failed to search for M365 folder', [
                'folder_name' => $folderName,
                'response' => $response->body(),
            ]);
            return null;
        }

        $folders = $response->json()['value'] ?? [];
        return $folders[0]['id'] ?? null;
    }
}