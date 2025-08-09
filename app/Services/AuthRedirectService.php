<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;

class AuthRedirectService
{
    public function getRedirectPath(User $user): string
    {
        // Cache user roles for 5 minutes to avoid repeated database queries
        $userRoles = Cache::remember("user_roles_{$user->id}", 300, function () use ($user) {
            return $user->roles()->with('roleTemplate')->get();
        });
        
        // Check for admin roles first
        if ($this->hasAdminRole($userRoles)) {
            return route('dashboard.admin.index');
        }
        
        // Check for manager roles
        if ($this->hasManagerRole($userRoles)) {
            return route('dashboard.manager.index');
        }
        
        // Check for customer/client roles
        if ($this->hasCustomerRole($userRoles)) {
            return route('portal.index');
        }
        
        // Default to employee dashboard
        return route('dashboard.employee.index');
    }
    
    private function hasAdminRole($roles): bool
    {
        return $roles->some(function ($role) {
            // Check if it's Super Admin (always has admin permissions)
            if ($role->roleTemplate->isSuperAdmin()) {
                return true;
            }
            
            $permissions = $role->roleTemplate->getAllPermissions();
            return in_array('admin.manage', $permissions) || 
                   in_array('system.manage', $permissions) || 
                   in_array('accounts.create', $permissions);
        });
    }
    
    private function hasManagerRole($roles): bool
    {
        return $roles->some(function ($role) {
            // Super Admin has all permissions including manager permissions
            if ($role->roleTemplate->isSuperAdmin()) {
                return true;
            }
            
            $permissions = $role->roleTemplate->getAllPermissions();
            return in_array('teams.manage', $permissions) || 
                   in_array('team.manage', $permissions) ||
                   in_array('projects.manage', $permissions);
        });
    }
    
    private function hasCustomerRole($roles): bool
    {
        return $roles->some(function ($role) {
            // Super Admin should not be treated as customer
            if ($role->roleTemplate->isSuperAdmin()) {
                return false;
            }
            
            $permissions = $role->roleTemplate->getAllPermissions();
            return in_array('portal.access', $permissions) && 
                   !in_array('time.track', $permissions) &&
                   !in_array('timers.create', $permissions);
        });
    }
}