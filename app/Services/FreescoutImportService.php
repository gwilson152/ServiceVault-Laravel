<?php

namespace App\Services;

use App\Events\FreescoutImportProgressUpdated;
use App\Events\ImportJobStatusChanged;
use App\Models\ImportJob;
use App\Models\ImportProfile;
use App\Models\RoleTemplate;
use App\Models\DomainMapping;
use App\Models\Account;
use App\Models\User;
use App\Models\Ticket;
use App\Models\TimeEntry;
use App\Models\TicketComment;
use App\Models\BillingRate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class FreescoutImportService
{
    /**
     * Test connection to FreeScout API.
     */
    public function testConnection(ImportProfile $profile): array
    {
        try {
            $config = $profile->getConnectionConfig();
            
            if (!isset($config['host']) || !isset($config['password'])) {
                return [
                    'success' => false,
                    'error' => 'Missing API URL or API key in profile configuration'
                ];
            }

            $apiUrl = rtrim($config['host'], '/') . '/api/mailboxes';
            $response = Http::withHeaders([
                'X-FreeScout-API-Key' => $config['password']
            ])->timeout(10)->get($apiUrl);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'success' => true,
                    'mailboxes_count' => count($data['_embedded']['mailboxes'] ?? []),
                    'api_version' => $response->header('X-API-Version', 'unknown')
                ];
            }

            return [
                'success' => false,
                'error' => 'API request failed: ' . $response->status() . ' - ' . $response->body()
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Validate that required role templates exist in Service Vault.
     */
    public function validateRoleTemplates(): array
    {
        $requiredRoles = ['Agent', 'Account User'];
        $missingRoles = [];

        foreach ($requiredRoles as $roleName) {
            if (!RoleTemplate::where('name', $roleName)->exists()) {
                $missingRoles[] = $roleName;
            }
        }

        return [
            'success' => empty($missingRoles),
            'missing_roles' => $missingRoles,
            'required_roles' => $requiredRoles
        ];
    }

    /**
     * Validate domain mappings for domain mapping strategy.
     */
    public function validateDomainMappings(): array
    {
        $domainMappings = DomainMapping::where('is_active', true)->get();

        if ($domainMappings->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No active domain mappings found. Domain mapping strategy requires existing domain mappings.'
            ];
        }

        return [
            'success' => true,
            'mappings_count' => $domainMappings->count(),
            'mappings' => $domainMappings->map(fn($dm) => [
                'pattern' => $dm->domain_pattern,
                'account' => $dm->account->name
            ])
        ];
    }

    /**
     * Estimate import counts based on configuration.
     */
    public function estimateImportCounts(ImportProfile $profile, array $config): array
    {
        try {
            $apiConfig = $profile->getConnectionConfig();
            $baseUrl = $apiConfig['host'];
            $headers = ['X-FreeScout-API-Key' => $apiConfig['password']];

            $estimates = [
                'conversations' => 0,
                'time_entries' => 0,
                'customers' => 0,
                'accounts' => 0,
                'comments' => 0
            ];

            // Get mailboxes first (excluding any excluded ones)
            $mailboxesResponse = Http::withHeaders($headers)->get($baseUrl . '/api/mailboxes');
            if ($mailboxesResponse->successful()) {
                $mailboxes = $mailboxesResponse->json()['_embedded']['mailboxes'] ?? [];
                $excludedIds = $config['excluded_mailboxes'] ?? [];
                $includedMailboxes = array_filter($mailboxes, fn($mb) => !in_array($mb['id'], $excludedIds));

                // Estimate accounts based on strategy
                if ($config['account_strategy'] === 'map_mailboxes') {
                    $estimates['accounts'] = count($includedMailboxes);
                } else {
                    // Domain mapping - rough estimate based on existing mappings
                    $estimates['accounts'] = DomainMapping::where('is_active', true)->count();
                }

                // Estimate conversations from included mailboxes
                foreach ($includedMailboxes as $mailbox) {
                    $estimates['conversations'] += $mailbox['conversation_count'] ?? 0;
                }

                // Apply limits if specified
                if (!empty($config['limits']['conversations'])) {
                    $estimates['conversations'] = min($estimates['conversations'], $config['limits']['conversations']);
                }
            }

            // Estimate customers - typically 1-2 customers per conversation
            $estimates['customers'] = intval($estimates['conversations'] * 1.3);
            if (!empty($config['limits']['customers'])) {
                $estimates['customers'] = min($estimates['customers'], $config['limits']['customers']);
            }

            // Estimate time entries - sample a few conversations to get average
            $sampleConversations = $this->sampleConversationsForTimeEntries($profile, 5);
            $avgTimeEntriesPerConversation = $sampleConversations['avg_time_entries'] ?? 0.2;
            $estimates['time_entries'] = intval($estimates['conversations'] * $avgTimeEntriesPerConversation);
            if (!empty($config['limits']['time_entries'])) {
                $estimates['time_entries'] = min($estimates['time_entries'], $config['limits']['time_entries']);
            }

            // Estimate comments - typically 3-5 comments per conversation
            $estimates['comments'] = intval($estimates['conversations'] * 4);

            return $estimates;

        } catch (Exception $e) {
            Log::error('Failed to estimate import counts', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage()
            ]);

            return [
                'conversations' => 0,
                'time_entries' => 0,
                'customers' => 0,
                'accounts' => 0,
                'comments' => 0,
                'error' => 'Unable to estimate counts: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Preview import data with sample records.
     */
    public function previewImport(ImportProfile $profile, array $config, int $sampleSize = 10): array
    {
        $apiConfig = $profile->getConnectionConfig();
        $baseUrl = $apiConfig['host'];
        $headers = ['X-FreeScout-API-Key' => $apiConfig['password']];

        $preview = [
            'conversations' => [],
            'customers' => [],
            'time_entries' => [],
            'users' => [],
            'relationship_mapping' => []
        ];

        try {
            // Get sample conversations
            $conversationsResponse = Http::withHeaders($headers)
                ->get($baseUrl . '/api/conversations', ['per_page' => $sampleSize]);
                
            if ($conversationsResponse->successful()) {
                $conversations = $conversationsResponse->json()['_embedded']['conversations'] ?? [];
                
                foreach ($conversations as $conversation) {
                    $preview['conversations'][] = [
                        'freescout_id' => $conversation['id'],
                        'subject' => $conversation['subject'],
                        'status' => $conversation['status'],
                        'mailbox' => $conversation['mailbox']['name'] ?? 'Unknown',
                        'customer_email' => $conversation['customer']['email'] ?? null,
                        'service_vault_mapping' => [
                            'account_id' => $this->determineAccountForConversation($conversation, $config),
                            'title' => $conversation['subject'],
                            'status' => $this->mapFreescoutStatus($conversation['status']),
                            'external_id' => $conversation['id']
                        ]
                    ];
                    
                    // Get threads for this conversation for comment preview
                    $threadsResponse = Http::withHeaders($headers)
                        ->get($baseUrl . "/api/conversations/{$conversation['id']}/threads");
                        
                    if ($threadsResponse->successful()) {
                        $threads = $threadsResponse->json()['_embedded']['threads'] ?? [];
                        foreach (array_slice($threads, 0, 2) as $thread) {
                            $preview['relationship_mapping'][] = [
                                'type' => 'comment',
                                'freescout_thread_id' => $thread['id'],
                                'thread_type' => $thread['type'],
                                'service_vault_mapping' => [
                                    'is_internal' => $thread['type'] === 'note',
                                    'content' => $this->processCommentContent($thread, $config),
                                    'external_id' => $thread['id']
                                ]
                            ];
                        }
                    }
                }
            }

            // Get sample customers
            $customersResponse = Http::withHeaders($headers)
                ->get($baseUrl . '/api/customers', ['per_page' => $sampleSize]);
                
            if ($customersResponse->successful()) {
                $customers = $customersResponse->json()['_embedded']['customers'] ?? [];
                foreach ($customers as $customer) {
                    $preview['customers'][] = [
                        'freescout_id' => $customer['id'],
                        'name' => ($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''),
                        'email' => $customer['emails'][0]['email'] ?? null,
                        'service_vault_mapping' => [
                            'user_type' => 'account_user',
                            'account_id' => $this->determineAccountForCustomer($customer, $config),
                            'role_template' => 'Account User',
                            'external_id' => $customer['id']
                        ]
                    ];
                }
            }

            return $preview;

        } catch (Exception $e) {
            Log::error('Failed to generate import preview', [
                'profile_id' => $profile->id,
                'error' => $e->getMessage()
            ]);

            throw new Exception('Preview generation failed: ' . $e->getMessage());
        }
    }

    /**
     * Execute the actual import process.
     */
    public function executeImport(ImportProfile $profile, array $config): ImportJob
    {
        // Map sync_mode to valid import mode enum values
        $syncModeMapping = [
            'incremental' => 'append',    // Add new records only
            'full_scan' => 'sync',        // Full synchronization (replace)
            'hybrid' => 'update',         // Mix of incremental and full
            'full' => 'sync',             // Legacy full sync
            'update_only' => 'update',    // Update existing only
            'create_only' => 'append',    // Create new only
        ];
        
        $syncMode = $config['sync_mode'] ?? 'incremental';
        $mode = $syncModeMapping[$syncMode] ?? 'sync';
        
        // Create import job
        $job = ImportJob::create([
            'profile_id' => $profile->id,
            'status' => 'pending',
            'mode' => $mode,
            'mode_config' => $config,
            'started_by' => Auth::id(),
        ]);

        $job->markAsStarted();
        $job->updateProgress(0, 'Starting FreeScout import...');

        try {
            DB::transaction(function () use ($job, $profile, $config) {
                $this->processFreescoutImport($job, $profile, $config);
            });

            $job->markAsCompleted();
            
            // Comprehensive success logging
            Log::info('FreeScout import completed successfully', [
                'job_id' => $job->id,
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'user_id' => Auth::id(),
                'records_imported' => $job->records_imported,
                'records_processed' => $job->records_processed,
                'duration_seconds' => $job->started_at ? now()->diffInSeconds($job->started_at) : null,
                'config_summary' => [
                    'account_strategy' => $config['account_strategy'],
                    'agent_access' => $config['agent_access'],
                    'billing_rate_strategy' => $config['billing_rate_strategy'],
                    'sync_strategy' => $config['sync_strategy'],
                    'duplicate_detection' => $config['duplicate_detection']
                ]
            ]);

            // Broadcast completion event
            event(new ImportJobStatusChanged($job, 'completed'));

        } catch (Exception $e) {
            $job->markAsFailed($e->getMessage());
            
            // Comprehensive error logging
            Log::error('FreeScout import failed', [
                'job_id' => $job->id,
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'records_processed_before_failure' => $job->records_processed ?? 0,
                'progress_before_failure' => $job->progress ?? 0,
                'config_summary' => [
                    'account_strategy' => $config['account_strategy'],
                    'agent_access' => $config['agent_access'],
                    'billing_rate_strategy' => $config['billing_rate_strategy']
                ]
            ]);

            // Broadcast failure event
            event(new ImportJobStatusChanged($job, 'failed'));
            
            throw $e;
        }

        return $job;
    }

    /**
     * Get import job status with detailed progress information.
     */
    public function getImportJobStatus($jobId): ?array
    {
        $job = ImportJob::with(['profile'])->find($jobId);
        
        if (!$job) {
            return null;
        }

        return [
            'id' => $job->id,
            'status' => $job->status,
            'progress' => $job->progress,
            'message' => $job->status_message,
            'records_processed' => $job->records_processed,
            'records_imported' => $job->records_imported,
            'records_updated' => $job->records_updated,
            'records_skipped' => $job->records_skipped,
            'records_failed' => $job->records_failed,
            'error_log' => $job->error_log,
            'created_at' => $job->created_at,
            'completed_at' => $job->completed_at,
        ];
    }

    /**
     * Analyze relationships for import planning.
     */
    public function analyzeRelationships(ImportProfile $profile, array $config): array
    {
        // This would provide detailed relationship analysis
        // For now, return a structured analysis of the mapping strategy
        return [
            'account_strategy' => $config['account_strategy'],
            'estimated_accounts' => $this->estimateAccountsCount($profile, $config),
            'user_mapping_strategy' => [
                'agents_access' => $config['agent_access'],
                'role_templates' => ['Agent', 'Account User']
            ],
            'relationship_flow' => [
                'mailboxes' => 'accounts',
                'conversations' => 'tickets',
                'customers' => 'users (account_user)',
                'agents' => 'users (agent)',
                'threads' => 'ticket_comments',
                'time_entries' => 'time_entries'
            ]
        ];
    }

    // Private helper methods
    
    private function sampleConversationsForTimeEntries(ImportProfile $profile, int $sampleSize): array
    {
        // Implementation would sample conversations and check for time entries
        return ['avg_time_entries' => 0.2];
    }

    private function determineAccountForConversation(array $conversation, array $config): ?string
    {
        if ($config['account_strategy'] === 'map_mailboxes') {
            return ($conversation['mailbox']['name'] ?? 'Unknown') . ' Account';
        }
        
        // Domain mapping logic would go here
        return 'Mapped Account';
    }

    private function determineAccountForCustomer(array $customer, array $config): ?string
    {
        // Account determination logic based on strategy
        return 'Customer Account';
    }

    private function mapFreescoutStatus(string $freescoutStatus): string
    {
        $statusMap = [
            'active' => 'open',
            'pending' => 'pending',
            'closed' => 'closed',
            'spam' => 'closed'
        ];

        return $statusMap[$freescoutStatus] ?? 'open';
    }

    private function processCommentContent(array $thread, array $config): string
    {
        $content = $thread['body'] ?? '';
        
        if ($config['comment_processing']['add_context_prefix']) {
            $prefix = $thread['type'] === 'note' ? '[Agent Note]' : '[Imported from FreeScout]';
            $content = $prefix . ' ' . $content;
        }

        if (!$config['comment_processing']['preserve_html']) {
            $content = strip_tags($content);
        }

        return $content;
    }

    private function estimateAccountsCount(ImportProfile $profile, array $config): int
    {
        // Estimate accounts based on strategy
        if ($config['account_strategy'] === 'map_mailboxes') {
            // Would count mailboxes from API
            return 5; // Placeholder
        }
        
        return DomainMapping::where('is_active', true)->count();
    }

    private function processFreescoutImport(ImportJob $job, ImportProfile $profile, array $config): void
    {
        $apiConfig = $profile->getConnectionConfig();
        $baseUrl = $apiConfig['host'];  // API URL is stored in 'host' field
        $headers = ['X-FreeScout-API-Key' => $apiConfig['password']];  // API key is stored in 'password' field
        
        $importStats = [
            'accounts_created' => 0,
            'users_created' => 0,
            'tickets_created' => 0,
            'comments_created' => 0,
            'time_entries_created' => 0
        ];

        // Step 1: Process Accounts (based on strategy)
        $job->updateProgress(10, 'Creating accounts...');
        $this->broadcastProgress($job, 'accounts', ['step' => 'starting', 'strategy' => $config['account_strategy']]);
        
        $accountMapping = $this->processAccounts($baseUrl, $headers, $config);
        $importStats['accounts_created'] = count($accountMapping);
        
        $job->updateProgress(20, "Created {$importStats['accounts_created']} accounts");
        $this->broadcastProgress($job, 'accounts', [
            'step' => 'completed',
            'accounts_created' => $importStats['accounts_created'],
            'strategy' => $config['account_strategy']
        ]);

        // Step 2: Process Users (Agents and Customers)
        $job->updateProgress(25, 'Processing agents...');
        $this->broadcastProgress($job, 'users', ['step' => 'agents_starting', 'access_strategy' => $config['agent_access']]);
        
        $agentMapping = $this->processAgents($job, $baseUrl, $headers, $config, $accountMapping);
        
        $job->updateProgress(35, 'Processing customers...');
        $this->broadcastProgress($job, 'users', ['step' => 'customers_starting', 'agents_created' => count($agentMapping)]);
        
        $customerMapping = $this->processCustomers($job, $baseUrl, $headers, $config, $accountMapping);
        
        $importStats['users_created'] = count($agentMapping) + count($customerMapping);
        $job->updateProgress(45, "Created {$importStats['users_created']} users");
        $this->broadcastProgress($job, 'users', [
            'step' => 'completed',
            'agents_created' => count($agentMapping),
            'customers_created' => count($customerMapping),
            'total_users' => $importStats['users_created']
        ]);

        // Step 3: Process Conversations -> Tickets
        $job->updateProgress(50, 'Processing conversations as tickets...');
        $this->broadcastProgress($job, 'tickets', ['step' => 'starting']);
        
        $ticketMapping = $this->processConversations($baseUrl, $headers, $config, $accountMapping, $customerMapping);
        $importStats['tickets_created'] = count($ticketMapping);
        
        $job->updateProgress(65, "Created {$importStats['tickets_created']} tickets");
        $this->broadcastProgress($job, 'tickets', [
            'step' => 'completed',
            'tickets_created' => $importStats['tickets_created']
        ]);

        // Step 4: Process Conversation Threads -> Comments
        $job->updateProgress(70, 'Processing conversation threads as comments...');
        $this->broadcastProgress($job, 'comments', [
            'step' => 'starting',
            'preserve_html' => $config['comment_processing']['preserve_html'],
            'add_context_prefix' => $config['comment_processing']['add_context_prefix']
        ]);
        
        $commentsCreated = $this->processConversationThreads($baseUrl, $headers, $config, $ticketMapping, $agentMapping, $customerMapping);
        $importStats['comments_created'] = $commentsCreated;
        
        $job->updateProgress(85, "Created {$importStats['comments_created']} comments");
        $this->broadcastProgress($job, 'comments', [
            'step' => 'completed',
            'comments_created' => $commentsCreated
        ]);

        // Step 5: Process Time Entries
        $job->updateProgress(90, 'Processing time entries...');
        $this->broadcastProgress($job, 'time_entries', [
            'step' => 'starting',
            'billing_strategy' => $config['billing_rate_strategy'],
            'billable_default' => $config['time_entry_defaults']['billable']
        ]);
        
        $timeEntriesCreated = $this->processTimeEntries($baseUrl, $headers, $config, $ticketMapping, $agentMapping);
        $importStats['time_entries_created'] = $timeEntriesCreated;
        
        $job->updateProgress(95, "Created {$importStats['time_entries_created']} time entries");
        $this->broadcastProgress($job, 'time_entries', [
            'step' => 'completed',
            'time_entries_created' => $timeEntriesCreated
        ]);

        // Update job statistics
        $job->records_processed = array_sum($importStats);
        $job->records_imported = array_sum($importStats);
        $job->save();
        
        $job->updateProgress(100, 'FreeScout import completed successfully');
        $this->broadcastProgress($job, 'completed', [
            'step' => 'finished',
            'final_stats' => $importStats,
            'total_records' => array_sum($importStats)
        ]);
    }

    private function processAccounts(string $baseUrl, array $headers, array $config): array
    {
        $accountMapping = [];
        
        Log::info('FreescoutImportService::processAccounts starting', [
            'baseUrl' => $baseUrl,
            'account_strategy' => $config['account_strategy'],
            'excluded_mailboxes' => $config['excluded_mailboxes'] ?? []
        ]);

        if ($config['account_strategy'] === 'map_mailboxes') {
            // Get all mailboxes and create accounts
            $apiUrl = rtrim($baseUrl, '/') . '/api/mailboxes';
            Log::info('FreescoutImportService::processAccounts making API request', [
                'url' => $apiUrl,
                'headers' => array_keys($headers)
            ]);
            
            $response = Http::withHeaders($headers)->get($apiUrl);
            
            Log::info('FreescoutImportService::processAccounts API response', [
                'successful' => $response->successful(),
                'status' => $response->status(),
                'response_body' => $response->body(),
                'response_json' => $response->json()
            ]);
            
            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData === null) {
                    Log::error('FreescoutImportService::processAccounts API returned null JSON', [
                        'response_body' => $response->body(),
                        'content_type' => $response->header('Content-Type')
                    ]);
                    return $accountMapping;
                }
                
                $mailboxes = $responseData['_embedded']['mailboxes'] ?? [];
                $excludedIds = $config['excluded_mailboxes'] ?? [];
                
                Log::info('FreescoutImportService::processAccounts mailboxes found', [
                    'mailboxes_count' => count($mailboxes),
                    'excluded_count' => count($excludedIds),
                    'response_structure' => array_keys($responseData)
                ]);

                foreach ($mailboxes as $mailbox) {
                    if (in_array($mailbox['id'], $excludedIds)) {
                        continue;
                    }

                    // Check if account already exists by external_id
                    $existingAccount = Account::where('external_id', 'freescout_mailbox_' . $mailbox['id'])->first();
                    
                    if (!$existingAccount) {
                        $account = Account::create([
                            'name' => $mailbox['name'] . ' Account',
                            'description' => 'Account created from FreeScout mailbox: ' . $mailbox['name'],
                            'external_id' => 'freescout_mailbox_' . $mailbox['id'],
                            'is_active' => true
                        ]);
                        
                        Log::info('FreeScout import: Account created', [
                            'account_id' => $account->id,
                            'account_name' => $account->name,
                            'freescout_mailbox_id' => $mailbox['id'],
                            'freescout_mailbox_name' => $mailbox['name']
                        ]);
                        
                        $accountMapping['mailbox_' . $mailbox['id']] = $account->id;
                    } else {
                        Log::info('FreeScout import: Existing account found', [
                            'account_id' => $existingAccount->id,
                            'freescout_mailbox_id' => $mailbox['id']
                        ]);
                        $accountMapping['mailbox_' . $mailbox['id']] = $existingAccount->id;
                    }
                }
            } else {
                Log::error('FreescoutImportService::processAccounts API call failed', [
                    'status' => $response->status(),
                    'response_body' => $response->body(),
                    'url' => $baseUrl . '/api/mailboxes'
                ]);
            }
        } else {
            // Domain mapping strategy - use existing accounts
            $domainMappings = DomainMapping::where('is_active', true)->with('account')->get();
            foreach ($domainMappings as $mapping) {
                $accountMapping['domain_' . $mapping->id] = $mapping->account->id;
            }
        }
        
        Log::info('FreescoutImportService::processAccounts completed', [
            'account_mapping_count' => count($accountMapping),
            'account_mapping' => $accountMapping
        ]);

        return $accountMapping;
    }

    private function processAgents(ImportJob $job, string $baseUrl, array $headers, array $config, array $accountMapping): array
    {
        $agentMapping = [];
        $agentRole = RoleTemplate::where('name', 'Agent')->first();
        
        if (!$agentRole) {
            throw new Exception('Agent role template not found');
        }

        // Get FreeScout users (agents)
        $response = Http::withHeaders($headers)->get($baseUrl . '/api/users');
        
        if ($response->successful()) {
            $users = $response->json()['_embedded']['users'] ?? [];
            
            // Get list of FreeScout user IDs that should be imported as agents
            // Default to Grant and Jami Wilson (known DRIVE NW staff) if not configured
            $selectedAgentIds = $config['selected_agent_ids'] ?? [1, 2]; // Grant Wilson, Jami Wilson
            
            foreach ($users as $freescoutUser) {
                // Only process users selected as agents in the import configuration
                if (!in_array($freescoutUser['id'], $selectedAgentIds)) {
                    continue; // Skip non-selected users - they'll be processed as customers
                }
                
                // Check for existing user by external_id first, then by email+user_type composite
                $existingUser = User::where('external_id', 'freescout_user_' . $freescoutUser['id'])->first();
                
                if (!$existingUser) {
                    // Also check if user exists with same email+user_type (composite constraint)
                    $existingByEmailType = User::where('email', $freescoutUser['email'])
                        ->where('user_type', 'agent')
                        ->first();
                    
                    if ($existingByEmailType) {
                        // User exists with same email+user_type, use existing user
                        $existingUser = $existingByEmailType;
                        // Update external_id to link FreeScout record
                        if (!$existingUser->external_id) {
                            $existingUser->update(['external_id' => 'freescout_user_' . $freescoutUser['id']]);
                        }
                        $agentMapping['user_' . $freescoutUser['id']] = $existingUser->id;
                        $this->broadcastProgress($job, 'users', [
                            'step' => 'agent_found_existing',
                            'agent_email' => $freescoutUser['email'],
                            'agent_id' => $existingUser->id
                        ]);
                        continue;
                    }
                }
                
                if (!$existingUser) {
                    // Determine account assignment for agent
                    $accountId = null;
                    if ($config['agent_access'] === 'primary_account') {
                        // Create or use "FreeScout Import" account
                        $primaryAccount = Account::firstOrCreate(
                            ['name' => 'FreeScout Import'],
                            [
                                'description' => 'Primary account for imported FreeScout agents',
                                'is_active' => true
                            ]
                        );
                        $accountId = $primaryAccount->id;
                    } else {
                        // For 'all_accounts', we'll handle permissions separately
                        $accountId = null;
                    }

                    $user = User::create([
                        'name' => ($freescoutUser['firstName'] ?? '') . ' ' . ($freescoutUser['lastName'] ?? ''),
                        'email' => $freescoutUser['email'],
                        'user_type' => 'agent',
                        'account_id' => $accountId,
                        'role_template_id' => $agentRole->id,
                        'external_id' => 'freescout_user_' . $freescoutUser['id'],
                        'is_active' => true,
                        'is_visible' => true
                    ]);

                    $agentMapping['user_' . $freescoutUser['id']] = $user->id;
                } else {
                    $agentMapping['user_' . $freescoutUser['id']] = $existingUser->id;
                }
            }
        }

        return $agentMapping;
    }

    private function processCustomers(ImportJob $job, string $baseUrl, array $headers, array $config, array $accountMapping): array
    {
        $customerMapping = [];
        $customerRole = RoleTemplate::where('name', 'Account User')->first();
        
        if (!$customerRole) {
            throw new Exception('Account User role template not found');
        }

        // Get FreeScout users and filter for customers (non-selected agents)
        $response = Http::withHeaders($headers)->get($baseUrl . '/api/users');
        
        if ($response->successful()) {
            $allUsers = $response->json()['_embedded']['users'] ?? [];
            // Default to Grant and Jami Wilson (known DRIVE NW staff) if not configured
            $selectedAgentIds = $config['selected_agent_ids'] ?? [1, 2]; // Grant Wilson, Jami Wilson
            
            // Filter for users NOT selected as agents - these become customers
            $customers = array_filter($allUsers, function($user) use ($selectedAgentIds) {
                return !in_array($user['id'], $selectedAgentIds);
            });
            
            foreach ($customers as $customer) {
                // Check for existing user by external_id first, then by email+user_type composite  
                $existingUser = User::where('external_id', 'freescout_customer_' . $customer['id'])->first();
                $email = $customer['email'] ?? null; // FreeScout users have direct email field, not nested
                
                if (!$existingUser && $email) {
                    // Also check if user exists with same email+user_type (composite constraint)
                    $existingByEmailType = User::where('email', $email)
                        ->where('user_type', 'account_user')
                        ->first();
                    
                    if ($existingByEmailType) {
                        // User exists with same email+user_type, use existing user
                        $existingUser = $existingByEmailType;
                        // Update external_id to link FreeScout record
                        if (!$existingUser->external_id) {
                            $existingUser->update(['external_id' => 'freescout_customer_' . $customer['id']]);
                        }
                        $customerMapping['customer_' . $customer['id']] = $existingUser->id;
                        $this->broadcastProgress($job, 'users', [
                            'step' => 'customer_found_existing',
                            'customer_email' => $email,
                            'customer_id' => $existingUser->id
                        ]);
                        continue;
                    }
                }
                
                if (!$existingUser) {
                    $accountId = $this->determineCustomerAccount($customer, $config, $accountMapping);
                    
                    if (!$accountId) {
                        continue; // Skip if no account can be determined
                    }

                    $user = User::create([
                        'name' => trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? '')),
                        'email' => $email,
                        'user_type' => 'account_user',
                        'account_id' => $accountId,
                        'role_template_id' => $customerRole->id,
                        'external_id' => 'freescout_customer_' . $customer['id'],
                        'is_active' => true,
                        'is_visible' => true
                    ]);

                    $customerMapping['customer_' . $customer['id']] = $user->id;
                } else {
                    $customerMapping['customer_' . $customer['id']] = $existingUser->id;
                }
            }
        }

        return $customerMapping;
    }

    private function processConversations(string $baseUrl, array $headers, array $config, array $accountMapping, array $customerMapping): array
    {
        $ticketMapping = [];
        
        // Get conversations with pagination
        $page = 1;
        $limit = $config['limits']['conversations'] ?? null;
        $processed = 0;

        do {
            $response = Http::withHeaders($headers)->get($baseUrl . '/api/conversations', [
                'page' => $page,
                'per_page' => 50
            ]);

            if (!$response->successful()) {
                break;
            }

            $data = $response->json();
            $conversations = $data['_embedded']['conversations'] ?? [];

            foreach ($conversations as $conversation) {
                if ($limit && $processed >= $limit) {
                    break 2;
                }

                // Skip if mailbox is excluded
                if (isset($conversation['mailbox']['id']) && in_array($conversation['mailbox']['id'], $config['excluded_mailboxes'] ?? [])) {
                    continue;
                }

                $existingTicket = Ticket::where('external_id', 'freescout_conversation_' . $conversation['id'])->first();
                
                if (!$existingTicket) {
                    $accountId = $this->getAccountForConversation($conversation, $config, $accountMapping);
                    
                    // Skip ticket creation if no valid account can be determined
                    if (!$accountId) {
                        Log::warning('Skipping conversation - no valid account found', [
                            'conversation_id' => $conversation['id'] ?? 'unknown',
                            'has_mailbox' => isset($conversation['mailbox']),
                        ]);
                        continue;
                    }
                    
                    $customerId = $customerMapping['customer_' . $conversation['customer']['id']] ?? null;

                    $ticket = Ticket::create([
                        'title' => $conversation['subject'],
                        'description' => $conversation['preview'] ?? '',
                        'status' => $this->mapFreescoutStatus($conversation['status']),
                        'priority' => 'medium', // FreeScout doesn't have priority
                        'account_id' => $accountId,
                        'customer_id' => $customerId,
                        'external_id' => 'freescout_conversation_' . $conversation['id'],
                        'created_at' => $conversation['createdAt'] ?? null,
                        'updated_at' => $conversation['updatedAt'] ?? null
                    ]);

                    $ticketMapping['conversation_' . $conversation['id']] = $ticket->id;
                }

                $processed++;
            }

            $page++;
        } while (!empty($conversations) && (!$limit || $processed < $limit));

        return $ticketMapping;
    }

    private function processConversationThreads(string $baseUrl, array $headers, array $config, array $ticketMapping, array $agentMapping, array $customerMapping): int
    {
        $commentsCreated = 0;

        foreach ($ticketMapping as $conversationKey => $ticketId) {
            $conversationId = str_replace('conversation_', '', $conversationKey);
            
            try {
                // Get threads for this conversation
                $response = Http::withHeaders($headers)->timeout(30)->get($baseUrl . "/api/conversations/{$conversationId}/threads");
                
                if ($response->successful()) {
                    $threads = $response->json()['_embedded']['threads'] ?? [];
                    
                    foreach ($threads as $thread) {
                        try {
                            $existingComment = TicketComment::where('external_id', 'freescout_thread_' . $thread['id'])->first();
                            
                            if (!$existingComment) {
                                // Determine comment author
                                $userId = null;
                                if (($thread['createdBy']['type'] ?? null) === 'customer') {
                                    $userId = $customerMapping['customer_' . $thread['createdBy']['id']] ?? null;
                                } else {
                                    $userId = $agentMapping['user_' . $thread['createdBy']['id']] ?? null;
                                }

                                $content = $this->processCommentContent($thread, $config);
                                $isInternal = $thread['type'] === 'note';

                                TicketComment::create([
                                    'ticket_id' => $ticketId,
                                    'user_id' => $userId,
                                    'content' => $content,
                                    'is_internal' => $isInternal,
                                    'external_id' => 'freescout_thread_' . $thread['id'],
                                    'created_at' => $thread['createdAt'] ?? null,
                                    'updated_at' => $thread['updatedAt'] ?? $thread['createdAt']
                                ]);

                                $commentsCreated++;
                            }
                        } catch (Exception $e) {
                            Log::warning('FreeScout import: Failed to process thread', [
                                'conversation_id' => $conversationId,
                                'thread_id' => $thread['id'] ?? 'unknown',
                                'error' => $e->getMessage()
                            ]);
                            // Continue processing other threads
                        }
                    }
                } else {
                    Log::warning('FreeScout import: Failed to fetch threads for conversation', [
                        'conversation_id' => $conversationId,
                        'status_code' => $response->status(),
                        'response_body' => $response->body()
                    ]);
                }
            } catch (Exception $e) {
                Log::warning('FreeScout import: Failed to process conversation threads', [
                    'conversation_id' => $conversationId,
                    'error' => $e->getMessage()
                ]);
                // Continue with next conversation
            }
        }

        return $commentsCreated;
    }

    private function processTimeEntries(string $baseUrl, array $headers, array $config, array $ticketMapping, array $agentMapping): int
    {
        $timeEntriesCreated = 0;
        $limit = $config['limits']['time_entries'] ?? null;
        $processed = 0;

        // Process time entries for each conversation
        foreach ($ticketMapping as $conversationKey => $ticketId) {
            if ($limit && $processed >= $limit) {
                break;
            }

            $conversationId = str_replace('conversation_', '', $conversationKey);
            
            // Get time entries for this conversation
            $response = Http::withHeaders($headers)->get($baseUrl . "/api/conversations/{$conversationId}/time_entries");
            
            if ($response->successful()) {
                $timeEntries = $response->json()['_embedded']['time_entries'] ?? [];
                
                foreach ($timeEntries as $timeEntry) {
                    if ($limit && $processed >= $limit) {
                        break 2;
                    }

                    $existingTimeEntry = TimeEntry::where('external_id', 'freescout_time_' . $timeEntry['id'])->first();
                    
                    if (!$existingTimeEntry) {
                        $userId = $agentMapping['user_' . $timeEntry['user']['id']] ?? null;
                        $ticket = Ticket::find($ticketId);
                        
                        if ($userId && $ticket) {
                            // Convert time to minutes
                            $duration = $this->convertTimeToMinutes($timeEntry['time']);
                            
                            // Determine billing rate
                            $billingRateId = $this->determineBillingRate($config, $ticket->account_id);

                            TimeEntry::create([
                                'description' => $timeEntry['note'] ?? 'Time entry imported from FreeScout',
                                'duration' => $duration,
                                'started_at' => $timeEntry['createdAt'] ?? null,
                                'user_id' => $userId,
                                'ticket_id' => $ticketId,
                                'account_id' => $ticket->account_id,
                                'billing_rate_id' => $billingRateId,
                                'billable' => $config['time_entry_defaults']['billable'],
                                'approval_status' => $config['time_entry_defaults']['approved'] ? 'approved' : 'pending',
                                'external_id' => 'freescout_time_' . $timeEntry['id'],
                                'created_at' => $timeEntry['createdAt'] ?? null,
                                'updated_at' => $timeEntry['updatedAt'] ?? $timeEntry['createdAt']
                            ]);

                            $timeEntriesCreated++;
                        }
                    }

                    $processed++;
                }
            }
        }

        return $timeEntriesCreated;
    }

    private function determineCustomerAccount(array $customer, array $config, array $accountMapping): ?string
    {
        $email = $customer['emails'][0]['email'] ?? null;
        
        if (!$email) {
            return null;
        }

        if ($config['account_strategy'] === 'domain_mapping') {
            // Find matching domain mapping
            $domain = substr(strrchr($email, "@"), 1);
            $domainMapping = DomainMapping::where('domain_pattern', 'like', "%{$domain}%")
                ->where('is_active', true)
                ->first();
                
            if ($domainMapping) {
                return $domainMapping->account_id;
            }
            
            // Handle unmapped users based on strategy
            if ($config['unmapped_users'] === 'auto_create') {
                $account = Account::create([
                    'name' => ucfirst($domain) . ' Account',
                    'description' => 'Auto-created account for domain: ' . $domain,
                    'is_active' => true
                ]);
                return $account->id;
            } elseif ($config['unmapped_users'] === 'default_account') {
                $defaultAccount = Account::firstOrCreate(
                    ['name' => 'Imported Customers'],
                    ['description' => 'Default account for unmapped imported customers', 'is_active' => true]
                );
                return $defaultAccount->id;
            }
            
            return null; // Skip unmapped users
        }
        
        // For mailbox strategy, we need to find which conversations this customer is in
        // This is simplified - in practice we'd need to map customer to their primary mailbox
        return reset($accountMapping) ?: null;
    }

    private function getAccountForConversation(array $conversation, array $config, array $accountMapping): ?string
    {
        if ($config['account_strategy'] === 'map_mailboxes') {
            if (!isset($conversation['mailbox']['id'])) {
                Log::warning('Conversation missing mailbox data', ['conversation_id' => $conversation['id'] ?? 'unknown']);
                return null;
            }
            return $accountMapping['mailbox_' . $conversation['mailbox']['id']] ?? null;
        }
        
        // For domain mapping, determine from customer email
        $customerEmail = $conversation['customer']['email'] ?? null;
        if ($customerEmail) {
            $domain = substr(strrchr($customerEmail, "@"), 1);
            $domainMapping = DomainMapping::where('domain_pattern', 'like', "%{$domain}%")
                ->where('is_active', true)
                ->first();
                
            if ($domainMapping) {
                return $domainMapping->account_id;
            }
        }
        
        // Fallback to first available account
        return reset($accountMapping) ?: null;
    }

    private function convertTimeToMinutes(string $timeString): int
    {
        // Handle different time formats: "2.5", "2:30", etc.
        if (strpos($timeString, ':') !== false) {
            // Format like "2:30"
            [$hours, $minutes] = explode(':', $timeString);
            return (int)$hours * 60 + (int)$minutes;
        }
        
        // Decimal hours format like "2.5"
        return (int)(floatval($timeString) * 60);
    }

    private function determineBillingRate(array $config, string $accountId): ?string
    {
        if ($config['billing_rate_strategy'] === 'no_rate') {
            return null;
        }
        
        if ($config['billing_rate_strategy'] === 'fixed_rate') {
            return $config['fixed_billing_rate_id'];
        }
        
        // Auto-select: account default -> global default
        $account = Account::find($accountId);
        if ($account && $account->settings['default_billing_rate_id'] ?? null) {
            return $account->settings['default_billing_rate_id'];
        }
        
        // Fall back to global default
        $globalDefault = BillingRate::where('is_default', true)->first();
        return $globalDefault?->id;
    }

    /**
     * Broadcast import progress with detailed step information.
     */
    private function broadcastProgress(ImportJob $job, string $currentStep, array $stepDetails = []): void
    {
        try {
            event(new FreescoutImportProgressUpdated($job, $currentStep, $stepDetails));
        } catch (Exception $e) {
            // Don't let broadcasting errors interrupt the import
            Log::warning('Failed to broadcast FreeScout import progress', [
                'job_id' => $job->id,
                'error' => $e->getMessage()
            ]);
        }
    }

}