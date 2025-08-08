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
            return route('dashboard.admin');
        }
        
        // Check for manager roles
        if ($this->hasManagerRole($userRoles)) {
            return route('dashboard.manager');
        }
        
        // Check for customer/client roles
        if ($this->hasCustomerRole($userRoles)) {
            return route('portal.dashboard');
        }
        
        // Default to employee dashboard
        return route('dashboard.employee');
    }
    
    private function hasAdminRole($roles): bool
    {
        return $roles->some(function ($role) {
            $permissions = $role->roleTemplate->permissions ?? [];
            return in_array('system.manage', $permissions) || 
                   in_array('accounts.create', $permissions);
        });
    }
    
    private function hasManagerRole($roles): bool
    {
        return $roles->some(function ($role) {
            $permissions = $role->roleTemplate->permissions ?? [];
            return in_array('team.manage', $permissions) || 
                   in_array('projects.manage', $permissions);
        });
    }
    
    private function hasCustomerRole($roles): bool
    {
        return $roles->some(function ($role) {
            $permissions = $role->roleTemplate->permissions ?? [];
            return in_array('portal.access', $permissions) && 
                   !in_array('timers.create', $permissions);
        });
    }
}