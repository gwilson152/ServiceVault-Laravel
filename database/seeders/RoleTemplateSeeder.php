<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RoleTemplate;

class RoleTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Super Administrator - Full system access (NON-MODIFIABLE)
        RoleTemplate::updateOrCreate(['name' => 'Super Admin'], [
            'display_name' => 'Super Administrator',
            'description' => 'Full system administration with all permissions (cannot be modified)',
            'is_system_role' => true,
            'is_default' => false,
            'is_modifiable' => false,
            'context' => 'service_provider',
            'permissions' => [
                // System Administration  
                'admin.manage',
                'admin.write',
                'system.configure',
                'system.manage',
                
                // Account Management
                'accounts.create',
                'accounts.manage',
                'accounts.hierarchy.access',
                
                // User Management
                'users.manage',
                'users.invite',
                'users.assign',
                
                // Role Management
                'roles.manage',
                'role_templates.manage',
                
                // Service Tickets - All Permissions
                'tickets.admin',
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
                'tickets.delete',
                
                // Time Tracking - All Permissions
                'time.admin',
                'time.track',
                'time.manage',
                'time.view.all',
                'time.view.account',
                'time.edit.all',
                'time.edit.account',
                'time.approve',
                'time.reports',
                
                // Timer Management - All Permissions
                'timers.admin',
                'timers.read',
                'timers.write',
                
                // Feature-Specific Agent Designations
                'timers.act_as_agent',
                'tickets.act_as_agent',
                'time.act_as_agent',
                'billing.act_as_agent',
                
                // Billing & Financial
                'billing.manage',
                'billing.configure',
                'billing.rates',
                'billing.invoices',
                'billing.reports',
                
                // Settings Management
                'settings.manage',
            ],
            'widget_permissions' => [
                // All dashboard widgets
                'widgets.dashboard.system-health',
                'widgets.dashboard.system-stats',
                'widgets.dashboard.user-management',
                'widgets.dashboard.account-management',
                'widgets.dashboard.ticket-overview',
                'widgets.dashboard.my-tickets',
                'widgets.dashboard.time-tracking',
                'widgets.dashboard.all-timers',
                'widgets.dashboard.billing-overview',
                'widgets.dashboard.account-activity',
                'widgets.dashboard.quick-actions',
                'widgets.configure',
                'dashboard.customize',
            ],
            'page_permissions' => [
                // All administrative pages
                'pages.admin.system',
                'pages.settings.roles',
                'pages.admin.users',
                'pages.tickets.manage',
                'pages.tickets.create',
                'pages.reports.account',
                'pages.billing.overview',
                'pages.reports.billing',
            ]
        ]);

        // Admin - Full service provider administration (MODIFIABLE)
        RoleTemplate::updateOrCreate(['name' => 'Admin'], [
            'display_name' => 'Administrator',
            'description' => 'Full service provider administration and user management',
            'is_system_role' => false,
            'is_default' => false,
            'is_modifiable' => true,
            'context' => 'service_provider',
            'permissions' => [
                'admin.manage',
                'accounts.create',
                'accounts.manage',
                'accounts.hierarchy.access',
                'users.manage',
                'users.manage.account',
                'users.invite',
                'billing.manage',
                'tickets.admin',
                'tickets.create',
                'tickets.create.account',
                'tickets.view.all',
                'tickets.edit.all',
                'tickets.assign',
                'tickets.assign.account',
                'tickets.transition',
                'tickets.close',
                'tickets.delete',
                'time.admin',
                'time.view.all',
                'time.edit.all',
                'time.approve',
                'time.reports',
                'time.reports.account',
                // Legacy timer support
                'timers.read',
                'timers.write',
                'timers.manage',
                'timers.sync',
                // Feature-Specific Agent Designations
                'timers.act_as_agent',
                'tickets.act_as_agent',
                'time.act_as_agent',
                'billing.act_as_agent',
            ],
            'widget_permissions' => [
                'widgets.dashboard.system-stats',
                'widgets.dashboard.user-management',
                'widgets.dashboard.account-management',
                'widgets.dashboard.ticket-overview',
                'widgets.dashboard.all-timers',
                'widgets.dashboard.billing-overview',
                'widgets.dashboard.account-activity',
                'widgets.configure',
            ],
            'page_permissions' => [
                'pages.admin.users',
                'pages.tickets.manage',
                'pages.reports.account',
                'pages.billing.overview',
            ]
        ]);


        // Agent - Service delivery and time tracking (MODIFIABLE)
        RoleTemplate::updateOrCreate(['name' => 'Agent'], [
            'display_name' => 'Service Agent',
            'description' => 'Service delivery agent with ticket management and time tracking',
            'is_system_role' => false,
            'is_default' => false,
            'is_modifiable' => true,
            'context' => 'service_provider',
            'permissions' => [
                'tickets.create',
                'tickets.create.account',
                'tickets.view.assigned',
                'tickets.view.own',
                'tickets.edit.own',
                'tickets.edit.assigned',
                'tickets.transition',
                'tickets.comment',
                'time.track',
                'time.view.own',
                'time.edit.own',
                'time.reports.own',
                // Legacy timer support
                'timers.create',
                'timers.read',
                'timers.write',
                'timers.sync',
                // Feature-Specific Agent Designations
                'timers.act_as_agent',
                'tickets.act_as_agent',
                'time.act_as_agent',
                'billing.act_as_agent',
            ],
            'widget_permissions' => [
                'widgets.dashboard.my-tickets',
                'widgets.dashboard.time-tracking',
                'widgets.dashboard.quick-actions',
                'widgets.dashboard.ticket-timer-stats',
                'widgets.dashboard.recent-time-entries',
            ],
            'page_permissions' => [
                'pages.tickets.create',
                'pages.reports.own',
            ]
        ]);

        // Account Manager - Primary account + all subsidiaries access (MODIFIABLE)
        RoleTemplate::updateOrCreate(['name' => 'Account Manager'], [
            'display_name' => 'Account Manager',
            'description' => 'Customer account manager with access to primary account and all subsidiaries',
            'is_system_role' => false,
            'is_default' => false,
            'is_modifiable' => true,
            'context' => 'account_user',
            'permissions' => [
                'accounts.hierarchy.access',
                'accounts.manage.own',
                'users.manage.account',
                'tickets.create.account',
                'tickets.view.account',
                'tickets.edit.account',
                'tickets.assign.account',
                'billing.view.account',
            ],
            'widget_permissions' => [
                'widgets.dashboard.ticket-overview',
                'widgets.dashboard.account-activity',
                'widgets.dashboard.billing-overview',
                'widgets.dashboard.my-tickets',
            ],
            'page_permissions' => [
                'pages.tickets.manage',
                'pages.reports.account',
                'pages.billing.overview',
                'pages.portal.dashboard',
            ]
        ]);

        // Account User - Basic customer account access (MODIFIABLE)
        RoleTemplate::updateOrCreate(['name' => 'Account User'], [
            'display_name' => 'Account User',
            'description' => 'Basic customer account access with service request capabilities',
            'is_system_role' => false,
            'is_default' => true,
            'is_modifiable' => true,
            'context' => 'account_user',
            'permissions' => [
                'tickets.create.request',
                'tickets.view.own',
                'tickets.comment',
                'billing.view.own',
                'portal.access',
            ],
            'widget_permissions' => [
                'widgets.dashboard.my-tickets',
                'widgets.dashboard.account-activity',
                'widgets.dashboard.quick-actions',
            ],
            'page_permissions' => [
                'pages.portal.dashboard',
                'pages.portal.tickets',
                'pages.billing.own',
            ]
        ]);
    }
}