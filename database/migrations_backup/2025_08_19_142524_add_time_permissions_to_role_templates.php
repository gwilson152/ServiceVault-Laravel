<?php

use App\Models\RoleTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Service Provider role template
        RoleTemplate::updateOrCreate(['name' => 'Service Provider'], [
            'display_name' => 'Service Provider',
            'description' => 'Service provider with comprehensive time management and client account access',
            'is_system_role' => false,
            'is_default' => false,
            'is_modifiable' => true,
            'context' => 'service_provider',
            'permissions' => [
                // Account Management
                'accounts.view',
                'accounts.manage',

                // User Management
                'users.view',
                'users.manage.account',

                // Service Tickets - Comprehensive Access
                'tickets.create',
                'tickets.create.account',
                'tickets.view.all',
                'tickets.view.account',
                'tickets.edit.all',
                'tickets.edit.account',
                'tickets.assign',
                'tickets.assign.account',
                'tickets.transition',
                'tickets.close',
                'tickets.comment',
                'tickets.act_as_agent',

                // Time Tracking - Full Management Access
                'time.track',
                'time.manage',         // Key permission for service providers
                'time.view.all',
                'time.view.account',
                'time.edit.all',       // Can edit all time entries
                'time.edit.team',      // Can edit team member entries
                'time.approve',        // Can approve time entries
                'time.reports',

                // Timer Management
                'timers.create',
                'timers.read',
                'timers.write',
                'timers.manage',
                'timers.sync',
                'timers.act_as_agent',

                // Billing - View and manage client billing
                'billing.view.account',
                'billing.manage.account',
                'billing.rates.view',

                // Reports
                'reports.account',
                'reports.time',
            ],
            'widget_permissions' => [
                'widgets.dashboard.account-management',
                'widgets.dashboard.ticket-overview',
                'widgets.dashboard.my-tickets',
                'widgets.dashboard.time-tracking',
                'widgets.dashboard.all-timers',
                'widgets.dashboard.billing-overview',
                'widgets.dashboard.account-activity',
                'widgets.dashboard.quick-actions',
            ],
            'page_permissions' => [
                'pages.tickets.manage',
                'pages.accounts.manage',
                'pages.reports.account',
                'pages.billing.overview',
                'pages.time-entries.manage',
            ],
        ]);

        // Update Manager role to include enhanced time permissions
        $managerRole = RoleTemplate::where('name', 'Manager')->first();
        if ($managerRole) {
            $permissions = $managerRole->permissions ?? [];

            // Add new time management permissions if not already present
            $newTimePermissions = [
                'time.manage',
                'time.edit.all',
                'time.edit.team',
                'time.approve',
            ];

            foreach ($newTimePermissions as $permission) {
                if (! in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }

            $managerRole->update(['permissions' => $permissions]);
        }

        // Update Agent role to include team time permissions
        $agentRole = RoleTemplate::where('name', 'Agent')->first();
        if ($agentRole) {
            $permissions = $agentRole->permissions ?? [];

            // Add agent-specific time permissions
            $newAgentPermissions = [
                'time.edit.team',     // Agents can edit team entries if they lead a team
                'timers.act_as_agent',
                'tickets.act_as_agent',
            ];

            foreach ($newAgentPermissions as $permission) {
                if (! in_array($permission, $permissions)) {
                    $permissions[] = $permission;
                }
            }

            $agentRole->update(['permissions' => $permissions]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Service Provider role
        RoleTemplate::where('name', 'Service Provider')->delete();

        // Remove added permissions from Manager role
        $managerRole = RoleTemplate::where('name', 'Manager')->first();
        if ($managerRole) {
            $permissions = $managerRole->permissions ?? [];
            $permissionsToRemove = ['time.manage', 'time.edit.all', 'time.edit.team', 'time.approve'];

            $permissions = array_filter($permissions, function ($permission) use ($permissionsToRemove) {
                return ! in_array($permission, $permissionsToRemove);
            });

            $managerRole->update(['permissions' => array_values($permissions)]);
        }

        // Remove added permissions from Agent role
        $agentRole = RoleTemplate::where('name', 'Agent')->first();
        if ($agentRole) {
            $permissions = $agentRole->permissions ?? [];
            $permissionsToRemove = ['time.edit.team', 'timers.act_as_agent', 'tickets.act_as_agent'];

            $permissions = array_filter($permissions, function ($permission) use ($permissionsToRemove) {
                return ! in_array($permission, $permissionsToRemove);
            });

            $agentRole->update(['permissions' => array_values($permissions)]);
        }
    }
};
