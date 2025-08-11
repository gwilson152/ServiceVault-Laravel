<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AuthRedirectService
{
    public function getRedirectPath(User $user): string
    {
        // Cache user role template for 5 minutes to avoid repeated database queries
        $roleTemplate = Cache::remember("user_role_template_{$user->id}", 300, function () use ($user) {
            return $user->roleTemplate;
        });
        
        if (!$roleTemplate) {
            // Default to employee dashboard if no role template
            return route('dashboard.employee.index');
        }
        
        // Check for admin roles first
        if ($this->hasAdminRole($roleTemplate)) {
            return route('dashboard.admin.index');
        }
        
        // Check for manager roles
        if ($this->hasManagerRole($roleTemplate)) {
            return route('dashboard.manager.index');
        }
        
        // Check for customer/client roles
        if ($this->hasCustomerRole($roleTemplate)) {
            return route('portal.index');
        }
        
        // Default to employee dashboard
        return route('dashboard.employee.index');
    }
    
    private function hasAdminRole($roleTemplate): bool
    {
        // Check if it's Super Admin (always has admin permissions)
        if ($roleTemplate->isSuperAdmin()) {
            return true;
        }
        
        $permissions = $roleTemplate->getAllPermissions();
        return in_array('admin.manage', $permissions) || 
               in_array('system.manage', $permissions) || 
               in_array('accounts.create', $permissions);
    }
    
    private function hasManagerRole($roleTemplate): bool
    {
        // Super Admin has all permissions including manager permissions
        if ($roleTemplate->isSuperAdmin()) {
            return true;
        }
        
        $permissions = $roleTemplate->getAllPermissions();
        return in_array('teams.manage', $permissions) || 
               in_array('team.manage', $permissions) ||
               in_array('tickets.assign', $permissions);
    }
    
    private function hasCustomerRole($roleTemplate): bool
    {
        // Super Admin should not be treated as customer
        if ($roleTemplate->isSuperAdmin()) {
            return false;
        }
        
        $permissions = $roleTemplate->getAllPermissions();
        return in_array('portal.access', $permissions) && 
               !in_array('time.track', $permissions) &&
               !in_array('timers.create', $permissions);
    }
}