<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'email_verified_at' => $this->email_verified_at,
            'user_type' => $this->user_type,
            'is_active' => $this->is_active,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'preferences' => $this->preferences,
            'last_active_at' => $this->last_active_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Account information (single relationship)
            'account_id' => $this->account_id,
            'account' => $this->whenLoaded('account', function () {
                return [
                    'id' => $this->account->id,
                    'name' => $this->account->name,
                    'display_name' => $this->account->display_name,
                    'account_type' => $this->account->account_type,
                    'is_active' => $this->account->is_active,
                    'hierarchy_level' => $this->account->hierarchy_level,
                ];
            }),
            
            // Role template information (single relationship)
            'role_template_id' => $this->role_template_id,
            'role_template' => $this->whenLoaded('roleTemplate', function () {
                return [
                    'id' => $this->roleTemplate->id,
                    'name' => $this->roleTemplate->name,
                    'description' => $this->roleTemplate->description,
                    'context' => $this->roleTemplate->context,
                    'is_system_role' => $this->roleTemplate->is_system_role,
                    'is_super_admin' => $this->roleTemplate->is_super_admin,
                    'is_modifiable' => $this->roleTemplate->is_modifiable,
                    'permissions' => $this->roleTemplate->getAllPermissions(),
                ];
            }),
            
            // Permission information
            'is_super_admin' => $this->isSuperAdmin(),
            'permissions' => $this->when($request->user()->isSuperAdmin() || $request->user()->hasAnyPermission(['admin.read', 'admin.manage']), function () {
                return $this->getAllPermissions();
            }),
            
            // Activity statistics
            'statistics' => $this->when($this->relationLoaded('timers') || $this->relationLoaded('timeEntries'), function () {
                return [
                    'total_assigned_tickets' => $this->assignedTickets()->count(),
                    'total_created_tickets' => $this->createdTickets()->count(),
                    'total_time_entries' => $this->timeEntries()->count(),
                    'total_time_logged' => $this->timeEntries()->sum('duration'),
                    'active_timers_count' => $this->timers()->where('status', 'running')->count(),
                    'total_timers_created' => $this->timers()->count(),
                ];
            }),
            
            // Recent activity (limited when loaded)
            'recent_timers' => $this->whenLoaded('timers', function () {
                return $this->timers->take(5)->map(function ($timer) {
                    return [
                        'id' => $timer->id,
                        'description' => $timer->description,
                        'status' => $timer->status,
                        'started_at' => $timer->started_at,
                        'updated_at' => $timer->updated_at,
                        'duration' => $timer->duration,
                    ];
                });
            }),
            
            'recent_time_entries' => $this->whenLoaded('timeEntries', function () {
                return $this->timeEntries->take(10)->map(function ($timeEntry) {
                    return [
                        'id' => $timeEntry->id,
                        'description' => $timeEntry->description,
                        'duration' => $timeEntry->duration,
                        'duration_formatted' => $timeEntry->duration_formatted,
                        'billable' => $timeEntry->billable,
                        'status' => $timeEntry->status,
                        'started_at' => $timeEntry->started_at,
                        'ended_at' => $timeEntry->ended_at,
                        'calculated_cost' => $timeEntry->calculated_cost,
                    ];
                });
            }),
            
        ];
    }
}