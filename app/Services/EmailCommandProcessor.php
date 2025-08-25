<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\TimeEntry;
use App\Models\TicketStatus;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\EmailProcessingLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class EmailCommandProcessor
{
    private array $supportedCommands = [
        'time' => 'Add time entry (minutes)',
        'priority' => 'Set ticket priority',
        'status' => 'Update ticket status', 
        'assign' => 'Assign ticket to agent',
        'due' => 'Set due date',
        'category' => 'Set ticket category',
        'billing' => 'Set billing rate',
        'tag' => 'Add tags to ticket',
        'close' => 'Close ticket',
        'reopen' => 'Reopen ticket',
    ];

    private array $executionResults = [];
    private int $commandsExecuted = 0;
    private int $commandsFailed = 0;

    /**
     * Process and execute all commands from email subject
     */
    public function processCommands(
        array $commands, 
        Ticket $ticket, 
        User $user, 
        array $emailData,
        EmailProcessingLog $processingLog
    ): array {
        $this->executionResults = [];
        $this->commandsExecuted = 0;
        $this->commandsFailed = 0;

        Log::info('Processing email commands', [
            'email_id' => $processingLog->email_id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'commands_count' => count($commands),
            'commands' => array_map(fn($c) => $c['original'], $commands),
        ]);

        DB::beginTransaction();
        
        try {
            foreach ($commands as $command) {
                $result = $this->executeCommand($command, $ticket, $user, $emailData, $processingLog);
                $this->executionResults[] = $result;
                
                if ($result['success']) {
                    $this->commandsExecuted++;
                } else {
                    $this->commandsFailed++;
                }
            }

            // Update processing log with command results
            $processingLog->update([
                'commands_processed' => count($commands),
                'commands_executed' => $this->commandsExecuted,
                'commands_failed' => $this->commandsFailed,
                'command_results' => $this->executionResults,
            ]);

            DB::commit();

            return [
                'success' => true,
                'commands_processed' => count($commands),
                'commands_executed' => $this->commandsExecuted,
                'commands_failed' => $this->commandsFailed,
                'results' => $this->executionResults,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Command processing failed', [
                'email_id' => $processingLog->email_id,
                'error' => $e->getMessage(),
                'commands' => $commands,
            ]);

            return [
                'success' => false,
                'error' => 'Command processing failed: ' . $e->getMessage(),
                'results' => $this->executionResults,
            ];
        }
    }

    /**
     * Execute a single command with validation and permissions
     */
    private function executeCommand(
        array $command, 
        Ticket $ticket, 
        User $user, 
        array $emailData,
        EmailProcessingLog $processingLog
    ): array {
        $commandName = $command['command'];
        $value = $command['value'];
        $original = $command['original'];

        try {
            // Check if command is supported
            if (!isset($this->supportedCommands[$commandName])) {
                return $this->commandResult($original, false, 'Unknown command', [
                    'available_commands' => array_keys($this->supportedCommands)
                ]);
            }

            // Check user permissions for this command
            if (!$this->checkCommandPermission($commandName, $user, $ticket)) {
                return $this->commandResult($original, false, 'Permission denied', [
                    'required_permission' => $this->getRequiredPermission($commandName)
                ]);
            }

            // Validate command value
            $validation = $this->validateCommandValue($commandName, $value, $ticket);
            if (!$validation['valid']) {
                return $this->commandResult($original, false, $validation['error'], $validation);
            }

            // Execute the command
            $result = $this->executeSpecificCommand($commandName, $value, $ticket, $user, $emailData);

            // Log successful command execution
            $this->logCommandExecution($commandName, $value, $ticket, $user, $result, $processingLog);

            return $this->commandResult($original, true, 'Command executed successfully', $result);

        } catch (\Exception $e) {
            Log::error('Command execution failed', [
                'command' => $original,
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->commandResult($original, false, $e->getMessage());
        }
    }

    /**
     * Check if user has permission to execute command
     */
    private function checkCommandPermission(string $commandName, User $user, Ticket $ticket): bool
    {
        // Super admins can execute all commands
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Check specific command permissions
        return match ($commandName) {
            'time' => $user->hasPermission('time_entries.create'),
            'priority', 'status', 'category' => $user->hasPermission('tickets.edit'),
            'assign' => $user->hasPermission('tickets.assign'),
            'due' => $user->hasPermission('tickets.edit'),
            'billing' => $user->hasPermission('billing.manage'),
            'tag' => $user->hasPermission('tickets.edit'),
            'close', 'reopen' => $user->hasPermission('tickets.edit'),
            default => false,
        };
    }

    /**
     * Get required permission for command
     */
    private function getRequiredPermission(string $commandName): string
    {
        return match ($commandName) {
            'time' => 'time_entries.create',
            'priority', 'status', 'category', 'due', 'tag', 'close', 'reopen' => 'tickets.edit',
            'assign' => 'tickets.assign',
            'billing' => 'billing.manage',
            default => 'unknown',
        };
    }

    /**
     * Validate command value
     */
    private function validateCommandValue(string $commandName, string $value, Ticket $ticket): array
    {
        return match ($commandName) {
            'time' => $this->validateTimeValue($value),
            'priority' => $this->validatePriorityValue($value),
            'status' => $this->validateStatusValue($value),
            'assign' => $this->validateAssignValue($value, $ticket),
            'due' => $this->validateDueValue($value),
            'category' => $this->validateCategoryValue($value),
            'billing' => $this->validateBillingValue($value),
            'tag' => $this->validateTagValue($value),
            'close', 'reopen' => ['valid' => true],
            default => ['valid' => false, 'error' => 'Unknown command validation'],
        };
    }

    /**
     * Execute specific command
     */
    private function executeSpecificCommand(
        string $commandName, 
        string $value, 
        Ticket $ticket, 
        User $user, 
        array $emailData
    ): array {
        return match ($commandName) {
            'time' => $this->executeTimeCommand($value, $ticket, $user, $emailData),
            'priority' => $this->executePriorityCommand($value, $ticket, $user),
            'status' => $this->executeStatusCommand($value, $ticket, $user),
            'assign' => $this->executeAssignCommand($value, $ticket, $user),
            'due' => $this->executeDueCommand($value, $ticket, $user),
            'category' => $this->executeCategoryCommand($value, $ticket, $user),
            'billing' => $this->executeBillingCommand($value, $ticket, $user),
            'tag' => $this->executeTagCommand($value, $ticket, $user),
            'close' => $this->executeCloseCommand($ticket, $user),
            'reopen' => $this->executeReopenCommand($ticket, $user),
            default => throw new \Exception('Command execution not implemented'),
        };
    }

    /**
     * Time command validation
     */
    private function validateTimeValue(string $value): array
    {
        $minutes = (int) $value;
        
        if ($minutes <= 0) {
            return ['valid' => false, 'error' => 'Time must be greater than 0 minutes'];
        }
        
        if ($minutes > 1440) { // 24 hours
            return ['valid' => false, 'error' => 'Time cannot exceed 24 hours (1440 minutes)'];
        }
        
        return ['valid' => true, 'minutes' => $minutes];
    }

    /**
     * Priority command validation
     */
    private function validatePriorityValue(string $value): array
    {
        $priority = TicketPriority::where('name', 'ILIKE', $value)
                                 ->orWhere('slug', 'ILIKE', $value)
                                 ->first();
        
        if (!$priority) {
            $available = TicketPriority::pluck('name')->toArray();
            return [
                'valid' => false, 
                'error' => 'Priority not found', 
                'available_priorities' => $available
            ];
        }
        
        return ['valid' => true, 'priority' => $priority];
    }

    /**
     * Status command validation
     */
    private function validateStatusValue(string $value): array
    {
        $status = TicketStatus::where('name', 'ILIKE', $value)
                             ->orWhere('slug', 'ILIKE', $value)
                             ->first();
        
        if (!$status) {
            $available = TicketStatus::pluck('name')->toArray();
            return [
                'valid' => false, 
                'error' => 'Status not found', 
                'available_statuses' => $available
            ];
        }
        
        return ['valid' => true, 'status' => $status];
    }

    /**
     * Assign command validation
     */
    private function validateAssignValue(string $value, Ticket $ticket): array
    {
        $user = User::where('email', $value)->first();
        
        if (!$user) {
            return ['valid' => false, 'error' => 'User not found with email: ' . $value];
        }
        
        // Check if user can be assigned to tickets in this account
        if ($user->account_id !== $ticket->account_id && !$user->hasPermission('system.configure')) {
            return ['valid' => false, 'error' => 'User cannot be assigned to tickets in this account'];
        }
        
        return ['valid' => true, 'user' => $user];
    }

    /**
     * Due date command validation
     */
    private function validateDueValue(string $value): array
    {
        try {
            $date = Carbon::parse($value);
            
            if ($date->isPast()) {
                return ['valid' => false, 'error' => 'Due date cannot be in the past'];
            }
            
            return ['valid' => true, 'date' => $date];
            
        } catch (\Exception $e) {
            return ['valid' => false, 'error' => 'Invalid date format. Use YYYY-MM-DD or similar'];
        }
    }

    /**
     * Category command validation
     */
    private function validateCategoryValue(string $value): array
    {
        $category = TicketCategory::where('name', 'ILIKE', $value)
                                 ->orWhere('slug', 'ILIKE', $value)
                                 ->first();
        
        if (!$category) {
            $available = TicketCategory::pluck('name')->toArray();
            return [
                'valid' => false, 
                'error' => 'Category not found', 
                'available_categories' => $available
            ];
        }
        
        return ['valid' => true, 'category' => $category];
    }

    /**
     * Billing command validation
     */
    private function validateBillingValue(string $value): array
    {
        // This would need to be implemented based on your billing rate system
        return ['valid' => true, 'billing_rate' => $value];
    }

    /**
     * Tag command validation
     */
    private function validateTagValue(string $value): array
    {
        $tags = explode(',', $value);
        $cleanTags = array_map('trim', $tags);
        
        return ['valid' => true, 'tags' => $cleanTags];
    }

    /**
     * Execute time command
     */
    private function executeTimeCommand(string $minutes, Ticket $ticket, User $user, array $emailData): array
    {
        $timeEntry = TimeEntry::create([
            'account_id' => $ticket->account_id,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'description' => 'Time added via email command',
            'duration' => (int) $minutes,
            'started_at' => now()->subMinutes((int) $minutes),
            'ended_at' => now(),
            'billable' => true,
            'status' => 'pending',
            'notes' => 'Added from email: ' . ($emailData['subject'] ?? ''),
        ]);

        return [
            'action' => 'time_added',
            'time_entry_id' => $timeEntry->id,
            'minutes' => (int) $minutes,
            'formatted_duration' => $this->formatDuration((int) $minutes),
        ];
    }

    /**
     * Execute priority command
     */
    private function executePriorityCommand(string $value, Ticket $ticket, User $user): array
    {
        $validation = $this->validatePriorityValue($value);
        $priority = $validation['priority'];
        
        $oldPriority = $ticket->priority?->name ?? 'None';
        $ticket->update(['priority_id' => $priority->id]);
        
        return [
            'action' => 'priority_updated',
            'old_priority' => $oldPriority,
            'new_priority' => $priority->name,
            'priority_id' => $priority->id,
        ];
    }

    /**
     * Execute status command
     */
    private function executeStatusCommand(string $value, Ticket $ticket, User $user): array
    {
        $validation = $this->validateStatusValue($value);
        $status = $validation['status'];
        
        $oldStatus = $ticket->status?->name ?? 'None';
        $ticket->update(['status_id' => $status->id]);
        
        return [
            'action' => 'status_updated',
            'old_status' => $oldStatus,
            'new_status' => $status->name,
            'status_id' => $status->id,
        ];
    }

    /**
     * Execute assign command
     */
    private function executeAssignCommand(string $email, Ticket $ticket, User $user): array
    {
        $validation = $this->validateAssignValue($email, $ticket);
        $assignee = $validation['user'];
        
        $oldAssignee = $ticket->assignedUser?->name ?? 'Unassigned';
        $ticket->update(['assigned_user_id' => $assignee->id]);
        
        return [
            'action' => 'ticket_assigned',
            'old_assignee' => $oldAssignee,
            'new_assignee' => $assignee->name,
            'assignee_email' => $assignee->email,
            'assignee_id' => $assignee->id,
        ];
    }

    /**
     * Execute due date command
     */
    private function executeDueCommand(string $value, Ticket $ticket, User $user): array
    {
        $validation = $this->validateDueValue($value);
        $dueDate = $validation['date'];
        
        $oldDue = $ticket->due_at?->format('Y-m-d') ?? 'None';
        $ticket->update(['due_at' => $dueDate]);
        
        return [
            'action' => 'due_date_set',
            'old_due_date' => $oldDue,
            'new_due_date' => $dueDate->format('Y-m-d'),
            'due_at' => $dueDate->toISOString(),
        ];
    }

    /**
     * Execute category command
     */
    private function executeCategoryCommand(string $value, Ticket $ticket, User $user): array
    {
        $validation = $this->validateCategoryValue($value);
        $category = $validation['category'];
        
        $oldCategory = $ticket->category?->name ?? 'None';
        $ticket->update(['category_id' => $category->id]);
        
        return [
            'action' => 'category_updated',
            'old_category' => $oldCategory,
            'new_category' => $category->name,
            'category_id' => $category->id,
        ];
    }

    /**
     * Execute billing command
     */
    private function executeBillingCommand(string $value, Ticket $ticket, User $user): array
    {
        // Implementation depends on your billing rate system
        return [
            'action' => 'billing_updated',
            'billing_rate' => $value,
        ];
    }

    /**
     * Execute tag command
     */
    private function executeTagCommand(string $value, Ticket $ticket, User $user): array
    {
        $validation = $this->validateTagValue($value);
        $tags = $validation['tags'];
        
        $existingTags = $ticket->tags ?? [];
        $newTags = array_unique(array_merge($existingTags, $tags));
        
        $ticket->update(['tags' => $newTags]);
        
        return [
            'action' => 'tags_added',
            'added_tags' => $tags,
            'all_tags' => $newTags,
        ];
    }

    /**
     * Execute close command
     */
    private function executeCloseCommand(Ticket $ticket, User $user): array
    {
        $closedStatus = TicketStatus::where('is_closed', true)->first();
        
        if (!$closedStatus) {
            throw new \Exception('No closed status found');
        }
        
        $oldStatus = $ticket->status?->name ?? 'None';
        $ticket->update([
            'status_id' => $closedStatus->id,
            'resolved_at' => now(),
            'resolved_by_id' => $user->id,
        ]);
        
        return [
            'action' => 'ticket_closed',
            'old_status' => $oldStatus,
            'closed_status' => $closedStatus->name,
            'resolved_at' => now()->toISOString(),
            'resolved_by' => $user->name,
        ];
    }

    /**
     * Execute reopen command
     */
    private function executeReopenCommand(Ticket $ticket, User $user): array
    {
        $openStatus = TicketStatus::where('is_closed', false)->first();
        
        if (!$openStatus) {
            throw new \Exception('No open status found');
        }
        
        $oldStatus = $ticket->status?->name ?? 'None';
        $ticket->update([
            'status_id' => $openStatus->id,
            'resolved_at' => null,
            'resolved_by_id' => null,
        ]);
        
        return [
            'action' => 'ticket_reopened',
            'old_status' => $oldStatus,
            'reopened_status' => $openStatus->name,
            'reopened_by' => $user->name,
        ];
    }

    /**
     * Format command result
     */
    private function commandResult(string $original, bool $success, string $message, array $data = []): array
    {
        return array_merge([
            'command' => $original,
            'success' => $success,
            'message' => $message,
            'timestamp' => now()->toISOString(),
        ], $data);
    }

    /**
     * Log command execution for audit
     */
    private function logCommandExecution(
        string $command, 
        string $value, 
        Ticket $ticket, 
        User $user, 
        array $result,
        EmailProcessingLog $processingLog
    ): void {
        Log::info('Email command executed', [
            'email_id' => $processingLog->email_id,
            'command' => $command,
            'value' => $value,
            'ticket_id' => $ticket->id,
            'user_id' => $user->id,
            'result' => $result,
        ]);
    }

    /**
     * Format duration in readable format
     */
    private function formatDuration(int $minutes): string
    {
        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;
        
        if ($hours > 0) {
            return $hours . 'h ' . $remainingMinutes . 'm';
        }
        
        return $minutes . 'm';
    }

    /**
     * Get supported commands
     */
    public function getSupportedCommands(): array
    {
        return $this->supportedCommands;
    }
}