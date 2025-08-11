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
            'is_active' => $this->is_active,
            'timezone' => $this->timezone,
            'locale' => $this->locale,
            'preferences' => $this->preferences,
            'last_active_at' => $this->last_active_at,
            'last_login_at' => $this->last_login_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Current account information
            'current_account_id' => $this->current_account_id,
            'current_account' => $this->whenLoaded('currentAccount', function () {
                return [
                    'id' => $this->currentAccount->id,
                    'name' => $this->currentAccount->name,
                    'display_name' => $this->currentAccount->display_name,
                    'account_type' => $this->currentAccount->account_type,
                ];
            }),
            
            // Account assignments
            'accounts' => $this->whenLoaded('accounts', function () {
                return $this->accounts->map(function ($account) {
                    return [
                        'id' => $account->id,
                        'name' => $account->name,
                        'display_name' => $account->display_name,
                        'company_name' => $account->company_name,
                        'account_type' => $account->account_type,
                        'is_active' => $account->is_active,
                        'users_count' => $account->users_count ?? $account->users()->count(),
                        'hierarchy_level' => $account->hierarchy_level,
                        'assigned_at' => $account->pivot->created_at ?? null,
                    ];
                });
            }),
            
            // Role template assignments
            'role_templates' => $this->whenLoaded('roleTemplates', function () {
                return $this->roleTemplates->map(function ($roleTemplate) {
                    return [
                        'id' => $roleTemplate->id,
                        'name' => $roleTemplate->name,
                        'description' => $roleTemplate->description,
                        'context' => $roleTemplate->context,
                        'is_system_role' => $roleTemplate->is_system_role,
                        'is_modifiable' => $roleTemplate->is_modifiable,
                        'account_id' => $roleTemplate->pivot->account_id ?? null,
                        'assigned_at' => $roleTemplate->pivot->created_at ?? null,
                    ];
                });
            }),
            
            // Permission information
            'is_super_admin' => $this->isSuperAdmin(),
            'permissions' => $this->when($request->user()->hasAnyPermission(['admin.read', 'super_admin']), function () {
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
                        'duration' => $timer->getCurrentDuration(),
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
            
            // Account summary for overview
            'accounts_summary' => $this->when($this->relationLoaded('accounts'), function () {
                return [
                    'total_accounts' => $this->accounts->count(),
                    'account_types' => $this->accounts->groupBy('account_type')->map->count(),
                    'active_accounts' => $this->accounts->where('is_active', true)->count(),
                ];
            }),
            
            // Role template summary
            'roles_summary' => $this->when($this->relationLoaded('roleTemplates'), function () {
                return [
                    'total_roles' => $this->roleTemplates->count(),
                    'contexts' => $this->roleTemplates->pluck('context')->unique()->values(),
                    'system_roles' => $this->roleTemplates->where('is_system_role', true)->count(),
                ];
            }),
        ];
    }
}