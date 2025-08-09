<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\RoleTemplateFactory> */
    use HasFactory;
    
    protected $fillable = [
        'name',
        'permissions',
        'is_system_role',
        'is_default',
        'description',
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'is_system_role' => 'boolean',
        'is_default' => 'boolean',
    ];
    
    public function roles()
    {
        return $this->hasMany(Role::class);
    }
    
    /**
     * Check if this role template is Super Administrator and should inherit all permissions
     */
    public function isSuperAdmin(): bool
    {
        return $this->name === 'Super Administrator';
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
        
        return $this->permissions ?? [];
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
            'accounts.configure',
            'accounts.view',
            
            // User Management
            'users.manage',
            'users.invite',
            'users.assign',
            
            // Role Management
            'roles.manage',
            'role_templates.manage',
            
            // Team Management
            'teams.manage',
            'team.manage',
            'projects.manage',
            'projects.view',
            
            // Billing & Financial
            'billing.manage',
            'billing.configure',
            'billing.rates',
            'billing.invoices',
            'billing.reports',
            'billing.view.own',
            
            // Service Tickets - Admin Level
            'tickets.admin',
            'tickets.create',
            'tickets.create.basic',
            'tickets.create.request',
            'tickets.view.all',
            'tickets.view.account',
            'tickets.view.team',
            'tickets.view.assigned',
            'tickets.view.own',
            'tickets.view.support',
            'tickets.view.development',
            'tickets.view.billing',
            'tickets.edit.all',
            'tickets.edit.account',
            'tickets.edit.team',
            'tickets.edit.own',
            'tickets.edit.support',
            'tickets.edit.development',
            'tickets.assign',
            'tickets.assign.basic',
            'tickets.transition',
            'tickets.transition.basic',
            'tickets.close',
            'tickets.delete',
            'tickets.comment',
            'tickets.technical',
            
            // Time Tracking & Management
            'time.admin',
            'time.track',
            'time.manage',
            'time.view.all',
            'time.view.team',
            'time.view.own',
            'time.view.billable',
            'time.edit.all',
            'time.edit.own',
            'time.approve',
            'time.reports',
            'time.reports.all',
            'time.reports.account',
            'time.reports.team',
            'time.reports.own',
            'time.reports.billing',
            
            // Legacy Timer Permissions (backward compatibility)
            'timers.create',
            'timers.manage',
            'timers.sync',
            'timers.read',
            'timers.write',
            'timers.delete',
            
            // Customer Portal
            'portal.access',
            
            // Settings Management
            'settings.manage',
        ];
    }
}
