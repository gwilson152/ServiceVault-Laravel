<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\Ticket;

class TicketResource extends JsonResource
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
            
            // Assignment and billing
            'estimated_hours' => $this->estimated_hours,
            'estimated_amount' => $this->estimated_amount,
            'actual_amount' => $this->actual_amount,
            
            // Dates and time tracking
            'due_date' => $this->due_date,
            'started_at' => $this->started_at,
            'completed_at' => $this->completed_at,
            'resolved_at' => $this->resolved_at,
            'closed_at' => $this->closed_at,
            
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
            
            'agent' => $this->whenLoaded('agent', function () {
                return $this->agent ? [
                    'id' => $this->agent->id,
                    'name' => $this->agent->name,
                    'email' => $this->when(
                        isset($this->agent->email),
                        $this->agent->email
                    )
                ] : null;
            }),
            
            'customer' => $this->when($this->customer_id || $this->customer_name, function () {
                if ($this->customer) {
                    return $this->whenLoaded('customer', function () {
                        return [
                            'id' => $this->customer->id,
                            'name' => $this->customer->name,
                            'email' => $this->customer->email,
                        ];
                    });
                } else {
                    return [
                        'name' => $this->customer_name,
                        'email' => $this->customer_email,
                    ];
                }
            }),
            
            // Legacy field for backward compatibility
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
                        'email' => $user->email,
                        'role' => $user->pivot->role ?? 'assignee',
                        'assigned_at' => $user->pivot->assigned_at
                    ];
                });
            }),
            
            'billing_rate' => $this->whenLoaded('billingRate', function () {
                return $this->billingRate ? [
                    'id' => $this->billingRate->id,
                    'rate' => $this->billingRate->rate
                ] : null;
            }),
            
            // Status and category models
            'status_model' => $this->whenLoaded('statusModel', function () {
                return $this->statusModel ? [
                    'key' => $this->statusModel->key,
                    'name' => $this->statusModel->name,
                    'color' => $this->statusModel->color,
                    'bg_color' => $this->statusModel->bg_color,
                    'icon' => $this->statusModel->icon,
                    'is_closed' => $this->statusModel->is_closed
                ] : null;
            }),
            
            'category_model' => $this->whenLoaded('categoryModel', function () {
                return $this->categoryModel ? [
                    'key' => $this->categoryModel->key,
                    'name' => $this->categoryModel->name,
                    'color' => $this->categoryModel->color,
                    'bg_color' => $this->categoryModel->bg_color,
                    'icon' => $this->categoryModel->icon,
                    'sla_hours' => $this->categoryModel->sla_hours,
                    'formatted_sla' => $this->categoryModel->formatted_sla
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
                        'started_at' => $timer->started_at,
                        'duration' => $timer->duration
                    ];
                });
            }),
            
            'addons' => $this->whenLoaded('addons', function () {
                return $this->addons->map(function ($addon) {
                    return [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'status' => $addon->status,
                        'total' => $addon->total,
                        'created_at' => $addon->created_at
                    ];
                });
            }),
            
            // Calculated fields
            'is_overdue' => $this->isOverdue(),
            'total_time_logged' => $this->getTotalTimeLogged(),
            'total_billable_time' => $this->getTotalBillableTime(),
            'total_cost' => $this->when(
                $this->relationLoaded('timeEntries') && $this->timeEntries->count() > 0,
                $this->getTotalCost()
            ),
            'total_addon_cost' => $this->getTotalAddonCost(),
            'pending_addon_cost' => $this->getPendingAddonCost(),
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
            
            // Metadata
            'metadata' => $this->metadata,
            'settings' => $this->settings,
        ];
    }
    
    /**
     * Get status color for frontend display
     */
    private function getStatusColor(): string
    {
        return match ($this->status) {
            Ticket::STATUS_OPEN => 'blue',
            Ticket::STATUS_IN_PROGRESS => 'yellow',
            Ticket::STATUS_WAITING_CUSTOMER => 'orange',
            Ticket::STATUS_ON_HOLD => 'purple',
            Ticket::STATUS_RESOLVED => 'green',
            Ticket::STATUS_CLOSED => 'gray',
            Ticket::STATUS_CANCELLED => 'red',
            default => 'gray'
        };
    }
    
    /**
     * Get priority color for frontend display
     */
    private function getPriorityColor(): string
    {
        return match ($this->priority) {
            Ticket::PRIORITY_LOW => 'gray',
            Ticket::PRIORITY_NORMAL => 'blue',
            Ticket::PRIORITY_HIGH => 'orange',
            Ticket::PRIORITY_URGENT => 'red',
            default => 'gray'
        };
    }
    
    /**
     * Get human-readable status label
     */
    private function getStatusLabel(): string
    {
        return match ($this->status) {
            Ticket::STATUS_OPEN => 'Open',
            Ticket::STATUS_IN_PROGRESS => 'In Progress',
            Ticket::STATUS_WAITING_CUSTOMER => 'Waiting for Customer',
            Ticket::STATUS_ON_HOLD => 'On Hold',
            Ticket::STATUS_RESOLVED => 'Resolved',
            Ticket::STATUS_CLOSED => 'Closed',
            Ticket::STATUS_CANCELLED => 'Cancelled',
            default => ucfirst($this->status)
        };
    }
    
    /**
     * Get human-readable priority label
     */
    private function getPriorityLabel(): string
    {
        return match ($this->priority) {
            Ticket::PRIORITY_LOW => 'Low',
            Ticket::PRIORITY_NORMAL => 'Normal',
            Ticket::PRIORITY_HIGH => 'High',
            Ticket::PRIORITY_URGENT => 'Urgent',
            default => ucfirst($this->priority)
        };
    }
    
    /**
     * Check if user can assign this ticket
     */
    private function canAssign($user): bool
    {
        return $user->isSuperAdmin() ||
               $user->hasAnyPermission(['admin.write', 'tickets.assign', 'teams.manage']);
    }
    
    /**
     * Get available status transitions for this user
     */
    private function getAvailableTransitions(): array
    {
        $allTransitions = [
            Ticket::STATUS_OPEN => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_ON_HOLD, Ticket::STATUS_CANCELLED],
            Ticket::STATUS_IN_PROGRESS => [Ticket::STATUS_WAITING_CUSTOMER, Ticket::STATUS_ON_HOLD, Ticket::STATUS_RESOLVED, Ticket::STATUS_CANCELLED],
            Ticket::STATUS_WAITING_CUSTOMER => [Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_ON_HOLD, Ticket::STATUS_RESOLVED],
            Ticket::STATUS_ON_HOLD => [Ticket::STATUS_OPEN, Ticket::STATUS_IN_PROGRESS, Ticket::STATUS_CANCELLED],
            Ticket::STATUS_RESOLVED => [Ticket::STATUS_CLOSED, Ticket::STATUS_IN_PROGRESS],
            Ticket::STATUS_CLOSED => [],
            Ticket::STATUS_CANCELLED => [],
        ];
        
        return $allTransitions[$this->status] ?? [];
    }
}