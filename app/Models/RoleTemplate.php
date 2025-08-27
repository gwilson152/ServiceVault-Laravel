<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\RoleTemplateFactory> */
    use HasFactory, HasUuid;

    protected $fillable = [
        'name',
        'description',
        'is_system_role',
        'permissions',
        'widget_permissions',
        'page_permissions',
        'is_active',
    ];

    protected $casts = [
        'permissions' => 'array',
        'widget_permissions' => 'array',
        'page_permissions' => 'array',
        'is_system_role' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the display name attribute, falling back to name.
     */
    public function getDisplayNameAttribute()
    {
        return $this->name;
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function widgets()
    {
        return $this->hasMany(RoleTemplateWidget::class);
    }

    public function widgetPermissions()
    {
        return $this->belongsToMany(WidgetPermission::class, 'role_template_widgets', 'role_template_id', 'widget_id', 'id', 'widget_id');
    }

    public function pagePermissions()
    {
        return $this->belongsToMany(PagePermission::class, 'role_template_pages', 'role_template_id', 'page_permission_id');
    }

    /**
     * Check if this role template is Super Administrator and should inherit all permissions
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === 'Super Admin';
    }

    /**
     * Get all permissions for this role template, including inherited permissions for Super Admin
     */
    public function getAllPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            // Super Admin gets ALL possible ABAC permissions
            return $this->getAllPossiblePermissions();
        }

        // Merge all three permission dimensions for comprehensive permission checking
        return array_unique(array_merge(
            $this->permissions ?? [],
            $this->widget_permissions ?? [],
            $this->page_permissions ?? []
        ));
    }

    /**
     * Get all possible ABAC permissions in the system
     */
    public function getAllPossiblePermissions(): array
    {
        return [
            // System Administration
            'admin.manage',
            'system.configure',
            'system.manage',

            // Account Management
            'accounts.create',
            'accounts.manage',
            'accounts.hierarchy.access',
            'accounts.manage.own',
            'accounts.configure',
            'accounts.view',

            // User Management
            'users.manage',
            'users.manage.account',
            'users.invite',
            'users.assign',

            // Role Management
            'roles.manage',
            'role_templates.manage',

            // Billing & Financial Management
            'billing.full_access',
            'billing.admin',
            'billing.configure',
            'billing.manage',
            'billing.view.all',
            'billing.view.account',
            'billing.view.own',
            'billing.reports',

            // Invoice Management
            'invoices.create',
            'invoices.edit',
            'invoices.edit.account',
            'invoices.delete',
            'invoices.view.all',
            'invoices.view.account',
            'invoices.view.own',
            'invoices.send',
            'invoices.generate',
            'invoices.void',
            'invoices.duplicate',

            // Payment Management
            'payments.create',
            'payments.edit',
            'payments.edit.account',
            'payments.delete',
            'payments.view.all',
            'payments.view.account',
            'payments.view.own',
            'payments.track',
            'payments.refund',
            'payments.process',

            // Billing Rates Management
            'billing.rates.create',
            'billing.rates.edit',
            'billing.rates.delete',
            'billing.rates.view',
            'billing.rates.manage',
            'billing.rates.manage.account',

            // Billing Addons & Templates
            'billing.addons.create',
            'billing.addons.edit',
            'billing.addons.delete',
            'billing.addons.view',
            'billing.addons.manage',

            // Service Tickets - Updated for account-scoped permissions
            'tickets.admin',
            'tickets.create',
            'tickets.create.account',
            'tickets.create.request',
            'tickets.view.all',
            'tickets.view.account',
            'tickets.view.assigned',
            'tickets.view.own',
            'tickets.edit.all',
            'tickets.edit.account',
            'tickets.edit.own',
            'tickets.assign',
            'tickets.assign.account',
            'tickets.transition',
            'tickets.close',
            'tickets.delete',
            'tickets.comment',

            // Time Tracking & Management
            'time.admin',
            'time.track',
            'time.manage',
            'time.view.all',
            'time.view.account',
            'time.view.own',
            'time.edit.all',
            'time.edit.account',
            'time.edit.own',
            'time.approve',
            'time.reports',
            'time.reports.account',
            'time.reports.own',

            // Legacy Timer Permissions (backward compatibility)
            'timers.create',
            'timers.manage',
            'timers.sync',
            'timers.read',
            'timers.write',
            'timers.delete',

            // Feature-Specific Agent Designations
            'timers.act_as_agent',
            'tickets.act_as_agent',
            'time.act_as_agent',
            'billing.act_as_agent',

            // Customer Portal
            'portal.access',

            // Settings Management
            'settings.manage',

            // Import System Management
            'system.import',
            'system.import.configure',
            'system.import.execute',
            'import.profiles.manage',
            'import.jobs.execute',
            'import.jobs.monitor',
        ];
    }

    /**
     * Get all widget permissions for this role template
     */
    public function getAllWidgetPermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllPossibleWidgetPermissions();
        }

        return $this->widget_permissions ?? [];
    }

    /**
     * Get all page permissions for this role template
     */
    public function getAllPagePermissions(): array
    {
        if ($this->isSuperAdmin()) {
            return $this->getAllPossiblePagePermissions();
        }

        return $this->page_permissions ?? [];
    }

    /**
     * Get all possible widget permissions in the system
     */
    public function getAllPossibleWidgetPermissions(): array
    {
        return [
            // Dashboard Widgets
            'widgets.dashboard.system-health',
            'widgets.dashboard.system-stats',
            'widgets.dashboard.user-management',
            'widgets.dashboard.account-management',
            'widgets.dashboard.ticket-overview',
            'widgets.dashboard.my-tickets',
            'widgets.dashboard.time-tracking',
            'widgets.dashboard.all-timers',
            'widgets.dashboard.account-activity',
            'widgets.dashboard.quick-actions',

            // Billing Widgets
            'widgets.billing.overview',
            'widgets.billing.invoices',
            'widgets.billing.payments',
            'widgets.billing.rates',
            'widgets.dashboard.billing-overview',

            // Widget Configuration
            'widgets.configure',
            'dashboard.customize',
        ];
    }

    /**
     * Get all possible page permissions in the system
     */
    public function getAllPossiblePagePermissions(): array
    {
        return [
            // Administrative Pages
            'pages.admin.system',
            'pages.admin.users',
            'pages.settings.roles',

            // Ticket Management
            'pages.tickets.manage',
            'pages.tickets.create',

            // Reports
            'pages.reports.account',
            'pages.reports.own',
            'pages.reports.billing',

            // Billing Pages
            'pages.billing.overview',
            'pages.billing.invoices',
            'pages.billing.payments',
            'pages.billing.rates',
            'pages.billing.reports',
            'pages.billing.own',

            // Customer Portal
            'pages.portal.dashboard',
            'pages.portal.tickets',

            // Import Management
            'pages.import.manage',
            'pages.settings.import',
        ];
    }

    /**
     * Check if this role template has a specific widget permission
     */
    public function hasWidgetPermission(string $permission): bool
    {
        return in_array($permission, $this->getAllWidgetPermissions());
    }

    /**
     * Check if this role template has a specific page permission
     */
    public function hasPagePermission(string $permission): bool
    {
        return in_array($permission, $this->getAllPagePermissions());
    }

    /**
     * Check if this role template can be modified (system roles cannot be modified)
     */
    public function isModifiable(): bool
    {
        return !$this->is_system_role;
    }
}
