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
        $domainMappings = DomainMapping::where('is_active', true)->with('account')->get();

        if ($domainMappings->isEmpty()) {
            return [
                'success' => false,
                'error' => 'No active domain mappings found. You need to create domain mappings first.',
                'suggestion' => 'Go to Settings > Email > Domain Mappings to create domain patterns that map email domains to accounts.',
                'mappings_count' => 0,
                'warning' => 'Domain mapping strategy requires existing domain mappings. Please configure domain mappings or choose a different account strategy.'
            ];
        }

        return [
            'success' => true,
            'mappings_count' => $domainMappings->count(),
            'mappings' => $domainMappings->map(fn($dm) => [
                'pattern' => $dm->domain_pattern,
                'account' => $dm->account->name ?? 'Unknown Account'
            ])->toArray()
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
                $accountStrategy = $config['account_strategy'] ?? 'domain_mapping_strict';
                if ($accountStrategy === 'mailbox_per_account') {
                    $estimates['accounts'] = count($includedMailboxes);
                } elseif ($accountStrategy === 'single_account') {
                    $estimates['accounts'] = 1;
                } elseif (in_array($accountStrategy, ['domain_mapping', 'domain_mapping_strict'])) {
                    // Uses existing domain mappings - count mapped accounts
                    $estimates['accounts'] = DomainMapping::where('is_active', true)
                        ->distinct('account_id')
                        ->count('account_id');
                } else {
                    $estimates['accounts'] = count($includedMailboxes);
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

            // Estimate time entries - automatically imported with conversations
            $sampleConversations = $this->sampleConversationsForTimeEntries($profile, 5);
            $avgTimeEntriesPerConversation = $sampleConversations['avg_time_entries'] ?? 0.2;
            $estimates['time_entries'] = intval($estimates['conversations'] * $avgTimeEntriesPerConversation);

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
            'mailboxes' => [],
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
                        'email' => $customer['_embedded']['emails'][0]['value'] ?? null,
                        'service_vault_mapping' => [
                            'user_type' => 'account_user',
                            'account_id' => $this->determineAccountForCustomer($customer, $config),
                            'role_template' => 'Account User',
                            'external_id' => $customer['id']
                        ]
                    ];
                }
            }

            // Get mailboxes with conversation counts
            $mailboxesResponse = Http::withHeaders($headers)->get($baseUrl . '/api/mailboxes');
            if ($mailboxesResponse->successful()) {
                $mailboxes = $mailboxesResponse->json()['_embedded']['mailboxes'] ?? [];
                foreach ($mailboxes as $mailbox) {
                    $preview['mailboxes'][] = [
                        'id' => $mailbox['id'],
                        'name' => $mailbox['name'],
                        'email' => $mailbox['email'],
                        'conversation_count' => $mailbox['conversation_count'] ?? 0
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
     * Execute the actual import process (synchronous - deprecated).
     * 
     * @deprecated Use executeImportAsync() instead
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
            $this->processFreescoutImport($job, $profile, $config);

            $job->markAsCompleted();
            
            // Comprehensive success logging
            Log::info('FreeScout import completed successfully', [
                'job_id' => $job->id,
                'profile_id' => $profile->id,
                'profile_name' => $profile->name,
                'user_id' => Auth::id(),
                'records_imported' => $job->records_imported,
                'records_processed' => $job->records_processed,
                'duration_seconds' => $job->started_at ? $job->started_at->diffInSeconds(now()) : null,
                'config_summary' => [
                    'account_strategy' => $config['account_strategy'],
                    'agent_role_strategy' => $config['agent_role_strategy'] ?? 'standard_agent',
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
                    'agent_role_strategy' => $config['agent_role_strategy'] ?? 'standard_agent',
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
     * Execute the import process asynchronously via job queue.
     */
    public function executeImportAsync(ImportProfile $profile, array $config): ImportJob
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

        // Dispatch the import job to the queue
        \App\Jobs\ProcessFreescoutImportJob::dispatch($job, $profile, $config);

        Log::info('FreeScout import job queued', [
            'job_id' => $job->id,
            'profile_id' => $profile->id,
            'profile_name' => $profile->name,
            'user_id' => Auth::id()
        ]);

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
                'agent_role_strategy' => $config['agent_role_strategy'] ?? 'standard_agent',
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
        $email = $customer['_embedded']['emails'][0]['value'] ?? null;
        
        if (!$email) {
            return 'No Account (missing email)';
        }

        if ($config['account_strategy'] === 'domain_mapping') {
            $domain = substr(strrchr($email, "@"), 1);
            $domainMapping = DomainMapping::where('domain', 'like', "%{$domain}%")
                ->where('is_active', true)
                ->first();
                
            if ($domainMapping) {
                return $domainMapping->account->name ?? 'Mapped Account';
            }
            
            return 'Default Account (unmapped domain)';
        } else {
            // map_mailboxes strategy - will be determined during import
            return 'Account from Mailbox';
        }
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

    public function processFreescoutImport(ImportJob $job, ImportProfile $profile, array $config): void
    {
        $apiConfig = $profile->getConnectionConfig();
        $baseUrl = rtrim($apiConfig['host'], '/');
        $headers = ['X-FreeScout-API-Key' => $apiConfig['password']];
        
        // Initialize import tracking
        $importStats = [
            'accounts_created' => 0,
            'users_created' => 0, 
            'customers_created' => 0,
            'tickets_created' => 0,
            'comments_created' => 0,
            'time_entries_created' => 0,
            'records_skipped' => 0,
            'validation_errors' => []
        ];
        
        // Validate domain mapping configuration before starting import
        $accountStrategy = $config['account_strategy'] ?? 'mailbox_per_account';
        if (in_array($accountStrategy, ['domain_mapping', 'domain_mapping_strict'])) {
            $domainValidation = $this->validateDomainMappings();
            if (!$domainValidation['success']) {
                $job->markAsFailed([
                    'error' => $domainValidation['error'],
                    'suggestion' => $domainValidation['suggestion'] ?? null,
                    'warning' => $domainValidation['warning'] ?? null
                ]);
                return;
            }
        }

        // Track needed and imported entities for dependency resolution
        $dependencyTracker = [
            'accounts' => [],      // mailbox_id => account_id (imported accounts)
            'agents' => [],        // freescout_user_id => service_vault_user_id (imported agents)
            'customers' => [],     // freescout_customer_id => service_vault_user_id (imported customers)
            'tickets' => [],       // freescout_conversation_id => service_vault_ticket_id (imported tickets)
            'needed_mailboxes' => [],    // Track mailboxes that need to be imported
            'needed_agents' => [],       // Track users that need to be imported as agents
            'needed_customers' => []     // Track customers that need to be imported
        ];

        try {
            $job->updateProgress(10, 'Analyzing conversations in date range...');
            
            // Step 1: Find conversations in date range and identify dependencies
            $conversationsToImport = $this->findConversationsInDateRange($baseUrl, $headers, $config, $dependencyTracker, $importStats);
            
            if (empty($conversationsToImport)) {
                $job->updateProgress(100, 'No conversations found in specified date range');
                return;
            }
            
            Log::info('Found conversations to import', [
                'count' => count($conversationsToImport),
                'date_range' => $config['date_range'] ?? 'all',
                'needed_mailboxes' => count($dependencyTracker['needed_mailboxes']),
                'needed_agents' => count($dependencyTracker['needed_agents']),
                'needed_customers' => count($dependencyTracker['needed_customers'])
            ]);
            
            // Step 2: Import conversations with just-in-time dependency creation
            $job->updateProgress(25, 'Importing conversations with dependencies...');
            $this->importConversationsWithDependencies($job, $conversationsToImport, $baseUrl, $headers, $config, $importStats);
            
            // Finalize import statistics
            $totalCreated = array_sum(array_filter($importStats, 'is_numeric'));
                          
            $job->update([
                'records_processed' => $totalCreated + $importStats['records_skipped'],
                'records_imported' => $totalCreated,
                'records_skipped' => $importStats['records_skipped'],
                'summary' => $importStats
            ]);
            
            $job->updateProgress(100, 'FreeScout import completed successfully');
            
        } catch (Exception $e) {
            Log::error('FreeScout import failed', [
                'job_id' => $job->id,
                'profile_id' => $profile->id,
                'error' => $e->getMessage(),
                'partial_stats' => $importStats
            ]);
            throw $e;
        }
    }

    /**
     * Find conversations within date range and identify all dependencies
     */
    private function findConversationsInDateRange(string $baseUrl, array $headers, array $config, array &$dependencyTracker, array &$importStats): array
    {
        $conversations = [];
        $dateRange = $config['date_range'] ?? [];
        $page = 1;
        
        try {
            do {
                $params = ['page' => $page, 'per_page' => 100];
                
                // Add date filtering if specified
                if (!empty($dateRange['start_date'])) {
                    $params['created_since'] = $dateRange['start_date'];
                }
                if (!empty($dateRange['end_date'])) {
                    $params['created_until'] = $dateRange['end_date'];
                }
                
                $response = Http::withHeaders($headers)->get("$baseUrl/api/conversations", $params);
                
                if (!$response->successful()) {
                    throw new Exception("Failed to fetch conversations: " . $response->status());
                }
                
                $data = $response->json();
                $pageConversations = $data['_embedded']['conversations'] ?? [];
                
                foreach ($pageConversations as $conversation) {
                    // Skip conversations that already exist (check by external_id)
                    if (Ticket::where('external_id', "freescout_conversation_{$conversation['id']}")->exists()) {
                        continue;
                    }
                    
                    $conversations[] = $conversation;
                    
                    // Track needed mailbox
                    if (isset($conversation['mailbox']['id'])) {
                        $dependencyTracker['needed_mailboxes'][$conversation['mailbox']['id']] = $conversation['mailbox'];
                    }
                    
                    // Track needed customer
                    if (isset($conversation['customer']['id'])) {
                        $dependencyTracker['needed_customers'][$conversation['customer']['id']] = $conversation['customer'];
                    }
                    
                    // Track needed agent (if assigned)
                    if (isset($conversation['user']['id'])) {
                        $dependencyTracker['needed_agents'][$conversation['user']['id']] = $conversation['user'];
                    }
                }
                
                $page++;
            } while (!empty($pageConversations));
            
            Log::info('Found conversations for import', [
                'total_conversations' => count($conversations),
                'unique_mailboxes' => count($dependencyTracker['needed_mailboxes']),
                'unique_customers' => count($dependencyTracker['needed_customers']),
                'unique_agents' => count($dependencyTracker['needed_agents']),
                'date_range' => $dateRange
            ]);
            
            return $conversations;
            
        } catch (Exception $e) {
            Log::error('Failed to find conversations in date range', ['error' => $e->getMessage()]);
            if (!($config['continue_on_error'] ?? true)) {
                throw $e;
            }
            return [];
        }
    }
    
    /**
     * Import only the mailboxes that are needed for the conversations
     */
    private function importRequiredMailboxes(string $baseUrl, array $headers, array $config, array &$dependencyTracker, array &$importStats): void
    {
        $accountStrategy = $config['account_strategy'] ?? 'mailbox_per_account';
        
        if ($accountStrategy === 'single_account') {
            // Create single account for all data
            $account = Account::firstOrCreate(
                ['external_id' => 'freescout_single_account'],
                [
                    'name' => 'FreeScout Import Account',
                    'description' => 'Consolidated account for all imported FreeScout data'
                ]
            );
            
            // Map all needed mailboxes to this single account
            foreach ($dependencyTracker['needed_mailboxes'] as $mailboxId => $mailbox) {
                $dependencyTracker['accounts'][$mailboxId] = $account->id;
            }
            
            $importStats['accounts_created'] = $account->wasRecentlyCreated ? 1 : 0;
            
        } elseif (in_array($accountStrategy, ['domain_mapping', 'domain_mapping_strict'])) {
            // Domain mapping strategies - don't create accounts, use existing mappings
            // Account assignment will be handled during customer/conversation import
            $importStats['accounts_created'] = 0;
            
            Log::info('Using domain mapping strategy - no accounts created, will use existing mapped accounts', [
                'strategy' => $accountStrategy,
                'needed_mailboxes' => count($dependencyTracker['needed_mailboxes'])
            ]);
            
        } else {
            // Create one account per needed mailbox (mailbox_per_account)
            foreach ($dependencyTracker['needed_mailboxes'] as $mailboxId => $mailbox) {
                $externalId = "freescout_mailbox_{$mailboxId}";
                
                $account = Account::firstOrCreate(
                    ['external_id' => $externalId],
                    [
                        'name' => $mailbox['name'] ?? "Mailbox {$mailboxId}",
                        'description' => "Account created from FreeScout mailbox: " . ($mailbox['name'] ?? $mailboxId)
                    ]
                );
                
                $dependencyTracker['accounts'][$mailboxId] = $account->id;
                
                if ($account->wasRecentlyCreated) {
                    $importStats['accounts_created']++;
                }
            }
        }
        
        Log::info('Imported required mailboxes', [
            'accounts_created' => $importStats['accounts_created'],
            'strategy' => $accountStrategy
        ]);
    }
    
    /**
     * Import only the agents that are needed for the conversations  
     */
    private function importRequiredAgents(string $baseUrl, array $headers, array $config, array &$dependencyTracker, array &$importStats): void
    {
        if (empty($dependencyTracker['needed_agents'])) {
            return;
        }
        
        $agentRole = RoleTemplate::where('name', 'Agent')->first();
        if (!$agentRole) {
            $importStats['validation_errors'][] = 'Agent role template not found - skipping agent import';
            return;
        }
        
        foreach ($dependencyTracker['needed_agents'] as $userId => $userInfo) {
            try {
                // Fetch full user details from FreeScout
                $response = Http::withHeaders($headers)->get("$baseUrl/api/users/$userId");
                
                if (!$response->successful()) {
                    $importStats['validation_errors'][] = "Failed to fetch user $userId from FreeScout API";
                    continue;
                }
                
                $freescoutUser = $response->json();
                
                if (empty($freescoutUser['email'])) {
                    $importStats['validation_errors'][] = "User $userId missing required email - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                $externalId = "freescout_user_{$userId}";
                
                // Check if user already exists
                $user = User::where('external_id', $externalId)->first();
                if (!$user) {
                    // Create new agent
                    $user = User::create([
                        'first_name' => $freescoutUser['firstName'] ?? '',
                        'last_name' => $freescoutUser['lastName'] ?? '',
                        'email' => $freescoutUser['email'],
                        'user_type' => 'agent',
                        'external_id' => $externalId,
                        'password' => bcrypt(str()->random(16))
                    ]);
                    
                    // Assign Agent role
                    $user->roleTemplates()->attach($agentRole->id);
                    $importStats['users_created']++;
                }
                
                $dependencyTracker['agents'][$userId] = $user->id;
                
            } catch (Exception $e) {
                $importStats['validation_errors'][] = "Failed to import user $userId: " . $e->getMessage();
                $importStats['records_skipped']++;
                
                if (!($config['continue_on_error'] ?? true)) {
                    throw $e;
                }
            }
        }
        
        Log::info('Imported required agents', ['agents_created' => $importStats['users_created']]);
    }
    
    /**
     * Import only the customers that are needed for the conversations
     */
    private function importRequiredCustomers(string $baseUrl, array $headers, array $config, array &$dependencyTracker, array &$importStats): void
    {
        if (empty($dependencyTracker['needed_customers'])) {
            return;
        }
        
        $accountUserRole = RoleTemplate::where('name', 'Account User')->first();
        if (!$accountUserRole) {
            $importStats['validation_errors'][] = 'Account User role template not found - skipping customer import';
            return;
        }
        
        foreach ($dependencyTracker['needed_customers'] as $customerId => $customerInfo) {
            try {
                // Fetch full customer details from FreeScout
                $response = Http::withHeaders($headers)->get("$baseUrl/api/customers/$customerId");
                
                if (!$response->successful()) {
                    $importStats['validation_errors'][] = "Failed to fetch customer $customerId from FreeScout API";
                    continue;
                }
                
                $freescoutCustomer = $response->json();
                $email = $freescoutCustomer['_embedded']['emails'][0]['value'] ?? null;
                
                if (empty($email)) {
                    $importStats['validation_errors'][] = "Customer $customerId missing required email - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Handle domain mapping strategies
                $accountStrategy = $config['account_strategy'] ?? 'mailbox_per_account';
                if (in_array($accountStrategy, ['domain_mapping', 'domain_mapping_strict'])) {
                    $domain = substr(strrchr($email, "@"), 1);
                    $domainMapping = DomainMapping::where('is_active', true)
                        ->where('domain_pattern', 'like', "%{$domain}%")
                        ->first();
                    
                    if (!$domainMapping) {
                        if ($accountStrategy === 'domain_mapping_strict') {
                            // Skip customers without domain mapping in strict mode
                            $importStats['validation_errors'][] = "Customer $customerId email domain '$domain' not mapped - skipped (strict mode)";
                            $importStats['records_skipped']++;
                            continue;
                        } else {
                            // Log warning for unmapped domain but continue
                            $importStats['validation_errors'][] = "Customer $customerId email domain '$domain' not mapped - will need manual assignment";
                        }
                    }
                }
                
                $externalId = "freescout_customer_{$customerId}";
                
                // Check if customer already exists
                $user = User::where('external_id', $externalId)->first();
                if (!$user) {
                    // Create new customer user
                    $user = User::create([
                        'first_name' => $freescoutCustomer['firstName'] ?? '',
                        'last_name' => $freescoutCustomer['lastName'] ?? '',
                        'email' => $email,
                        'user_type' => 'account_user',
                        'external_id' => $externalId,
                        'password' => bcrypt(str()->random(16))
                    ]);
                    
                    // Assign Account User role
                    $user->roleTemplates()->attach($accountUserRole->id);
                    
                    // Assign to account based on domain mapping if available
                    if (isset($domainMapping) && $domainMapping) {
                        $user->accounts()->attach($domainMapping->account_id);
                    }
                    
                    $importStats['customers_created']++;
                }
                
                $dependencyTracker['customers'][$customerId] = $user->id;
                
            } catch (Exception $e) {
                $importStats['validation_errors'][] = "Failed to import customer $customerId: " . $e->getMessage();
                $importStats['records_skipped']++;
                
                if (!($config['continue_on_error'] ?? true)) {
                    throw $e;
                }
            }
        }
        
        Log::info('Imported required customers', ['customers_created' => $importStats['customers_created']]);
    }
    
    /**
     * Import the conversations as tickets
     */
    private function importConversationsAsTickets(ImportJob $job, array $conversations, array $config, array &$dependencyTracker, array &$importStats): void
    {
        $totalConversations = count($conversations);
        $processedCount = 0;
        
        foreach ($conversations as $conversation) {
            $processedCount++;
            
            // Update progress every 10 conversations or for the last conversation
            if ($processedCount % 10 === 0 || $processedCount === $totalConversations) {
                $progressPercentage = 70 + (($processedCount / $totalConversations) * 15); // 70% to 85%
                $job->updateProgress($progressPercentage, "Importing conversations as tickets... ({$processedCount}/{$totalConversations})");
            }
            try {
                if (empty($conversation['subject'])) {
                    $importStats['validation_errors'][] = "Conversation {$conversation['id']} missing required subject - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Determine account ID based on strategy
                $accountStrategy = $config['account_strategy'] ?? 'mailbox_per_account';
                $accountId = null;
                
                if (in_array($accountStrategy, ['domain_mapping', 'domain_mapping_strict'])) {
                    // Use domain mapping to determine account
                    $customerEmail = $conversation['customer']['email'] ?? null;
                    if ($customerEmail) {
                        $domain = substr(strrchr($customerEmail, "@"), 1);
                        $domainMapping = DomainMapping::where('is_active', true)
                            ->where('domain_pattern', 'like', "%{$domain}%")
                            ->first();
                        
                        if ($domainMapping) {
                            $accountId = $domainMapping->account_id;
                        } elseif ($accountStrategy === 'domain_mapping_strict') {
                            // Skip conversation without domain mapping in strict mode
                            $importStats['validation_errors'][] = "Conversation {$conversation['id']} - customer domain '$domain' not mapped - skipped (strict mode)";
                            $importStats['records_skipped']++;
                            continue;
                        } else {
                            // Non-strict mode: fallback to mailbox mapping when domain mapping fails
                            $accountId = $dependencyTracker['accounts'][$conversation['mailbox']['id']] ?? null;
                            if ($accountId) {
                                $importStats['validation_errors'][] = "Conversation {$conversation['id']} - domain '$domain' not mapped, using mailbox fallback";
                            }
                        }
                    }
                } else {
                    // Use mailbox mapping for other strategies
                    $accountId = $dependencyTracker['accounts'][$conversation['mailbox']['id']] ?? null;
                }
                
                if (!$accountId) {
                    $importStats['validation_errors'][] = "Conversation {$conversation['id']} - unable to determine account - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Get customer and agent mappings
                $customerId = isset($conversation['customer']['id']) 
                    ? ($dependencyTracker['customers'][$conversation['customer']['id']] ?? null) 
                    : null;
                $agentId = isset($conversation['user']['id']) 
                    ? ($dependencyTracker['agents'][$conversation['user']['id']] ?? null) 
                    : null;
                
                // Create ticket
                $ticket = Ticket::create([
                    'account_id' => $accountId,
                    'title' => $conversation['subject'],
                    'description' => $conversation['preview'] ?? '',
                    'status' => $this->mapFreescoutStatus($conversation['status']),
                    'priority' => $this->mapFreescoutPriority($conversation['type'] ?? 'email'),
                    'agent_id' => $agentId,
                    'customer_id' => $customerId,
                    'customer_email' => $conversation['customer']['email'] ?? null,
                    'external_id' => "freescout_conversation_{$conversation['id']}",
                    'created_by_id' => Auth::id()
                ]);
                
                $dependencyTracker['tickets'][$conversation['id']] = $ticket->id;
                $importStats['tickets_created']++;
                
            } catch (Exception $e) {
                $importStats['validation_errors'][] = "Failed to import conversation {$conversation['id']}: " . $e->getMessage();
                $importStats['records_skipped']++;
                
                if (!($config['continue_on_error'] ?? true)) {
                    throw $e;
                }
            }
        }
        
        Log::info('Imported conversations as tickets', ['tickets_created' => $importStats['tickets_created']]);
    }
    
    /**
     * Import conversations with just-in-time dependency creation
     */
    private function importConversationsWithDependencies(ImportJob $job, array $conversations, string $baseUrl, array $headers, array $config, array &$importStats): void
    {
        $totalConversations = count($conversations);
        $processedCount = 0;
        $accountsCreated = [];
        $agentsCreated = [];
        $customersCreated = [];
        $ticketsCreated = [];
        
        foreach ($conversations as $conversation) {
            $processedCount++;
            
            // Update progress outside any transactions
            if ($processedCount % 10 === 0 || $processedCount === $totalConversations) {
                $progressPercentage = 25 + (($processedCount / $totalConversations) * 70); // 25% to 95%
                $job->updateProgress($progressPercentage, "Processing conversation {$processedCount}/{$totalConversations}...");
            }
            
            try {
                // Validate conversation has required fields
                if (empty($conversation['subject'])) {
                    $importStats['validation_errors'][] = "Conversation {$conversation['id']} missing required subject - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Step 1: Determine account based on domain mapping strategy
                $accountId = $this->determineOrCreateAccount($conversation, $config, $accountsCreated, $importStats);
                if (!$accountId) {
                    continue; // Already logged and counted in determineOrCreateAccount
                }
                
                // Step 2: Handle customer creation/matching
                $customerId = $this->createOrFindCustomer($conversation, $config, $customersCreated, $importStats);
                
                // Step 3: Handle agent creation/matching based on import strategy
                $agentId = $this->handleAgentImport($conversation, $config, $baseUrl, $headers, $agentsCreated, $importStats);
                
                // Step 4: Create the ticket in its own transaction
                $ticketId = null;
                DB::transaction(function () use ($conversation, $accountId, $customerId, $agentId, &$ticketId, &$importStats) {
                    $ticket = Ticket::create([
                        'account_id' => $accountId,
                        'title' => $conversation['subject'],
                        'description' => $conversation['preview'] ?? '',
                        'status' => $this->mapFreescoutStatus($conversation['status']),
                        'priority' => $this->mapFreescoutPriority($conversation['type'] ?? 'email'),
                        'agent_id' => $agentId,
                        'customer_id' => $customerId,
                        'customer_email' => $conversation['customer']['email'] ?? null,
                        'external_id' => "freescout_conversation_{$conversation['id']}",
                        'created_by_id' => Auth::id()
                    ]);
                    
                    $ticketId = $ticket->id;
                    $importStats['tickets_created']++;
                });
                
                $ticketsCreated[$conversation['id']] = $ticketId;
                
                // Step 5: Import threads and time entries for this specific conversation
                $this->importThreadsForConversation($conversation['id'], $ticketId, $baseUrl, $headers, $config, $importStats);
                $this->importTimeEntriesForConversation($conversation['id'], $ticketId, $baseUrl, $headers, $config, $importStats);
                
            } catch (Exception $e) {
                $importStats['validation_errors'][] = "Failed to import conversation {$conversation['id']}: " . $e->getMessage();
                $importStats['records_skipped']++;
                
                if (!($config['continue_on_error'] ?? true)) {
                    throw $e;
                }
            }
        }
        
        // Update final statistics
        $importStats['accounts_created'] = count($accountsCreated);
        $importStats['users_created'] = count($agentsCreated);
        $importStats['customers_created'] = count($customersCreated);
        
        Log::info('Imported conversations with dependencies', [
            'conversations_processed' => $processedCount,
            'tickets_created' => $importStats['tickets_created'],
            'accounts_created' => $importStats['accounts_created'],
            'agents_created' => $importStats['users_created'],
            'customers_created' => $importStats['customers_created']
        ]);
    }
    
    /**
     * Determine or create account based on domain mapping strategy
     */
    private function determineOrCreateAccount(array $conversation, array $config, array &$accountsCreated, array &$importStats): ?string
    {
        $accountStrategy = $config['account_strategy'] ?? 'domain_mapping_strict';
        $customerEmail = $conversation['customer']['email'] ?? null;
        
        if (in_array($accountStrategy, ['domain_mapping', 'domain_mapping_strict'])) {
            if (!$customerEmail) {
                $importStats['validation_errors'][] = "Conversation {$conversation['id']} - no customer email for domain mapping - skipped";
                $importStats['records_skipped']++;
                return null;
            }
            
            $domain = substr(strrchr($customerEmail, "@"), 1);
            $domainMapping = DomainMapping::where('is_active', true)
                ->where('domain_pattern', 'like', "%{$domain}%")
                ->first();
            
            if ($domainMapping) {
                return $domainMapping->account_id;
            }
            
            if ($accountStrategy === 'domain_mapping_strict') {
                // Skip conversation without domain mapping in strict mode
                $importStats['validation_errors'][] = "Conversation {$conversation['id']} - customer domain '$domain' not mapped - skipped (strict mode)";
                $importStats['records_skipped']++;
                return null;
            }
            
            // Fallback mode: create account based on customer information
            return $this->createAccountFromCustomer($conversation['customer'], $accountsCreated, $importStats);
            
        } elseif ($accountStrategy === 'single_account') {
            // Find or create the single account
            $account = Account::firstOrCreate(
                ['name' => 'FreeScout Import Account'],
                ['description' => 'Single account for all FreeScout imported data']
            );
            
            if ($account->wasRecentlyCreated) {
                $accountsCreated[] = $account->id;
            }
            
            return $account->id;
            
        } elseif ($accountStrategy === 'mailbox_per_account') {
            // Create account based on mailbox
            $mailboxName = $conversation['mailbox']['name'] ?? 'Unknown Mailbox';
            $account = Account::firstOrCreate(
                ['name' => $mailboxName],
                ['description' => "Account for FreeScout mailbox: {$mailboxName}"]
            );
            
            if ($account->wasRecentlyCreated) {
                $accountsCreated[] = $account->id;
            }
            
            return $account->id;
        }
        
        return null;
    }
    
    /**
     * Create account based on customer information
     */
    private function createAccountFromCustomer(array $customer, array &$accountsCreated, array &$importStats): string
    {
        $customerName = trim(($customer['firstName'] ?? '') . ' ' . ($customer['lastName'] ?? ''));
        if (empty($customerName)) {
            $customerName = $customer['email'] ?? 'Unknown Customer';
        }
        
        // Create account name from customer info
        $accountName = !empty($customer['organization']) 
            ? $customer['organization'] 
            : $customerName . ' Account';
        
        $account = Account::firstOrCreate(
            ['name' => $accountName],
            ['description' => "Customer-based account for: {$customerName}"]
        );
        
        if ($account->wasRecentlyCreated) {
            $accountsCreated[] = $account->id;
        }
        
        return $account->id;
    }
    
    /**
     * Create or find customer user
     */
    private function createOrFindCustomer(array $conversation, array $config, array &$customersCreated, array &$importStats): ?string
    {
        $customer = $conversation['customer'] ?? null;
        if (!$customer || empty($customer['email'])) {
            return null;
        }
        
        $externalId = "freescout_customer_{$customer['id']}";
        
        // Check if customer already exists
        $user = User::where('external_id', $externalId)->first();
        if (!$user) {
            DB::transaction(function () use ($customer, $externalId, &$user, &$customersCreated, &$importStats) {
                $user = User::create([
                    'first_name' => $customer['firstName'] ?? '',
                    'last_name' => $customer['lastName'] ?? '',
                    'email' => $customer['email'],
                    'user_type' => 'account_user',
                    'external_id' => $externalId,
                    'created_by_id' => Auth::id()
                ]);
                
                $customersCreated[] = $user->id;
                $importStats['customers_created']++;
            });
        }
        
        return $user->id;
    }
    
    /**
     * Handle agent import based on strategy
     */
    private function handleAgentImport(array $conversation, array $config, string $baseUrl, array $headers, array &$agentsCreated, array &$importStats): ?string
    {
        $agentImportStrategy = $config['agent_import_strategy'] ?? 'skip';
        $freescoutUser = $conversation['user'] ?? null;
        
        if (!$freescoutUser || empty($freescoutUser['email'])) {
            return null; // No agent to import/match
        }
        
        switch ($agentImportStrategy) {
            case 'create_new':
                return $this->createNewAgent($freescoutUser, $baseUrl, $headers, $agentsCreated, $importStats);
                
            case 'match_existing':
                return $this->matchExistingAgent($freescoutUser, $importStats);
                
            case 'skip':
            default:
                return null; // No agent assignment
        }
    }
    
    /**
     * Create new agent from FreeScout user
     */
    private function createNewAgent(array $freescoutUser, string $baseUrl, array $headers, array &$agentsCreated, array &$importStats): ?string
    {
        $externalId = "freescout_user_{$freescoutUser['id']}";
        
        // Check if agent already exists
        $user = User::where('external_id', $externalId)->first();
        if ($user) {
            return $user->id;
        }
        
        // Get the Agent role template
        $agentRole = RoleTemplate::where('name', 'Agent')->first();
        if (!$agentRole) {
            $importStats['validation_errors'][] = "Agent role template not found - cannot create agent {$freescoutUser['id']}";
            return null;
        }
        
        // Fetch full user details from FreeScout API if needed
        try {
            $response = Http::withHeaders($headers)->get("{$baseUrl}/api/users/{$freescoutUser['id']}");
            if ($response->successful()) {
                $fullUserData = $response->json();
            } else {
                $fullUserData = $freescoutUser; // Use basic data if API call fails
            }
        } catch (Exception $e) {
            $fullUserData = $freescoutUser; // Use basic data if API call fails
        }
        
        $user = null;
        DB::transaction(function () use ($fullUserData, $externalId, $agentRole, &$user, &$agentsCreated, &$importStats) {
            $user = User::create([
                'first_name' => $fullUserData['first_name'] ?? $fullUserData['firstName'] ?? '',
                'last_name' => $fullUserData['last_name'] ?? $fullUserData['lastName'] ?? '',
                'email' => $fullUserData['email'],
                'user_type' => 'agent',
                'role_template_id' => $agentRole->id,
                'external_id' => $externalId,
                'created_by_id' => Auth::id()
            ]);
            
            $agentsCreated[] = $user->id;
            $importStats['users_created']++;
        });
        
        return $user->id;
    }
    
    /**
     * Match existing agent by email
     */
    private function matchExistingAgent(array $freescoutUser, array &$importStats): ?string
    {
        $email = $freescoutUser['email'];
        
        // Look for existing agent with matching email
        $existingAgent = User::where('email', $email)
            ->where('user_type', 'agent')
            ->first();
            
        if ($existingAgent) {
            return $existingAgent->id;
        }
        
        // Log when no matching agent is found
        $importStats['validation_errors'][] = "No existing agent found with email '{$email}' - conversation will be unassigned";
        
        return null;
    }
    
    /**
     * Import threads for a specific conversation
     */
    private function importThreadsForConversation(int $conversationId, string $ticketId, string $baseUrl, array $headers, array $config, array &$importStats): void
    {
        try {
            $response = Http::withHeaders($headers)->get("$baseUrl/api/conversations/$conversationId/threads");
            
            if (!$response->successful()) {
                return; // Some conversations may not have threads
            }
            
            $threads = $response->json()['_embedded']['threads'] ?? [];
            
            foreach ($threads as $thread) {
                // Skip if already imported
                $externalId = "freescout_thread_{$thread['id']}";
                if (TicketComment::where('external_id', $externalId)->exists()) {
                    continue;
                }
                
                DB::transaction(function () use ($thread, $ticketId, $externalId, &$importStats) {
                    TicketComment::create([
                        'ticket_id' => $ticketId,
                        'comment' => $thread['body'] ?? '',
                        'is_internal' => ($thread['type'] ?? 'message') === 'note',
                        'external_id' => $externalId,
                        'created_by_id' => Auth::id(),
                        'created_at' => $thread['created_at'] ?? now()
                    ]);
                    
                    $importStats['comments_created']++;
                });
            }
        } catch (Exception $e) {
            $importStats['validation_errors'][] = "Failed to import threads for conversation {$conversationId}: " . $e->getMessage();
            
            if (!($config['continue_on_error'] ?? true)) {
                throw $e;
            }
        }
    }
    
    /**
     * Import time entries for a specific conversation
     */
    private function importTimeEntriesForConversation(int $conversationId, string $ticketId, string $baseUrl, array $headers, array $config, array &$importStats): void
    {
        try {
            $response = Http::withHeaders($headers)->get("$baseUrl/api/conversations/$conversationId/time-tracking");
            
            if (!$response->successful()) {
                return; // Some conversations may not have time entries
            }
            
            $timeEntries = $response->json()['_embedded']['time_entries'] ?? [];
            
            foreach ($timeEntries as $timeEntry) {
                // Skip if already imported
                $externalId = "freescout_time_entry_{$timeEntry['id']}";
                if (TimeEntry::where('external_id', $externalId)->exists()) {
                    continue;
                }
                
                DB::transaction(function () use ($timeEntry, $ticketId, $externalId, &$importStats) {
                    TimeEntry::create([
                        'ticket_id' => $ticketId,
                        'description' => $timeEntry['description'] ?? 'Imported from FreeScout',
                        'duration' => intval($timeEntry['time_spent'] ?? 0), // Convert to minutes
                        'started_at' => $timeEntry['created_at'] ?? now(),
                        'billable' => true, // Default to billable
                        'external_id' => $externalId,
                        'user_id' => Auth::id(),
                        'created_by_id' => Auth::id()
                    ]);
                    
                    $importStats['time_entries_created']++;
                });
            }
        } catch (Exception $e) {
            $importStats['validation_errors'][] = "Failed to import time entries for conversation {$conversationId}: " . $e->getMessage();
            
            if (!($config['continue_on_error'] ?? true)) {
                throw $e;
            }
        }
    }
    
    /**
     * Import threads and time entries for all imported tickets
     */
    private function importThreadsAndTimeEntries(ImportJob $job, string $baseUrl, array $headers, array $config, array &$dependencyTracker, array &$importStats): void
    {
        foreach ($dependencyTracker['tickets'] as $conversationId => $ticketId) {
            // Import threads for this conversation
            $this->importThreadsForTicket($baseUrl, $headers, $conversationId, $ticketId, $config, $dependencyTracker, $importStats);
            
            // Import time entries for this conversation (using natural dates)
            $this->importTimeEntriesForTicket($baseUrl, $headers, $conversationId, $ticketId, $config, $dependencyTracker, $importStats);
        }
    }
    
    /**
     * Import threads for a specific ticket
     */
    private function importThreadsForTicket(string $baseUrl, array $headers, int $conversationId, string $ticketId, array $config, array &$dependencyTracker, array &$importStats): void
    {
        try {
            $response = Http::withHeaders($headers)->get("$baseUrl/api/conversations/$conversationId/threads");
            
            if (!$response->successful()) {
                return; // Some conversations may not have threads
            }
            
            $threads = $response->json()['_embedded']['threads'] ?? [];
            
            foreach ($threads as $thread) {
                // Skip if already imported
                if (TicketComment::where('external_id', "freescout_thread_{$thread['id']}")->exists()) {
                    continue;
                }
                
                if (empty($thread['body'])) {
                    $importStats['validation_errors'][] = "Thread {$thread['id']} missing required body - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Determine author
                $authorId = null;
                if (isset($thread['createdBy'])) {
                    if ($thread['createdBy']['type'] === 'user' && isset($thread['createdBy']['id'])) {
                        $authorId = $dependencyTracker['agents'][$thread['createdBy']['id']] ?? null;
                    } elseif ($thread['createdBy']['type'] === 'customer' && isset($thread['createdBy']['id'])) {
                        $authorId = $dependencyTracker['customers'][$thread['createdBy']['id']] ?? null;
                    }
                }
                
                if (!$authorId) {
                    $authorId = Auth::id(); // Fallback to current user
                }
                
                // Create comment
                TicketComment::create([
                    'ticket_id' => $ticketId,
                    'user_id' => $authorId,
                    'content' => $thread['body'],
                    'is_internal' => ($thread['type'] ?? '') === 'note',
                    'external_id' => "freescout_thread_{$thread['id']}",
                    'created_at' => isset($thread['createdAt']) ? \Carbon\Carbon::parse($thread['createdAt']) : now()
                ]);
                
                $importStats['comments_created']++;
            }
            
        } catch (Exception $e) {
            if ($config['continue_on_error'] ?? true) {
                $importStats['validation_errors'][] = "Failed to import threads for conversation $conversationId: " . $e->getMessage();
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Import time entries for a specific ticket (using their natural dates)
     */
    private function importTimeEntriesForTicket(string $baseUrl, array $headers, int $conversationId, string $ticketId, array $config, array &$dependencyTracker, array &$importStats): void
    {
        try {
            $response = Http::withHeaders($headers)->get("$baseUrl/api/conversations/$conversationId/time-entries");
            
            if (!$response->successful()) {
                return; // Not all conversations have time entries
            }
            
            $timeEntries = $response->json()['_embedded']['time-entries'] ?? [];
            
            foreach ($timeEntries as $timeEntry) {
                // Skip if already imported (don't re-import existing time entries)
                if (TimeEntry::where('external_id', "freescout_time_entry_{$timeEntry['id']}")->exists()) {
                    continue;
                }
                
                if (empty($timeEntry['time']) || empty($timeEntry['user']['id'])) {
                    $importStats['validation_errors'][] = "Time entry {$timeEntry['id']} missing required fields - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Find the user who created this time entry
                $userId = $dependencyTracker['agents'][$timeEntry['user']['id']] ?? null;
                if (!$userId) {
                    $importStats['validation_errors'][] = "Time entry {$timeEntry['id']} - user not found in agent mapping - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Get the ticket to determine account
                $ticket = Ticket::find($ticketId);
                if (!$ticket) {
                    $importStats['validation_errors'][] = "Time entry {$timeEntry['id']} - ticket not found - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Convert time from seconds to minutes
                $durationMinutes = (int) ($timeEntry['time'] / 60);
                
                if ($durationMinutes < 1) {
                    $importStats['validation_errors'][] = "Time entry {$timeEntry['id']} - duration too short (< 1 minute) - skipped";
                    $importStats['records_skipped']++;
                    continue;
                }
                
                // Use the time entry's natural creation date
                $startedAt = isset($timeEntry['created_at']) 
                    ? \Carbon\Carbon::parse($timeEntry['created_at'])
                    : now();
                
                // Create time entry with natural date
                TimeEntry::create([
                    'user_id' => $userId,
                    'account_id' => $ticket->account_id,
                    'ticket_id' => $ticketId,
                    'description' => $timeEntry['note'] ?? 'Time entry imported from FreeScout',
                    'duration' => $durationMinutes,
                    'started_at' => $startedAt,
                    'billable' => true,
                    'external_id' => "freescout_time_entry_{$timeEntry['id']}"
                ]);
                
                $importStats['time_entries_created']++;
            }
            
        } catch (Exception $e) {
            if ($config['continue_on_error'] ?? true) {
                $importStats['validation_errors'][] = "Failed to import time entries for conversation $conversationId: " . $e->getMessage();
            } else {
                throw $e;
            }
        }
    }
}

