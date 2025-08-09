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
        // Super Administrator - Full system access
        RoleTemplate::updateOrCreate(['name' => 'Super Administrator'], [
            'description' => 'Full system administration with all permissions',
            'is_system_role' => true,
            'is_default' => false,
            'permissions' => [
                'admin.manage',
                'system.configure',
                'accounts.create',
                'accounts.manage',
                'users.manage',
                'roles.manage',
                'teams.manage',
                'billing.manage',
                'tickets.admin',
                'tickets.create',
                'tickets.view.all',
                'tickets.edit.all',
                'tickets.assign',
                'tickets.transition',
                'tickets.close',
                'tickets.delete',
                'time.admin',
                'time.view.all',
                'time.edit.all',
                'time.approve',
                'time.reports',
            ]
        ]);

        // System Administrator - System management without role template management
        RoleTemplate::updateOrCreate(['name' => 'System Administrator'], [
            'description' => 'System administration and user management',
            'is_system_role' => true,
            'is_default' => false,
            'permissions' => [
                'admin.manage',
                'accounts.manage',
                'users.manage',
                'teams.manage',
                'billing.manage',
                'tickets.admin',
                'tickets.create',
                'tickets.view.all',
                'tickets.edit.all',
                'tickets.assign',
                'tickets.transition',
                'tickets.close',
                'time.admin',
                'time.view.all',
                'time.edit.all',
                'time.approve',
                'time.reports',
            ]
        ]);

        // Account Manager - Account-specific administration
        RoleTemplate::updateOrCreate(['name' => 'Account Manager'], [
            'description' => 'Account setup, user assignment, and account-level management',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'accounts.configure',
                'users.invite',
                'users.assign',
                'teams.manage',
                'billing.configure',
                'tickets.admin',
                'tickets.create',
                'tickets.view.account',
                'tickets.edit.account',
                'tickets.assign',
                'tickets.transition',
                'tickets.close',
                'time.manage',
                'time.view.team',
                'time.approve',
                'time.reports.account',
            ]
        ]);

        // Team Lead/Manager - Team oversight and project management
        RoleTemplate::updateOrCreate(['name' => 'Team Lead'], [
            'description' => 'Team oversight, ticket assignment, and approval workflows',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'teams.manage',
                'tickets.create',
                'tickets.view.team',
                'tickets.edit.team',
                'tickets.assign',
                'tickets.transition',
                'time.manage',
                'time.view.team',
                'time.approve',
                'time.reports.team',
            ]
        ]);

        // Senior Employee - Advanced employee with some team responsibilities
        RoleTemplate::updateOrCreate(['name' => 'Senior Employee'], [
            'description' => 'Experienced employee with mentor and ticket assignment capabilities',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'tickets.create',
                'tickets.view.assigned',
                'tickets.edit.own',
                'tickets.transition',
                'tickets.assign.basic',
                'time.track',
                'time.view.own',
                'time.edit.own',
                'time.reports.own',
            ]
        ]);

        // Employee - Standard employee with time tracking and ticket work
        RoleTemplate::updateOrCreate(['name' => 'Employee'], [
            'description' => 'Standard employee with time tracking and assigned ticket management',
            'is_system_role' => false,
            'is_default' => true,
            'permissions' => [
                'tickets.create.basic',
                'tickets.view.assigned',
                'tickets.edit.own',
                'tickets.transition.basic',
                'time.track',
                'time.view.own',
                'time.edit.own',
                'time.reports.own',
            ]
        ]);

        // Customer/Client - Portal access with limited ticket interaction
        RoleTemplate::updateOrCreate(['name' => 'Customer'], [
            'description' => 'Customer portal access with ticket viewing and creation',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'tickets.create.request',
                'tickets.view.own',
                'tickets.comment',
                'billing.view.own',
                'portal.access',
            ]
        ]);

        // Billing Administrator - Specialized billing and invoicing role
        RoleTemplate::updateOrCreate(['name' => 'Billing Administrator'], [
            'description' => 'Specialized role for billing, invoicing, and financial management',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'billing.manage',
                'billing.rates',
                'billing.invoices',
                'billing.reports',
                'tickets.view.billing',
                'time.view.billable',
                'time.reports.billing',
            ]
        ]);

        // Support Agent - Focused on customer support tickets
        RoleTemplate::updateOrCreate(['name' => 'Support Agent'], [
            'description' => 'Customer support specialist with ticket management focus',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'tickets.create',
                'tickets.view.support',
                'tickets.edit.support',
                'tickets.transition',
                'tickets.comment',
                'time.track',
                'time.view.own',
                'time.edit.own',
            ]
        ]);

        // Developer - Technical role with development-specific permissions
        RoleTemplate::updateOrCreate(['name' => 'Developer'], [
            'description' => 'Technical development role with code and technical ticket focus',
            'is_system_role' => false,
            'is_default' => false,
            'permissions' => [
                'tickets.create',
                'tickets.view.development',
                'tickets.edit.development',
                'tickets.transition',
                'tickets.technical',
                'time.track',
                'time.view.own',
                'time.edit.own',
                'time.reports.own',
            ]
        ]);
    }
}
