<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\ServiceTicket;

class ServiceTicketResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_number' => $this->ticket_number,
            'title' => $this->title,
            'description' => $this->description,
            
            // Status and priority
            'status' => $this->status,
            'priority' => $this->priority,
            'category' => $this->category,
            
            // Customer information
            'customer_name' => $this->customer_name,
            'customer_email' => $this->when(
                $this->shouldShowCustomerEmail($request->user()),
                $this->customer_email
            ),
            'customer_phone' => $this->when(
                $this->shouldShowCustomerPhone($request->user()),
                $this->customer_phone
            ),
            
            // Assignment and billing
            'billable' => $this->billable,
            'estimated_hours' => $this->estimated_hours,
            'quoted_amount' => $this->when(
                isset($this->quoted_amount),
                $this->quoted_amount
            ),
            'requires_approval' => $this->requires_approval,
            
            // Dates and time tracking
            'due_date' => $this->due_date,
            'opened_at' => $this->opened_at,
            'started_at' => $this->started_at,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            'last_customer_update' => $this->last_customer_update,
            'last_internal_update' => $this->last_internal_update,
            
            // Timestamps
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name
                ];
            }),
            
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                    'email' => $this->when(
                        isset($this->createdBy->email),
                        $this->createdBy->email
                    )
                ];
            }),
            
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return $this->assignedTo ? [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->when(
                        isset($this->assignedTo->email),
                        $this->assignedTo->email
                    )
                ] : null;
            }),
            
            'assigned_users' => $this->whenLoaded('assignedUsers', function () {
                return $this->assignedUsers->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email
                    ];
                });
            }),
            
            'billing_rate' => $this->whenLoaded('billingRate', function () {
                return $this->billingRate ? [
                    'id' => $this->billingRate->id,
                    'rate' => $this->billingRate->rate,
                    'currency' => $this->billingRate->currency ?? 'USD'
                ] : null;
            }),
            
            // Time tracking data
            'time_entries' => $this->whenLoaded('timeEntries', function () {
                return $this->timeEntries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'duration' => $entry->duration,
                        'billable' => $entry->billable,
                        'created_at' => $entry->created_at
                    ];
                });
            }),
            
            'timers' => $this->whenLoaded('timers', function () {
                return $this->timers->map(function ($timer) {
                    return [
                        'id' => $timer->id,
                        'status' => $timer->status,
                        'started_at' => $timer->started_at
                    ];
                });
            }),
            
            // Calculated fields
            'is_overdue' => $this->isOverdue(),
            'is_sla_breached' => $this->isSlaBreached(),
            'total_time_logged' => $this->getTotalTimeLogged(),
            'total_billable_time' => $this->getTotalBillableTime(),
            'total_cost' => $this->when(
                $this->relationLoaded('timeEntries') && $this->timeEntries->count() > 0,
                $this->getTotalCost()
            ),
            'hours_logged' => round($this->getTotalTimeLogged() / 3600, 2),
            'billable_hours' => round($this->getTotalBillableTime() / 3600, 2),
            
            // Status indicators for frontend
            'status_color' => $this->getStatusColor(),
            'priority_color' => $this->getPriorityColor(),
            'status_label' => $this->getStatusLabel(),
            'priority_label' => $this->getPriorityLabel(),
            
            // Permission flags for frontend
            'can_edit' => $this->canBeEditedBy($request->user()),
            'can_view' => $this->canBeViewedBy($request->user()),
            'can_assign' => $this->canAssign($request->user()),
            'can_transition' => $this->getAvailableTransitions(),
            
            // Internal notes (staff only)
            'internal_notes' => $this->when(
                $this->shouldShowInternalNotes($request->user()),
                $this->internal_notes
            ),
            'resolution_notes' => $this->when(
                $this->shouldShowResolutionNotes($request->user()),
                $this->resolution_notes
            ),
        ];
    }
    
    /**
     * Determine if customer email should be shown to this user
     */
    private function shouldShowCustomerEmail($user): bool
    {
        return $user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists() ||
               $user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() ||
               $this->created_by === $user->id ||
               $this->assigned_to === $user->id;
    }
    
    /**
     * Determine if customer phone should be shown to this user
     */
    private function shouldShowCustomerPhone($user): bool
    {
        return $this->shouldShowCustomerEmail($user);
    }
    
    /**
     * Determine if internal notes should be shown to this user
     */
    private function shouldShowInternalNotes($user): bool
    {
        return $user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists() ||
               $user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() ||
               $this->created_by === $user->id ||
               $this->assigned_to === $user->id;
    }
    
    /**
     * Determine if resolution notes should be shown to this user
     */
    private function shouldShowResolutionNotes($user): bool
    {
        return !empty($this->resolution_notes) && 
               ($user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists() ||
                $user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists() ||
                $this->created_by === $user->id ||
                $this->assigned_to === $user->id);
    }
    
    /**
     * Get status color for frontend display
     */
    private function getStatusColor(): string
    {
        return match ($this->status) {
            ServiceTicket::STATUS_OPEN => 'blue',
            ServiceTicket::STATUS_IN_PROGRESS => 'yellow',
            ServiceTicket::STATUS_WAITING_CUSTOMER => 'orange',
            ServiceTicket::STATUS_RESOLVED => 'green',
            ServiceTicket::STATUS_CLOSED => 'gray',
            ServiceTicket::STATUS_CANCELLED => 'red',
            default => 'gray'
        };
    }
    
    /**
     * Get priority color for frontend display
     */
    private function getPriorityColor(): string
    {
        return match ($this->priority) {
            ServiceTicket::PRIORITY_LOW => 'gray',
            ServiceTicket::PRIORITY_MEDIUM => 'blue',
            ServiceTicket::PRIORITY_HIGH => 'orange',
            ServiceTicket::PRIORITY_URGENT => 'red',
            default => 'gray'
        };
    }
    
    /**
     * Get human-readable status label
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            ServiceTicket::STATUS_OPEN => 'Open',
            ServiceTicket::STATUS_IN_PROGRESS => 'In Progress',
            ServiceTicket::STATUS_WAITING_CUSTOMER => 'Waiting for Customer',
            ServiceTicket::STATUS_RESOLVED => 'Resolved',
            ServiceTicket::STATUS_CLOSED => 'Closed',
            ServiceTicket::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status)
        };
    }
    
    /**
     * Get human-readable priority label
     */
    private function getPriorityLabel(): string
    {
        return match ($this->priority) {
            ServiceTicket::PRIORITY_LOW => 'Low',
            ServiceTicket::PRIORITY_MEDIUM => 'Medium',
            ServiceTicket::PRIORITY_HIGH => 'High',
            ServiceTicket::PRIORITY_URGENT => 'Urgent',
            default => ucfirst($this->priority)
        };
    }
    
    /**
     * Check if user can assign this ticket
     */
    private function canAssign($user): bool
    {
        return $user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists() ||
               $user->roleTemplates()->whereJsonContains('permissions', 'teams.manage')->exists();
    }
    
    /**
     * Get available status transitions for this user
     */
    private function getAvailableTransitions(): array
    {
        $allTransitions = [
            ServiceTicket::STATUS_OPEN => [ServiceTicket::STATUS_IN_PROGRESS, ServiceTicket::STATUS_CANCELLED],
            ServiceTicket::STATUS_IN_PROGRESS => [ServiceTicket::STATUS_WAITING_CUSTOMER, ServiceTicket::STATUS_RESOLVED, ServiceTicket::STATUS_CANCELLED],
            ServiceTicket::STATUS_WAITING_CUSTOMER => [ServiceTicket::STATUS_IN_PROGRESS, ServiceTicket::STATUS_RESOLVED],
            ServiceTicket::STATUS_RESOLVED => [ServiceTicket::STATUS_CLOSED, ServiceTicket::STATUS_IN_PROGRESS],
            ServiceTicket::STATUS_CLOSED => [],
            ServiceTicket::STATUS_CANCELLED => [],
        ];
        
        return $allTransitions[$this->status] ?? [];
    }
}
