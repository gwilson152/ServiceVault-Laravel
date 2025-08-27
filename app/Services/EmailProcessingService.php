<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DomainMapping;
use App\Models\EmailDomainMapping;
use App\Models\EmailProcessingLog;
use App\Models\EmailSystemConfig;
use App\Models\Ticket;
use App\Models\User;
use App\Services\IncomingEmailHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmailProcessingService
{
    private IncomingEmailHandler $emailHandler;

    public function __construct(IncomingEmailHandler $emailHandler)
    {
        $this->emailHandler = $emailHandler;
    }

    /**
     * Process an email through the complete pipeline
     */
    public function processEmail(array $emailData, bool $createTicket = true): array
    {
        Log::info('Processing email through unified pipeline', [
            'email_id' => $emailData['email_id'] ?? 'unknown',
            'from' => $emailData['from'] ?? 'unknown',
            'subject' => $emailData['subject'] ?? 'No Subject',
            'create_ticket' => $createTicket,
        ]);

        $result = [
            'success' => false,
            'actions_taken' => [],
            'ticket_created' => false,
            'user_created' => false,
            'account_assigned' => null,
            'processing_log_id' => null,
            'error' => null,
        ];

        try {
            // Step 1: Create processing log entry
            $processingLog = $this->createProcessingLog($emailData);
            $result['processing_log_id'] = $processingLog->id;

            if (!$createTicket) {
                // Test mode - just log and return
                $result['success'] = true;
                $result['actions_taken'][] = 'Email logged for testing';
                return $result;
            }

            // Step 2: Resolve domain and account
            $domainResolution = $this->resolveDomainAndAccount($emailData);
            $result['account_assigned'] = $domainResolution['account_id'];

            // Step 3: Handle user creation if needed
            $userResult = $this->handleUserCreation($emailData, $domainResolution);
            $result['user_created'] = $userResult['user_created'];
            $result['actions_taken'] = array_merge($result['actions_taken'], $userResult['actions']);

            // Step 4: Create ticket if configured
            if ($domainResolution['should_create_ticket']) {
                $ticketResult = $this->createTicketFromEmail($emailData, $domainResolution, $userResult['user']);
                $result['ticket_created'] = $ticketResult['success'];
                $result['actions_taken'] = array_merge($result['actions_taken'], $ticketResult['actions']);
                
                // Update processing log with ticket info
                $processingLog->update([
                    'ticket_id' => $ticketResult['ticket']->id ?? null,
                    'created_new_ticket' => $ticketResult['success'],
                    'account_id' => $domainResolution['account_id'],
                    'status' => 'processed',
                    'actions_taken' => $result['actions_taken'],
                ]);
            }

            $result['success'] = true;
            Log::info('Email processing completed successfully', $result);

        } catch (\Exception $e) {
            Log::error('Email processing failed', [
                'error' => $e->getMessage(),
                'email_data' => $emailData,
                'trace' => $e->getTraceAsString(),
            ]);

            $result['error'] = $e->getMessage();
            
            // Update processing log with error
            if (isset($processingLog)) {
                $processingLog->markAsFailed($e->getMessage(), [
                    'processing_stage' => 'unified_pipeline',
                    'actions_taken' => $result['actions_taken'],
                ]);
            }
        }

        return $result;
    }

    /**
     * Create processing log entry
     */
    private function createProcessingLog(array $emailData): EmailProcessingLog
    {
        return EmailProcessingLog::createFromEmailData($emailData);
    }

    /**
     * Resolve domain mapping and determine account assignment
     */
    private function resolveDomainAndAccount(array $emailData): array
    {
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        $domain = $this->extractDomain($fromEmail);

        Log::debug('Resolving domain and account', [
            'from_email' => $fromEmail,
            'domain' => $domain,
        ]);

        // Priority 1: Check EmailDomainMapping for exact email routing
        $emailDomainMapping = EmailDomainMapping::findMatchingMapping($fromEmail);
        if ($emailDomainMapping && $emailDomainMapping->is_active) {
            return [
                'account_id' => $emailDomainMapping->account_id,
                'should_create_ticket' => $emailDomainMapping->auto_create_tickets,
                'mapping_type' => 'email_domain_mapping',
                'mapping' => $emailDomainMapping,
                'assigned_user_id' => $emailDomainMapping->default_assigned_user_id,
            ];
        }

        // Priority 2: Check general DomainMapping for user creation
        $domainMapping = DomainMapping::findByDomain($domain);
        if ($domainMapping && $domainMapping->is_active) {
            return [
                'account_id' => $domainMapping->account_id,
                'should_create_ticket' => true, // Default to creating tickets
                'mapping_type' => 'domain_mapping',
                'mapping' => $domainMapping,
                'role_template_id' => $domainMapping->role_template_id,
            ];
        }

        // Priority 3: Use system default configuration
        $emailConfig = EmailSystemConfig::getConfig();
        $defaultStrategy = $this->getUnmappedDomainStrategy($emailConfig);

        switch ($defaultStrategy['strategy']) {
            case 'create_account':
                $accountId = $this->createAccountForDomain($domain);
                return [
                    'account_id' => $accountId,
                    'should_create_ticket' => $emailConfig->auto_create_tickets,
                    'mapping_type' => 'auto_created_account',
                    'created_account' => true,
                ];

            case 'assign_default_account':
                return [
                    'account_id' => $defaultStrategy['default_account_id'],
                    'should_create_ticket' => $emailConfig->auto_create_tickets,
                    'mapping_type' => 'default_account',
                ];

            case 'queue_for_review':
                // TODO: Implement email queuing system
                throw new \Exception('Email queued for manual domain mapping review (not yet implemented)');

            case 'reject':
            default:
                throw new \Exception("No domain mapping found for {$domain} and rejection strategy is configured");
        }
    }

    /**
     * Handle user creation if needed
     */
    private function handleUserCreation(array $emailData, array $domainResolution): array
    {
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        $fromName = $this->extractNameFromAddress($emailData['from'] ?? '');
        
        $result = [
            'user_created' => false,
            'user' => null,
            'actions' => [],
        ];

        // Check if user already exists
        $existingUser = User::where('email', $fromEmail)->first();
        if ($existingUser) {
            $result['user'] = $existingUser;
            return $result;
        }

        // Check if we should create users automatically
        $emailConfig = EmailSystemConfig::getConfig();
        if (!$this->shouldAutoCreateUser($emailConfig, $domainResolution)) {
            throw new \Exception("User {$fromEmail} not found and auto user creation is disabled");
        }

        // Create new user
        $userData = [
            'name' => $fromName ?: $this->generateNameFromEmail($fromEmail),
            'email' => $fromEmail,
            'password' => bcrypt(Str::random(32)), // Random password
            'email_verified_at' => now(),
            'account_id' => $domainResolution['account_id'],
        ];

        $newUser = User::create($userData);
        
        // Assign role if specified in domain mapping
        if (isset($domainResolution['role_template_id'])) {
            // TODO: Implement role assignment logic
        }

        $result['user_created'] = true;
        $result['user'] = $newUser;
        $result['actions'][] = "Created new user: {$fromEmail}";

        Log::info('Auto-created user from email', [
            'email' => $fromEmail,
            'user_id' => $newUser->id,
            'account_id' => $domainResolution['account_id'],
        ]);

        return $result;
    }

    /**
     * Create ticket from email
     */
    private function createTicketFromEmail(array $emailData, array $domainResolution, ?User $user): array
    {
        try {
            $ticket = $this->emailHandler->createTicketFromEmail($emailData, $domainResolution['account_id']);
            
            return [
                'success' => true,
                'ticket' => $ticket,
                'actions' => ["Created ticket: {$ticket->ticket_number}"],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'ticket' => null,
                'actions' => ["Failed to create ticket: {$e->getMessage()}"],
            ];
        }
    }

    /**
     * Get unmapped domain strategy configuration
     */
    private function getUnmappedDomainStrategy(EmailSystemConfig $config): array
    {
        return [
            'strategy' => $config->unmapped_domain_strategy ?? 'reject',
            'default_account_id' => $config->default_account_id,
        ];
    }

    /**
     * Create account for new domain
     */
    private function createAccountForDomain(string $domain): string
    {
        $accountName = $this->generateAccountNameFromDomain($domain);
        
        $account = Account::create([
            'name' => $accountName,
            'account_type' => 'customer',
            'email' => "contact@{$domain}",
            'website' => "https://{$domain}",
            'created_from_email' => true,
        ]);

        Log::info('Auto-created account from email domain', [
            'domain' => $domain,
            'account_id' => $account->id,
            'account_name' => $accountName,
        ]);

        return $account->id;
    }

    /**
     * Check if we should auto-create users
     */
    private function shouldAutoCreateUser(EmailSystemConfig $config, array $domainResolution): bool
    {
        // Check if auto user creation is enabled and we have an account to assign them to
        return ($config->auto_create_users ?? false) && !empty($domainResolution['account_id']);
    }

    /**
     * Extract email address from address string
     */
    private function extractEmailAddress(string $address): string
    {
        if (preg_match('/<(.+?)>/', $address, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return strtolower(trim($address));
    }

    /**
     * Extract name from address string
     */
    private function extractNameFromAddress(string $address): ?string
    {
        if (preg_match('/^(.*?)\s*<.+?>$/', $address, $matches)) {
            return trim($matches[1], '"\'');
        }
        return null;
    }

    /**
     * Extract domain from email address
     */
    private function extractDomain(string $email): string
    {
        return substr(strrchr($email, "@"), 1);
    }

    /**
     * Generate name from email address
     */
    private function generateNameFromEmail(string $email): string
    {
        $localPart = substr($email, 0, strpos($email, '@'));
        return ucwords(str_replace(['.', '_', '-'], ' ', $localPart));
    }

    /**
     * Generate account name from domain
     */
    private function generateAccountNameFromDomain(string $domain): string
    {
        // Remove common prefixes and suffixes
        $name = preg_replace('/^(www\.|mail\.)/', '', $domain);
        $name = preg_replace('/\.(com|org|net|gov|edu|co\.uk)$/', '', $name);
        
        return ucwords(str_replace(['.', '-', '_'], ' ', $name));
    }
}