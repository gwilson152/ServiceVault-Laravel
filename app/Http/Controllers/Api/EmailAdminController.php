<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\EmailSystemConfig;
use App\Models\EmailDomainMapping;
use App\Models\EmailProcessingLog;
use App\Models\Account;
use App\Models\User;
use App\Jobs\ProcessIncomingEmail;
use App\Jobs\ProcessOutgoingEmail;
use App\Services\EmailProcessingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailAdminController extends Controller
{
    /**
     * Get comprehensive email system dashboard data
     */
    public function dashboard(Request $request): JsonResponse
    {
        // Check if user can access email system administration
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access email administration');
        }

        $timeRange = $request->get('time_range', '24h');
        $startDate = $this->getStartDate($timeRange);

        try {
            $dashboardData = [
                // System health and configuration
                'system_health' => $this->getSystemHealth(),
                
                // Overview statistics
                'overview' => $this->getOverviewStats($startDate),
                
                // Processing performance
                'performance' => $this->getPerformanceStats($startDate),
                
                // Domain mapping statistics
                'domain_mappings' => $this->getDomainMappingStats(),
                
                // Queue health
                'queue_health' => $this->getQueueHealth(),
                
                // Recent activity
                'recent_activity' => $this->getRecentActivity(),
                
                // System alerts
                'alerts' => $this->getSystemAlerts($startDate),
            ];

            return response()->json([
                'success' => true,
                'data' => $dashboardData,
                'time_range' => $timeRange,
                'generated_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Email admin dashboard error', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to load dashboard data',
                'message' => 'An error occurred while loading the email administration dashboard',
            ], 500);
        }
    }

    /**
     * Get email processing logs with advanced filtering
     */
    public function getProcessingLogs(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access email processing logs');
        }

        $validator = Validator::make($request->all(), [
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'status' => 'nullable|in:pending,processing,processed,failed,retry',
            'direction' => 'nullable|in:incoming,outgoing',
            'account_id' => 'nullable|exists:accounts,id',
            'has_commands' => 'nullable|boolean',
            'command_success' => 'nullable|boolean',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'search' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = EmailProcessingLog::with(['account', 'ticket'])
                ->orderBy('created_at', 'desc');

            // Apply filters
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('direction')) {
                $query->where('direction', $request->direction);
            }

            if ($request->filled('account_id')) {
                $query->where('account_id', $request->account_id);
            }

            if ($request->filled('has_commands')) {
                if ($request->boolean('has_commands')) {
                    $query->where('commands_processed', '>', 0);
                } else {
                    $query->where(function ($q) {
                        $q->whereNull('commands_processed')->orWhere('commands_processed', 0);
                    });
                }
            }

            if ($request->filled('command_success')) {
                $query->where('command_processing_success', $request->boolean('command_success'));
            }

            if ($request->filled('date_from')) {
                $query->where('created_at', '>=', Carbon::parse($request->date_from)->startOfDay());
            }

            if ($request->filled('date_to')) {
                $query->where('created_at', '<=', Carbon::parse($request->date_to)->endOfDay());
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('subject', 'ILIKE', "%{$search}%")
                      ->orWhere('from_address', 'ILIKE', "%{$search}%")
                      ->orWhere('email_id', 'ILIKE', "%{$search}%");
                });
            }

            $logs = $query->paginate($request->get('per_page', 25));

            return response()->json([
                'success' => true,
                'data' => $logs->items(),
                'meta' => [
                    'current_page' => $logs->currentPage(),
                    'last_page' => $logs->lastPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch email processing logs', [
                'error' => $e->getMessage(),
                'filters' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch processing logs',
            ], 500);
        }
    }

    /**
     * Get detailed processing log information
     */
    public function getProcessingLogDetail(string $emailId): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access email processing details');
        }

        try {
            $log = EmailProcessingLog::where('email_id', $emailId)
                ->with(['account', 'ticket.comments'])
                ->first();

            if (!$log) {
                return response()->json([
                    'success' => false,
                    'error' => 'Processing log not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $log,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch processing log detail', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch processing log details',
            ], 500);
        }
    }

    /**
     * Retry failed email processing
     */
    public function retryProcessing(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to retry email processing');
        }

        $validator = Validator::make($request->all(), [
            'email_ids' => 'required|array|min:1|max:50',
            'email_ids.*' => 'required|string',
            'force' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $emailIds = $request->email_ids;
            $force = $request->boolean('force', false);
            $retryCount = 0;
            $failedCount = 0;

            foreach ($emailIds as $emailId) {
                $log = EmailProcessingLog::where('email_id', $emailId)->first();
                
                if (!$log) {
                    $failedCount++;
                    continue;
                }

                // Check if retry is allowed
                if (!$force && ($log->retry_count >= 3 || $log->status === 'processed')) {
                    $failedCount++;
                    continue;
                }

                // Reset status and dispatch job
                $log->update([
                    'status' => 'pending',
                    'next_retry_at' => null,
                    'error_message' => null,
                ]);

                // Reconstruct email data for reprocessing
                $emailData = [
                    'email_id' => $log->email_id,
                    'direction' => $log->direction,
                    'from' => $log->from_address,
                    'to' => $log->to_addresses ?? [],
                    'subject' => $log->subject,
                    'message_id' => $log->message_id,
                    'raw_content' => $log->raw_email_content,
                    'received_at' => $log->received_at,
                ];

                ProcessIncomingEmail::dispatch($emailData);
                $retryCount++;
            }

            return response()->json([
                'success' => true,
                'message' => "Retry initiated for {$retryCount} emails",
                'retried' => $retryCount,
                'failed' => $failedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Bulk retry processing failed', [
                'error' => $e->getMessage(),
                'email_ids' => $request->email_ids,
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Retry processing failed',
            ], 500);
        }
    }

    /**
     * Get queue monitoring information
     */
    public function getQueueStatus(): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to access queue status');
        }

        try {
            $queueData = [
                'queues' => [
                    'email-incoming' => $this->getQueueInfo('email-incoming'),
                    'email-outgoing' => $this->getQueueInfo('email-outgoing'),
                    'email-processing' => $this->getQueueInfo('email-processing'),
                    'default' => $this->getQueueInfo('default'),
                ],
                'failed_jobs' => $this->getFailedJobsInfo(),
                'workers' => $this->getWorkerStatus(),
                'health_check' => $this->performQueueHealthCheck(),
            ];

            return response()->json([
                'success' => true,
                'data' => $queueData,
                'checked_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Queue status check failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to check queue status',
            ], 500);
        }
    }

    /**
     * Get system health metrics (private method for dashboard)
     */
    private function getSystemHealth(): array
    {
        try {
            $emailConfig = EmailSystemConfig::getConfig();
            
            $health = [
                'system_active' => $emailConfig->system_active ?? false,
                'fully_configured' => $emailConfig->isFullyConfigured() ?? false,
                'incoming_enabled' => $emailConfig->incoming_enabled ?? false,
                'outgoing_enabled' => $emailConfig->outgoing_enabled ?? false,
                'email_provider' => $emailConfig->incoming_provider ?? 'none', // Dashboard expects 'email_provider'
                'outgoing_provider' => $emailConfig->outgoing_provider ?? 'none',
                'use_same_provider' => false, // TODO: Add this to model
                'timestamp_source' => 'original', // TODO: Add this to model
                'timestamp_timezone' => 'preserve', // TODO: Add this to model
                'domain_mappings_count' => EmailDomainMapping::count(),
                'active_domain_mappings' => EmailDomainMapping::where('is_active', true)->count(),
                'last_updated' => $emailConfig->updated_at,
            ];

            return $health;

        } catch (\Exception $e) {
            Log::error('System health check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'system_active' => false,
                'fully_configured' => false,
                'incoming_enabled' => false,
                'outgoing_enabled' => false,
                'email_provider' => 'none',
                'outgoing_provider' => 'none',
                'use_same_provider' => false,
                'timestamp_source' => 'original',
                'timestamp_timezone' => 'preserve',
                'domain_mappings_count' => 0,
                'active_domain_mappings' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Manually trigger email retrieval with dual modes (test vs process)
     */
    public function manualEmailRetrieval(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to trigger manual email retrieval');
        }

        $validator = Validator::make($request->all(), [
            'mode' => 'required|in:test,process',
            'limit' => 'integer|min:1|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $mode = $request->get('mode', 'test');
            $limit = $request->get('limit', 10);
            
            Log::info('Manual email retrieval initiated', [
                'user_id' => auth()->id(),
                'mode' => $mode,
                'limit' => $limit,
            ]);

            // Get email system configuration
            $emailConfig = EmailSystemConfig::getConfig();
            
            if (!$emailConfig->hasIncomingConfigured()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Incoming email not configured',
                    'message' => 'Configure incoming email settings first',
                ], 400);
            }

            $retrievalResult = $this->retrieveEmailsWithMode($emailConfig, $mode, $limit);
            
            Log::info('Manual email retrieval completed', [
                'user_id' => auth()->id(),
                'mode' => $mode,
                'emails_retrieved' => $retrievalResult['emails_retrieved'],
                'emails_processed' => $retrievalResult['emails_processed'],
                'tickets_created' => $retrievalResult['tickets_created'],
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Manual email retrieval ({$mode} mode) completed successfully",
                'mode' => $mode,
                'emails_retrieved' => $retrievalResult['emails_retrieved'],
                'emails_processed' => $retrievalResult['emails_processed'],
                'tickets_created' => $retrievalResult['tickets_created'],
                'processing_details' => $retrievalResult['processing_details'] ?? [],
            ]);

        } catch (\Exception $e) {
            Log::error('Manual email retrieval failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Manual email retrieval failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retrieve emails with specified mode (test vs process)
     */
    private function retrieveEmailsWithMode(EmailSystemConfig $config, string $mode, int $limit): array
    {
        // Retrieve emails based on provider type
        if ($config->incoming_provider === 'imap' || $config->incoming_provider === 'gmail' || $config->incoming_provider === 'outlook') {
            return $this->retrieveImapEmailsWithMode($config, $mode, $limit);
        } elseif ($config->incoming_provider === 'm365') {
            return $this->retrieveM365EmailsWithMode($config, $mode, $limit);
        } else {
            throw new \Exception('Unsupported email provider: ' . $config->incoming_provider);
        }
    }

    /**
     * Retrieve emails from IMAP server with dual mode support
     */
    private function retrieveImapEmailsWithMode(EmailSystemConfig $config, string $mode, int $limit): array
    {
        $emailProcessingService = app(EmailProcessingService::class);
        $createTickets = ($mode === 'process');
        
        $result = [
            'emails_retrieved' => 0,
            'emails_processed' => 0,
            'tickets_created' => 0,
            'processing_details' => [],
        ];
        
        $host = $config->incoming_host;
        $port = $config->incoming_port ?? 993;
        $username = $config->incoming_username;
        $password = $config->incoming_password;
        $encryption = $config->incoming_encryption ?? 'ssl';
        $folder = $config->incoming_folder ?? 'INBOX';
        
        // Build connection string
        $connectionString = "{{$host}:{$port}";
        if ($encryption) {
            $connectionString .= '/'.$encryption;
        }
        $connectionString .= '}'.$folder;

        // Connect to IMAP server
        $connection = imap_open($connectionString, $username, $password);
        if (!$connection) {
            throw new \Exception('Failed to connect to IMAP server: '.imap_last_error());
        }

        try {
            // Get recent emails (unread messages up to limit)
            $emails = imap_search($connection, 'UNSEEN', SE_UID);
            
            if (!$emails) {
                imap_close($connection);
                return $result;
            }

            // Limit emails
            $emails = array_slice($emails, -$limit);
            $result['emails_retrieved'] = count($emails);
            
            foreach ($emails as $uid) {
                try {
                    // Get email headers and body
                    $header = imap_rfc822_parse_headers(imap_fetchheader($connection, $uid, FT_UID));
                    $body = imap_fetchbody($connection, $uid, '', FT_UID);
                    
                    // Build email data array
                    $emailData = [
                        'email_id' => (string) \Str::uuid(),
                        'direction' => 'incoming',
                        'from' => $header->fromaddress ?? 'unknown@example.com',
                        'to' => isset($header->toaddress) ? [$header->toaddress] : [],
                        'subject' => $header->subject ?? 'No Subject',
                        'message_id' => $header->message_id ?? null,
                        'received_at' => isset($header->date) ? new \DateTime($header->date) : now(),
                        'raw_content' => $body,
                        'body_text' => $body,
                    ];
                    
                    // Process through unified service
                    $processingResult = $emailProcessingService->processEmail($emailData, $createTickets);
                    
                    if ($processingResult['success']) {
                        $result['emails_processed']++;
                        if ($processingResult['ticket_created']) {
                            $result['tickets_created']++;
                        }
                        
                        // Mark as read only in process mode when processing succeeded
                        if ($mode === 'process') {
                            imap_setflag_full($connection, $uid, '\\Seen', ST_UID);
                        }
                    }
                    
                    // Store processing details for response
                    $result['processing_details'][] = [
                        'email_id' => $emailData['email_id'],
                        'from' => $emailData['from'],
                        'subject' => $emailData['subject'],
                        'success' => $processingResult['success'],
                        'actions_taken' => $processingResult['actions_taken'],
                        'error' => $processingResult['error'] ?? null,
                    ];
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to process individual email', [
                        'uid' => $uid,
                        'error' => $e->getMessage(),
                    ]);
                    
                    $result['processing_details'][] = [
                        'email_id' => 'unknown',
                        'uid' => $uid,
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            imap_close($connection);
            return $result;
            
        } catch (\Exception $e) {
            imap_close($connection);
            throw $e;
        }
    }

    /**
     * Legacy IMAP method for backward compatibility
     */
    private function retrieveImapEmails(EmailSystemConfig $config): int
    {
        $host = $config->incoming_host;
        $port = $config->incoming_port ?? 993;
        $username = $config->incoming_username;
        $password = $config->incoming_password;
        $encryption = $config->incoming_encryption ?? 'ssl';
        $folder = $config->incoming_folder ?? 'INBOX';
        
        // Build connection string
        $connectionString = "{{$host}:{$port}";
        if ($encryption) {
            $connectionString .= '/'.$encryption;
        }
        $connectionString .= '}'.$folder;

        // Connect to IMAP server
        $connection = imap_open($connectionString, $username, $password);
        if (!$connection) {
            throw new \Exception('Failed to connect to IMAP server: '.imap_last_error());
        }

        try {
            // Get recent emails (last 10 unread messages)
            $emails = imap_search($connection, 'UNSEEN', SE_UID);
            
            if (!$emails) {
                imap_close($connection);
                return 0;
            }

            // Limit to 10 emails for manual retrieval
            $emails = array_slice($emails, -10);
            $processedCount = 0;
            
            foreach ($emails as $uid) {
                try {
                    // Get email headers and body
                    $header = imap_rfc822_parse_headers(imap_fetchheader($connection, $uid, FT_UID));
                    $body = imap_fetchbody($connection, $uid, '', FT_UID);
                    
                    // Create email processing log entry
                    EmailProcessingLog::createFromEmailData([
                        'email_id' => (string) \Str::uuid(),
                        'direction' => 'incoming',
                        'from' => $header->fromaddress ?? 'unknown@example.com',
                        'to' => isset($header->toaddress) ? [$header->toaddress] : [],
                        'subject' => $header->subject ?? 'No Subject',
                        'message_id' => $header->message_id ?? null,
                        'received_at' => isset($header->date) ? new \DateTime($header->date) : now(),
                        'raw_content' => $body,
                        'body_text' => $body,
                    ]);
                    
                    // Mark as read only in process mode
                    if ($mode === 'process') {
                        imap_setflag_full($connection, $uid, '\\Seen', ST_UID);
                    }
                    
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to process individual email', [
                        'uid' => $uid,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            imap_close($connection);
            return $processedCount;
            
        } catch (\Exception $e) {
            imap_close($connection);
            throw $e;
        }
    }

    /**
     * Retrieve emails from Microsoft 365 with dual mode support
     */
    private function retrieveM365EmailsWithMode(EmailSystemConfig $config, string $mode, int $limit): array
    {
        $emailProcessingService = app(EmailProcessingService::class);
        $createTickets = ($mode === 'process');
        
        $result = [
            'emails_retrieved' => 0,
            'emails_processed' => 0,
            'tickets_created' => 0,
            'processing_details' => [],
        ];
        
        $settings = $config->incoming_settings;
        $tenantId = $settings['tenant_id'];
        $clientId = $settings['client_id'];
        $clientSecret = $settings['client_secret'];
        $mailbox = $settings['mailbox'];
        $folderId = $settings['folder_id'] ?? null;
        
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
            $baseUrl = "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders";
            $messagesUrl = $folderId 
                ? "{$baseUrl}/{$folderId}/messages"
                : "https://graph.microsoft.com/v1.0/users/{$mailbox}/messages";
            
            // Get unread messages (up to limit)
            $messagesResponse = Http::withToken($token)
                ->get($messagesUrl, [
                    '$filter' => 'isRead eq false',
                    '$top' => $limit,
                    '$select' => 'id,subject,from,toRecipients,receivedDateTime,body,internetMessageId,conversationId,isRead'
                ]);
            
            if (!$messagesResponse->successful()) {
                throw new \Exception('Failed to get M365 messages: ' . $messagesResponse->body());
            }
            
            $messages = $messagesResponse->json()['value'] ?? [];
            $result['emails_retrieved'] = count($messages);
            
            // Step 3: Process each message
            foreach ($messages as $message) {
                try {
                    // Build email data array
                    $emailData = [
                        'email_id' => (string) \Str::uuid(),
                        'direction' => 'incoming',
                        'from' => $message['from']['emailAddress']['address'] ?? 'unknown@example.com',
                        'to' => collect($message['toRecipients'])->pluck('emailAddress.address')->toArray(),
                        'subject' => $message['subject'] ?? 'No Subject',
                        'message_id' => $message['internetMessageId'] ?? null,
                        'received_at' => isset($message['receivedDateTime']) 
                            ? new \DateTime($message['receivedDateTime']) 
                            : now(),
                        'raw_content' => json_encode($message),
                        'body_text' => $message['body']['content'] ?? '',
                        'body_html' => $message['body']['contentType'] === 'html' 
                            ? $message['body']['content'] 
                            : null,
                    ];
                    
                    // Process through unified service
                    $processingResult = $emailProcessingService->processEmail($emailData, $createTickets);
                    
                    if ($processingResult['success']) {
                        $result['emails_processed']++;
                        if ($processingResult['ticket_created']) {
                            $result['tickets_created']++;
                        }
                        
                        // Mark message as read only in process mode when processing succeeded
                        if ($mode === 'process') {
                            $markReadResponse = Http::withToken($token)
                                ->patch("https://graph.microsoft.com/v1.0/users/{$mailbox}/messages/{$message['id']}", [
                                    'isRead' => true
                                ]);
                            
                            if (!$markReadResponse->successful()) {
                                Log::warning('Failed to mark M365 message as read', [
                                    'message_id' => $message['id'],
                                    'error' => $markReadResponse->body()
                                ]);
                            }
                        }
                    }
                    
                    // Store processing details for response
                    $result['processing_details'][] = [
                        'email_id' => $emailData['email_id'],
                        'from' => $emailData['from'],
                        'subject' => $emailData['subject'],
                        'success' => $processingResult['success'],
                        'actions_taken' => $processingResult['actions_taken'],
                        'error' => $processingResult['error'] ?? null,
                    ];
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to process individual M365 email', [
                        'message_id' => $message['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                    
                    $result['processing_details'][] = [
                        'email_id' => 'unknown',
                        'message_id' => $message['id'] ?? 'unknown',
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }
            }
            
            return $result;
            
        } catch (\Exception $e) {
            Log::error('M365 email retrieval failed', [
                'mailbox' => $mailbox,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Legacy M365 method for backward compatibility
     */
    private function retrieveM365Emails(EmailSystemConfig $config): int
    {
        $settings = $config->incoming_settings;
        $tenantId = $settings['tenant_id'];
        $clientId = $settings['client_id'];
        $clientSecret = $settings['client_secret'];
        $mailbox = $settings['mailbox'];
        $folderId = $settings['folder_id'] ?? null;
        
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
            $baseUrl = "https://graph.microsoft.com/v1.0/users/{$mailbox}/mailFolders";
            $messagesUrl = $folderId 
                ? "{$baseUrl}/{$folderId}/messages"
                : "https://graph.microsoft.com/v1.0/users/{$mailbox}/messages";
            
            // Get unread messages (limit 10 for manual retrieval)
            $messagesResponse = Http::withToken($token)
                ->get($messagesUrl, [
                    '$filter' => 'isRead eq false',
                    '$top' => 10,
                    '$select' => 'id,subject,from,toRecipients,receivedDateTime,body,internetMessageId,conversationId,isRead'
                ]);
            
            if (!$messagesResponse->successful()) {
                throw new \Exception('Failed to get M365 messages: ' . $messagesResponse->body());
            }
            
            $messages = $messagesResponse->json()['value'] ?? [];
            $processedCount = 0;
            
            // Step 3: Process each message
            foreach ($messages as $message) {
                try {
                    // Create email processing log entry
                    EmailProcessingLog::createFromEmailData([
                        'email_id' => (string) \Str::uuid(),
                        'direction' => 'incoming',
                        'from' => $message['from']['emailAddress']['address'] ?? 'unknown@example.com',
                        'to' => collect($message['toRecipients'])->pluck('emailAddress.address')->toArray(),
                        'subject' => $message['subject'] ?? 'No Subject',
                        'message_id' => $message['internetMessageId'] ?? null,
                        'received_at' => isset($message['receivedDateTime']) 
                            ? new \DateTime($message['receivedDateTime']) 
                            : now(),
                        'raw_content' => json_encode($message),
                        'body_text' => $message['body']['content'] ?? '',
                        'body_html' => $message['body']['contentType'] === 'html' 
                            ? $message['body']['content'] 
                            : null,
                    ]);
                    
                    // Mark message as read only in process mode
                    if ($mode === 'process') {
                        $markReadResponse = Http::withToken($token)
                            ->patch("https://graph.microsoft.com/v1.0/users/{$mailbox}/messages/{$message['id']}", [
                                'isRead' => true
                            ]);
                        
                        if (!$markReadResponse->successful()) {
                            Log::warning('Failed to mark M365 message as read', [
                                'message_id' => $message['id'],
                                'error' => $markReadResponse->body()
                            ]);
                        }
                    }
                    
                    $processedCount++;
                    
                } catch (\Exception $e) {
                    Log::warning('Failed to process individual M365 email', [
                        'message_id' => $message['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            return $processedCount;
            
        } catch (\Exception $e) {
            Log::error('M365 email retrieval failed', [
                'mailbox' => $mailbox,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Debug email configuration - temporary endpoint for troubleshooting
     */
    public function debugEmailConfig(): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized');
        }

        $emailConfig = EmailSystemConfig::getConfig();
        $legacySettings = \App\Models\Setting::where('key', 'like', 'email.%')->pluck('value', 'key');
        
        return response()->json([
            'email_system_config' => [
                'id' => $emailConfig->id,
                'system_active' => $emailConfig->system_active,
                'incoming_enabled' => $emailConfig->incoming_enabled,
                'outgoing_enabled' => $emailConfig->outgoing_enabled,
                'incoming_provider' => $emailConfig->incoming_provider,
                'incoming_host' => $emailConfig->incoming_host,
                'incoming_username' => $emailConfig->incoming_username,
                'incoming_password' => $emailConfig->incoming_password ? '[HIDDEN]' : null,
                'hasIncomingConfigured' => $emailConfig->hasIncomingConfigured(),
            ],
            'legacy_settings' => $legacySettings,
            'raw_model_attributes' => $emailConfig->getAttributes(),
        ]);
    }

    /**
     * Clear failed jobs from queue
     */
    public function clearFailedJobs(Request $request): JsonResponse
    {
        if (!auth()->user()->can('system.configure')) {
            abort(403, 'Unauthorized to clear failed jobs');
        }

        $validator = Validator::make($request->all(), [
            'older_than_hours' => 'integer|min:1|max:720', // Max 30 days
            'queue' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $olderThanHours = $request->get('older_than_hours', 24);
            $queue = $request->get('queue');
            
            // This would need to be implemented based on your queue driver
            // For database queue driver:
            $query = DB::table('failed_jobs')
                ->where('failed_at', '<', now()->subHours($olderThanHours));
                
            if ($queue) {
                $query->where('queue', $queue);
            }
            
            $deletedCount = $query->count();
            $query->delete();

            Log::info('Failed jobs cleared', [
                'count' => $deletedCount,
                'older_than_hours' => $olderThanHours,
                'queue' => $queue,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cleared {$deletedCount} failed jobs",
                'cleared_count' => $deletedCount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to clear failed jobs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear failed jobs',
            ], 500);
        }
    }

    /**
     * Get queued/unprocessed emails for admin review
     */
    public function getQueuedEmails(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:pending,ignored,retry',
                'per_page' => 'integer|min:1|max:100',
                'page' => 'integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                ], 400);
            }

            $query = EmailProcessingLog::query()
                ->with(['account'])
                ->orderBy('received_at', 'desc');

            // Filter by status - default to pending and ignored (queued for review)
            $status = $request->get('status');
            if ($status) {
                $query->where('status', $status);
            } else {
                $query->whereIn('status', ['pending', 'ignored', 'retry']);
            }

            // Filter out successfully processed emails
            $query->where('status', '!=', 'processed');

            $perPage = $request->get('per_page', 20);
            $emails = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $emails->items(),
                'pagination' => [
                    'current_page' => $emails->currentPage(),
                    'per_page' => $emails->perPage(),
                    'total' => $emails->total(),
                    'last_page' => $emails->lastPage(),
                    'has_more' => $emails->hasMorePages(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch queued emails', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to fetch queued emails',
                'message' => 'An error occurred while loading queued emails for review',
            ], 500);
        }
    }

    /**
     * Assign account to queued email and reprocess
     */
    public function assignEmailToAccount(Request $request, string $emailId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'account_id' => 'required|exists:accounts,id',
                'create_ticket' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                ], 400);
            }

            $email = EmailProcessingLog::where('email_id', $emailId)->first();
            if (!$email) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email not found',
                ], 404);
            }

            // Update email with assigned account
            $email->update([
                'account_id' => $request->account_id,
                'status' => 'pending',
            ]);

            // TODO: Reprocess email with assigned account
            // This would involve calling the EmailProcessingService again

            Log::info('Email assigned to account', [
                'email_id' => $emailId,
                'account_id' => $request->account_id,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email assigned to account successfully',
                'email_id' => $emailId,
                'account_id' => $request->account_id,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to assign email to account', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to assign email to account',
            ], 500);
        }
    }

    /**
     * Reject/ignore queued email permanently
     */
    public function rejectQueuedEmail(Request $request, string $emailId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $validator->errors(),
                ], 400);
            }

            $email = EmailProcessingLog::where('email_id', $emailId)->first();
            if (!$email) {
                return response()->json([
                    'success' => false,
                    'error' => 'Email not found',
                ], 404);
            }

            // Mark as permanently ignored
            $email->update([
                'status' => 'ignored',
                'error_message' => $request->reason ? "Rejected by admin: {$request->reason}" : 'Rejected by admin',
                'error_details' => [
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now()->toISOString(),
                    'reason' => $request->reason,
                ],
            ]);

            Log::info('Email rejected by admin', [
                'email_id' => $emailId,
                'reason' => $request->reason,
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Email rejected successfully',
                'email_id' => $emailId,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reject queued email', [
                'email_id' => $emailId,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to reject email',
            ], 500);
        }
    }

    /**
     * Get overview statistics
     */
    private function getOverviewStats(Carbon $startDate): array
    {
        try {
            // Check if EmailProcessingLog table exists and has data
            if (!DB::table('email_processing_logs')->exists()) {
                return $this->getDefaultOverviewStats();
            }

            return [
                'total_emails_processed' => EmailProcessingLog::where('created_at', '>=', $startDate)->count(),
                'successful_processing' => EmailProcessingLog::where('created_at', '>=', $startDate)->where('status', 'processed')->count(),
                'failed_processing' => EmailProcessingLog::where('created_at', '>=', $startDate)->where('status', 'failed')->count(),
                'tickets_created' => EmailProcessingLog::where('created_at', '>=', $startDate)->where('created_new_ticket', true)->count(),
                'comments_added' => EmailProcessingLog::where('created_at', '>=', $startDate)->whereNotNull('ticket_comment_id')->count(),
                'commands_executed' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_executed_count'),
                'system_configured' => EmailSystemConfig::getConfig()->isFullyConfigured(),
                'domain_mappings_active' => EmailDomainMapping::where('is_active', true)->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get overview stats', ['error' => $e->getMessage()]);
            return $this->getDefaultOverviewStats();
        }
    }

    private function getDefaultOverviewStats(): array
    {
        return [
            'total_emails_processed' => 0,
            'successful_processing' => 0,
            'failed_processing' => 0,
            'tickets_created' => 0,
            'comments_added' => 0,
            'commands_executed' => 0,
            'system_configured' => false,
            'domain_mappings_active' => 0,
        ];
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats(Carbon $startDate): array
    {
        $logs = EmailProcessingLog::where('created_at', '>=', $startDate)
            ->whereNotNull('processing_duration_ms')
            ->get();

        return [
            'avg_processing_time_ms' => $logs->avg('processing_duration_ms'),
            'max_processing_time_ms' => $logs->max('processing_duration_ms'),
            'min_processing_time_ms' => $logs->min('processing_duration_ms'),
            'success_rate' => $logs->count() > 0 ? ($logs->where('status', 'processed')->count() / $logs->count()) * 100 : 0,
            'hourly_volume' => $this->getHourlyVolume($startDate),
        ];
    }

    /**
     * Get command statistics
     */
    private function getCommandStats(Carbon $startDate): array
    {
        return [
            'total_commands_found' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_processed'),
            'total_commands_executed' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_executed_count'),
            'total_commands_failed' => EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_failed_count'),
            'command_success_rate' => $this->getCommandSuccessRate($startDate),
            'popular_commands' => $this->getPopularCommands($startDate),
        ];
    }

    /**
     * Get domain mapping statistics
     */
    private function getDomainMappingStats(): array
    {
        return [
            'total_mappings' => EmailDomainMapping::count(),
            'active_mappings' => EmailDomainMapping::where('is_active', true)->count(),
            'by_priority' => EmailDomainMapping::groupBy('priority')
                ->selectRaw('priority, count(*) as count')
                ->pluck('count', 'priority')->toArray(),
            'recent_mappings' => EmailDomainMapping::where('created_at', '>=', now()->subDays(7))->count(),
        ];
    }

    /**
     * Get queue health information
     */
    private function getQueueHealth(): array
    {
        return [
            'pending_jobs' => $this->getPendingJobsCount(),
            'failed_jobs' => $this->getFailedJobsCount(),
            'processed_today' => $this->getProcessedTodayCount(),
            'oldest_pending' => $this->getOldestPendingJob(),
        ];
    }

    /**
     * Get recent activity
     */
    private function getRecentActivity(): array
    {
        try {
            if (!DB::table('email_processing_logs')->exists()) {
                return [];
            }

            return EmailProcessingLog::with(['account', 'ticket'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($log) {
                    return [
                        'email_id' => $log->email_id,
                        'status' => $log->status,
                        'from_address' => $log->from_address,
                        'subject' => $log->subject,
                        'created_at' => $log->created_at,
                        'ticket_number' => $log->ticket?->ticket_number,
                        'account_name' => $log->account?->name,
                    ];
                })
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Failed to get recent activity', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Get system alerts
     */
    private function getSystemAlerts(Carbon $startDate): array
    {
        $alerts = [];

        $emailConfig = EmailSystemConfig::getConfig();

        // Check if system is not configured
        if (!$emailConfig->system_active) {
            $alerts[] = [
                'type' => 'warning',
                'message' => 'Email system is not active',
                'details' => 'Configure and activate the email system to start processing emails',
            ];
        }

        // Check if system is not fully configured
        if (!$emailConfig->isFullyConfigured()) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'Email system configuration incomplete',
                'details' => 'Complete incoming and outgoing email service configuration',
            ];
        }

        // Check for high failure rate
        $totalEmails = EmailProcessingLog::where('created_at', '>=', $startDate)->count();
        if ($totalEmails > 0) {
            $failedEmails = EmailProcessingLog::where('created_at', '>=', $startDate)->where('status', 'failed')->count();
            if (($failedEmails / $totalEmails) > 0.1) {
                $alerts[] = [
                    'type' => 'warning',
                    'message' => 'High email processing failure rate detected',
                    'details' => "Failed: {$failedEmails}/{$totalEmails} (" . round(($failedEmails / $totalEmails) * 100, 1) . "%)",
                ];
            }
        }

        // Check for domain mappings
        $activeMappings = EmailDomainMapping::where('is_active', true)->count();
        if ($activeMappings === 0) {
            $alerts[] = [
                'type' => 'info',
                'message' => 'No active domain mappings configured',
                'details' => 'Set up domain mappings to route incoming emails to accounts',
            ];
        }

        return $alerts;
    }


    /**
     * Helper methods for statistics
     */
    private function getStartDate(string $range): Carbon
    {
        return match ($range) {
            '24h' => now()->subDay(),
            '7d' => now()->subWeek(),
            '30d' => now()->subMonth(),
            '90d' => now()->subMonths(3),
            default => now()->subWeek(),
        };
    }

    private function getHourlyVolume(Carbon $startDate): array
    {
        return EmailProcessingLog::where('created_at', '>=', $startDate)
            ->groupBy(DB::raw('DATE_TRUNC(\'hour\', created_at)'))
            ->selectRaw('DATE_TRUNC(\'hour\', created_at) as hour, count(*) as count')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();
    }

    private function getCommandSuccessRate(Carbon $startDate): float
    {
        $totalCommands = EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_processed');
        $executedCommands = EmailProcessingLog::where('created_at', '>=', $startDate)->sum('commands_executed_count');
        
        return $totalCommands > 0 ? ($executedCommands / $totalCommands) * 100 : 0;
    }

    private function getPopularCommands(Carbon $startDate): array
    {
        // This would require parsing command_results JSON to get specific command usage
        // For now, return placeholder data
        return [
            'time' => 150,
            'priority' => 89,
            'status' => 67,
            'assign' => 45,
            'due' => 23,
        ];
    }

    private function getPendingJobsCount(): int
    {
        return DB::table('jobs')->count();
    }

    private function getFailedJobsCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    private function getProcessedTodayCount(): int
    {
        return EmailProcessingLog::whereDate('created_at', today())->where('status', 'processed')->count();
    }

    private function getOldestPendingJob(): ?array
    {
        $job = DB::table('jobs')->orderBy('created_at')->first();
        
        if ($job) {
            return [
                'id' => $job->id,
                'queue' => $job->queue,
                'created_at' => $job->created_at,
            ];
        }
        
        return null;
    }

    private function getQueueInfo(string $queueName): array
    {
        $pending = DB::table('jobs')->where('queue', $queueName)->count();
        $failed = DB::table('failed_jobs')->where('queue', $queueName)->count();
        
        return [
            'pending' => $pending,
            'failed' => $failed,
            'status' => $pending > 100 ? 'warning' : 'healthy',
        ];
    }

    private function getFailedJobsInfo(): array
    {
        return [
            'total' => DB::table('failed_jobs')->count(),
            'by_queue' => DB::table('failed_jobs')
                ->groupBy('queue')
                ->selectRaw('queue, count(*) as count')
                ->pluck('count', 'queue')
                ->toArray(),
            'oldest' => DB::table('failed_jobs')->orderBy('failed_at')->first(),
        ];
    }

    private function getWorkerStatus(): array
    {
        // This would need to be implemented based on your worker monitoring system
        return [
            'active_workers' => 0, // Placeholder
            'status' => 'unknown',
        ];
    }

    private function performQueueHealthCheck(): array
    {
        $healthy = true;
        $issues = [];
        
        $pendingJobs = $this->getPendingJobsCount();
        if ($pendingJobs > 500) {
            $healthy = false;
            $issues[] = "High pending job count: {$pendingJobs}";
        }
        
        $failedJobs = $this->getFailedJobsCount();
        if ($failedJobs > 50) {
            $healthy = false;
            $issues[] = "High failed job count: {$failedJobs}";
        }
        
        return [
            'healthy' => $healthy,
            'issues' => $issues,
        ];
    }

    // Health check methods
    private function checkDatabaseHealth(): array
    {
        try {
            DB::connection()->getPdo();
            return ['healthy' => true, 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['healthy' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    private function checkEmailSystemHealth(): array
    {
        $emailConfig = EmailSystemConfig::getConfig();
        return [
            'healthy' => $emailConfig->system_active && $emailConfig->isFullyConfigured(),
            'message' => $emailConfig->system_active ? 
                ($emailConfig->isFullyConfigured() ? 'Email system active and configured' : 'Email system active but not fully configured') :
                'Email system inactive',
            'system_active' => $emailConfig->system_active,
            'fully_configured' => $emailConfig->isFullyConfigured(),
        ];
    }

    private function checkDomainMappingsHealth(): array
    {
        $activeMappings = EmailDomainMapping::where('is_active', true)->count();
        return [
            'healthy' => $activeMappings > 0,
            'message' => "Active domain mappings: {$activeMappings}",
            'count' => $activeMappings,
        ];
    }

    private function checkQueueSystemHealth(): array
    {
        $health = $this->performQueueHealthCheck();
        return [
            'healthy' => $health['healthy'],
            'message' => $health['healthy'] ? 'Queue system healthy' : implode(', ', $health['issues']),
        ];
    }

    private function checkProcessingLogsHealth(): array
    {
        $recentFailures = EmailProcessingLog::where('created_at', '>=', now()->subHour())
            ->where('status', 'failed')
            ->count();
            
        return [
            'healthy' => $recentFailures < 10,
            'message' => "Recent failures (1h): {$recentFailures}",
            'count' => $recentFailures,
        ];
    }

    private function checkRecentProcessingHealth(): array
    {
        $recentProcessing = EmailProcessingLog::where('created_at', '>=', now()->subMinutes(15))->count();
        
        return [
            'healthy' => true, // Always healthy for now
            'message' => "Recent emails (15m): {$recentProcessing}",
            'count' => $recentProcessing,
        ];
    }

    private function getSystemUptime(): string
    {
        // This would need to be implemented based on your system monitoring
        return 'Unknown';
    }

    private function getMemoryUsage(): array
    {
        return [
            'used' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ];
    }

    private function getDiskUsage(): array
    {
        $path = storage_path();
        return [
            'total' => disk_total_space($path),
            'free' => disk_free_space($path),
            'used' => disk_total_space($path) - disk_free_space($path),
        ];
    }
}