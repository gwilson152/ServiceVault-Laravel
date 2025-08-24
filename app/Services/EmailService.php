<?php

namespace App\Services;

use App\Models\Account;
use App\Models\DomainMapping;
use App\Models\EmailConfig;
use App\Models\EmailProcessingLog;
use App\Models\EmailTemplate;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class EmailService
{
    /**
     * Send an email using the best available configuration
     */
    public function sendEmail(
        string $to,
        string $subject,
        string $bodyText,
        ?string $bodyHtml = null,
        ?string $accountId = null,
        array $attachments = [],
        array $options = []
    ): bool {
        try {
            // Get the best email configuration for this account
            $emailConfig = $this->getBestEmailConfig($accountId, 'outgoing');
            
            if (!$emailConfig) {
                throw new \Exception('No email configuration available for sending');
            }

            // Create processing log entry
            $logEntry = EmailProcessingLog::create([
                'email_id' => (string) Str::uuid(),
                'account_id' => $accountId,
                'email_config_id' => $emailConfig->id,
                'direction' => 'outgoing',
                'status' => 'processing',
                'from_address' => $emailConfig->from_address,
                'to_addresses' => is_array($to) ? $to : [$to],
                'subject' => $subject,
                'parsed_body_text' => $bodyText,
                'parsed_body_html' => $bodyHtml,
                'attachments' => array_map(function($attachment) {
                    return [
                        'name' => $attachment['name'] ?? 'attachment',
                        'path' => $attachment['path'] ?? '',
                        'mime' => $attachment['mime'] ?? 'application/octet-stream',
                        'size' => $attachment['size'] ?? 0,
                    ];
                }, $attachments),
            ]);

            // Configure mail driver
            $this->configureMailDriver($emailConfig);

            // Send the email
            Mail::html($bodyHtml ?: nl2br($bodyText), function ($message) use ($to, $subject, $bodyText, $emailConfig, $attachments, $options) {
                $message->to($to)
                        ->subject($subject)
                        ->from($emailConfig->from_address, $emailConfig->from_name);

                // Add CC/BCC if specified
                if (!empty($options['cc'])) {
                    $message->cc($options['cc']);
                }
                if (!empty($options['bcc'])) {
                    $message->bcc($options['bcc']);
                }

                // Add attachments
                foreach ($attachments as $attachment) {
                    if (isset($attachment['path']) && file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], [
                            'as' => $attachment['name'] ?? basename($attachment['path']),
                            'mime' => $attachment['mime'] ?? null,
                        ]);
                    }
                }
            });

            // Mark as processed
            $logEntry->markAsProcessed(['email_sent' => true]);

            return true;

        } catch (\Exception $e) {
            if (isset($logEntry)) {
                $logEntry->markAsFailed($e->getMessage(), [
                    'exception_class' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ], $e->getTraceAsString());
            }

            Log::error('Failed to send email', [
                'error' => $e->getMessage(),
                'to' => $to,
                'subject' => $subject,
                'account_id' => $accountId,
            ]);

            return false;
        }
    }

    /**
     * Send email using a template
     */
    public function sendTemplatedEmail(
        string $templateType,
        string $to,
        array $variables = [],
        ?string $accountId = null,
        array $attachments = [],
        array $options = []
    ): bool {
        try {
            // Find the best template
            $template = EmailTemplate::getBestTemplate($templateType, $accountId);
            
            if (!$template) {
                throw new \Exception("No template found for type: {$templateType}");
            }

            // Prepare variables with system defaults
            $variables = $this->prepareTemplateVariables($variables, $accountId);

            // Render the template
            $rendered = $template->render($variables);

            return $this->sendEmail(
                $to,
                $rendered['subject'],
                $rendered['body_text'],
                $rendered['body_html'],
                $accountId,
                $attachments,
                $options
            );

        } catch (\Exception $e) {
            Log::error('Failed to send templated email', [
                'error' => $e->getMessage(),
                'template_type' => $templateType,
                'to' => $to,
                'account_id' => $accountId,
            ]);

            return false;
        }
    }

    /**
     * Process incoming email
     */
    public function processIncomingEmail(array $emailData): EmailProcessingLog
    {
        // Determine account from sender domain
        $senderDomain = $this->extractDomainFromEmail($emailData['from'] ?? '');
        $domainMapping = DomainMapping::findMatchingDomain($emailData['from'] ?? '');
        $accountId = $domainMapping?->account_id;

        // Create processing log
        $logEntry = EmailProcessingLog::createFromEmailData($emailData, $accountId);

        try {
            $logEntry->markAsProcessing();

            // Parse subject for commands
            $commands = $this->parseSubjectCommands($emailData['subject'] ?? '');

            // Determine if this is a reply to existing ticket
            $ticket = $this->findTicketFromEmail($emailData);

            $actionsTaken = [];

            if ($ticket) {
                // This is a reply to an existing ticket
                $actionsTaken[] = $this->handleTicketReply($ticket, $emailData, $commands);
            } else {
                // This is a new ticket
                $ticket = $this->createTicketFromEmail($emailData, $accountId, $commands);
                $actionsTaken[] = [
                    'action' => 'ticket_created',
                    'ticket_id' => $ticket->id,
                    'ticket_number' => $ticket->ticket_number,
                ];
            }

            // Execute any additional commands
            foreach ($commands as $command) {
                $commandResult = $this->executeEmailCommand($command, $ticket, $emailData);
                if ($commandResult) {
                    $actionsTaken[] = $commandResult;
                }
            }

            // Update log entry
            $logEntry->update([
                'ticket_id' => $ticket->id,
                'created_new_ticket' => !$logEntry->ticket_id,
            ]);

            $logEntry->markAsProcessed($actionsTaken);

            // Send confirmation email if requested
            if (!empty($commands)) {
                $this->sendCommandConfirmation($emailData['from'], $commands, $ticket, $accountId);
            }

        } catch (\Exception $e) {
            $logEntry->markAsFailed($e->getMessage(), [
                'exception_class' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ], $e->getTraceAsString());

            // Send error notification
            $this->sendCommandError($emailData['from'] ?? '', $e->getMessage(), $accountId);
        }

        return $logEntry;
    }

    /**
     * Parse subject line for commands
     */
    public function parseSubjectCommands(string $subject): array
    {
        $commands = [];

        // Pattern to match commands like: time:45, priority:high, status:resolved
        preg_match_all('/(\w+):([^\s,]+)/', $subject, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $command = strtolower($match[1]);
            $value = trim($match[2]);

            $commands[] = [
                'command' => $command,
                'value' => $value,
                'original' => $match[0],
            ];
        }

        return $commands;
    }

    /**
     * Execute a single email command
     */
    public function executeEmailCommand(array $command, Ticket $ticket, array $emailData): ?array
    {
        try {
            $commandName = $command['command'];
            $value = $command['value'];

            switch ($commandName) {
                case 'time':
                    return $this->addTimeEntry($ticket, $value, $emailData);

                case 'priority':
                    return $this->updateTicketPriority($ticket, $value);

                case 'status':
                    return $this->updateTicketStatus($ticket, $value);

                case 'assign':
                    return $this->assignTicket($ticket, $value);

                case 'due':
                    return $this->setTicketDueDate($ticket, $value);

                case 'category':
                    return $this->updateTicketCategory($ticket, $value);

                default:
                    return [
                        'action' => 'unknown_command',
                        'command' => $commandName,
                        'result' => 'Command not recognized',
                    ];
            }

        } catch (\Exception $e) {
            return [
                'action' => 'command_error',
                'command' => $command['command'],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Add time entry from email command
     */
    private function addTimeEntry(Ticket $ticket, string $minutes, array $emailData): array
    {
        $user = $this->findUserByEmail($emailData['from']);
        $minutesInt = (int) $minutes;
        
        if ($minutesInt <= 0 || $minutesInt > 1440) { // Max 24 hours
            throw new \Exception("Invalid time duration: {$minutes} minutes");
        }

        $timeEntry = \App\Models\TimeEntry::create([
            'account_id' => $ticket->account_id,
            'ticket_id' => $ticket->id,
            'user_id' => $user?->id,
            'description' => 'Time added via email command',
            'duration' => $minutesInt,
            'started_at' => now()->subMinutes($minutesInt),
            'ended_at' => now(),
            'billable' => true,
            'status' => 'pending',
        ]);

        return [
            'action' => 'time_added',
            'minutes' => $minutes,
            'time_entry_id' => $timeEntry->id,
            'duration_formatted' => floor($minutesInt / 60) . 'h ' . ($minutesInt % 60) . 'm',
        ];
    }

    /**
     * Update ticket priority
     */
    private function updateTicketPriority(Ticket $ticket, string $priority): array
    {
        $validPriorities = ['low', 'normal', 'medium', 'high', 'urgent'];
        
        if (!in_array(strtolower($priority), $validPriorities)) {
            throw new \Exception("Invalid priority: {$priority}");
        }

        $ticket->update(['priority' => strtolower($priority)]);

        return [
            'action' => 'priority_updated',
            'old_priority' => $ticket->getOriginal('priority'),
            'new_priority' => $priority,
        ];
    }

    /**
     * Update ticket status
     */
    private function updateTicketStatus(Ticket $ticket, string $status): array
    {
        $validStatuses = ['open', 'in_progress', 'waiting_customer', 'on_hold', 'resolved', 'closed'];
        
        if (!in_array(strtolower($status), $validStatuses)) {
            throw new \Exception("Invalid status: {$status}");
        }

        $oldStatus = $ticket->status;
        $ticket->update(['status' => strtolower($status)]);

        return [
            'action' => 'status_updated',
            'old_status' => $oldStatus,
            'new_status' => $status,
        ];
    }

    /**
     * Assign ticket to agent by email
     */
    private function assignTicket(Ticket $ticket, string $agentEmail): array
    {
        $agent = User::where('email', $agentEmail)->first();
        
        if (!$agent) {
            throw new \Exception("Agent not found: {$agentEmail}");
        }

        $ticket->assignTo($agent);

        return [
            'action' => 'ticket_assigned',
            'agent_email' => $agentEmail,
            'agent_name' => $agent->name,
        ];
    }

    /**
     * Set ticket due date
     */
    private function setTicketDueDate(Ticket $ticket, string $dateString): array
    {
        try {
            $dueDate = \Carbon\Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Invalid date format: {$dateString}");
        }

        $ticket->update(['due_date' => $dueDate]);

        return [
            'action' => 'due_date_set',
            'due_date' => $dueDate->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Update ticket category
     */
    private function updateTicketCategory(Ticket $ticket, string $category): array
    {
        $validCategories = ['support', 'maintenance', 'development', 'consulting', 'other'];
        
        if (!in_array(strtolower($category), $validCategories)) {
            throw new \Exception("Invalid category: {$category}");
        }

        $ticket->update(['category' => strtolower($category)]);

        return [
            'action' => 'category_updated',
            'old_category' => $ticket->getOriginal('category'),
            'new_category' => $category,
        ];
    }

    /**
     * Get the best email configuration for an account and direction
     */
    private function getBestEmailConfig(?string $accountId, string $direction = 'outgoing'): ?EmailConfig
    {
        if ($accountId) {
            return EmailConfig::getBestConfigForAccount($accountId, $direction);
        }

        return EmailConfig::getDefaultConfig($direction);
    }

    /**
     * Configure mail driver with email configuration
     */
    private function configureMailDriver(EmailConfig $emailConfig): void
    {
        $config = $emailConfig->getMailConfig();
        
        config(['mail.mailers.dynamic' => $config]);
        config(['mail.default' => 'dynamic']);
    }

    /**
     * Prepare template variables with system defaults
     */
    private function prepareTemplateVariables(array $variables, ?string $accountId): array
    {
        $systemVariables = [
            'current_date' => now()->format('M j, Y'),
            'current_time' => now()->format('g:i A'),
            'system_name' => 'Service Vault',
        ];

        if ($accountId) {
            $account = Account::find($accountId);
            if ($account) {
                $systemVariables['account_name'] = $account->name;
                $systemVariables['account_email'] = $account->email;
            }
        }

        return array_merge($systemVariables, $variables);
    }

    /**
     * Extract domain from email address
     */
    private function extractDomainFromEmail(string $email): string
    {
        $parts = explode('@', $email);
        return strtolower(end($parts));
    }

    /**
     * Find existing ticket from email data
     */
    private function findTicketFromEmail(array $emailData): ?Ticket
    {
        $subject = $emailData['subject'] ?? '';
        
        // Look for ticket number in subject (format: TKT-YYYY-XXXX)
        if (preg_match('/TKT-\d{4}-\d{4}/', $subject, $matches)) {
            return Ticket::where('ticket_number', $matches[0])->first();
        }

        // Look for ticket number in references or in-reply-to headers
        $messageId = $emailData['in_reply_to'] ?? '';
        if ($messageId) {
            return EmailProcessingLog::where('message_id', $messageId)
                                   ->whereNotNull('ticket_id')
                                   ->first()?->ticket;
        }

        return null;
    }

    /**
     * Create new ticket from email
     */
    private function createTicketFromEmail(array $emailData, ?string $accountId, array $commands = []): Ticket
    {
        $fromEmail = $this->extractEmailAddress($emailData['from'] ?? '');
        $customer = User::where('email', $fromEmail)->first();

        $ticketData = [
            'account_id' => $accountId,
            'title' => $this->cleanSubjectForTitle($emailData['subject'] ?? 'Email Support Request'),
            'description' => $emailData['body_text'] ?? '',
            'customer_email' => $fromEmail,
            'customer_name' => $this->extractNameFromEmail($emailData['from'] ?? ''),
            'customer_id' => $customer?->id,
            'status' => 'open',
            'priority' => 'normal',
            'category' => 'support',
        ];

        // Apply any priority/category from commands
        foreach ($commands as $command) {
            if ($command['command'] === 'priority' && in_array($command['value'], ['low', 'normal', 'medium', 'high', 'urgent'])) {
                $ticketData['priority'] = $command['value'];
            } elseif ($command['command'] === 'category' && in_array($command['value'], ['support', 'maintenance', 'development', 'consulting', 'other'])) {
                $ticketData['category'] = $command['value'];
            }
        }

        return Ticket::create($ticketData);
    }

    /**
     * Handle reply to existing ticket
     */
    private function handleTicketReply(Ticket $ticket, array $emailData, array $commands): array
    {
        // Add comment from email body
        $ticket->comments()->create([
            'user_id' => $this->findUserByEmail($emailData['from'])?->id,
            'content' => $emailData['body_text'] ?? '',
            'is_internal' => false,
            'author_name' => $this->extractNameFromEmail($emailData['from'] ?? ''),
            'author_email' => $this->extractEmailAddress($emailData['from'] ?? ''),
        ]);

        return [
            'action' => 'comment_added',
            'ticket_id' => $ticket->id,
            'comment_added' => true,
        ];
    }

    /**
     * Send command confirmation email
     */
    private function sendCommandConfirmation(string $to, array $commands, Ticket $ticket, ?string $accountId): void
    {
        $commandsText = implode(', ', array_map(fn($cmd) => $cmd['original'], $commands));
        
        $this->sendTemplatedEmail('command_confirmation', $to, [
            'command' => $commandsText,
            'result' => 'Commands executed successfully',
            'ticket_number' => $ticket->ticket_number,
        ], $accountId);
    }

    /**
     * Send command error email
     */
    private function sendCommandError(string $to, string $error, ?string $accountId): void
    {
        $this->sendTemplatedEmail('command_error', $to, [
            'command' => 'Email processing',
            'result' => $error,
        ], $accountId);
    }

    /**
     * Helper methods
     */
    private function findUserByEmail(string $email): ?User
    {
        return User::where('email', $this->extractEmailAddress($email))->first();
    }

    private function extractEmailAddress(string $emailString): string
    {
        if (preg_match('/<(.+)>/', $emailString, $matches)) {
            return strtolower(trim($matches[1]));
        }
        return strtolower(trim($emailString));
    }

    private function extractNameFromEmail(string $emailString): ?string
    {
        if (preg_match('/^(.+?)\s*</', $emailString, $matches)) {
            return trim($matches[1], '"\'');
        }
        return null;
    }

    private function cleanSubjectForTitle(string $subject): string
    {
        // Remove Re:, Fw:, etc.
        $subject = preg_replace('/^(Re|Fw|Fwd):\s*/i', '', $subject);
        // Remove commands from title
        $subject = preg_replace('/\w+:\w+/', '', $subject);
        return trim($subject) ?: 'Email Support Request';
    }
}