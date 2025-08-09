<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DashboardRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Determines the appropriate dashboard interface based on user's role templates
     * and account permissions.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $dashboardType = null): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return redirect()->route('login');
        }

        // Get user's primary dashboard type based on highest permission level
        $userDashboardType = $this->determinePrimaryDashboardType($user);
        
        // If no specific dashboard type required, redirect to appropriate one
        if (!$dashboardType) {
            return redirect()->route("dashboard.{$userDashboardType}.index");
        }
        
        // Check if user has access to requested dashboard type
        if (!$this->userCanAccessDashboard($user, $dashboardType)) {
            // Redirect to their primary dashboard
            return redirect()->route("dashboard.{$userDashboardType}.index")
                ->with('error', 'You do not have access to that dashboard.');
        }
        
        // Share dashboard context with views
        $request->merge([
            'dashboardType' => $dashboardType,
            'userPrimaryDashboard' => $userDashboardType
        ]);

        return $next($request);
    }
    
    /**
     * Determine the primary dashboard type for a user
     */
    private function determinePrimaryDashboardType($user): string
    {
        // Super Admin always goes to admin dashboard
        if ($user->isSuperAdmin()) {
            return 'admin';
        }
        
        // Check for admin permissions (highest priority)
        if ($user->hasPermission('admin.manage') || 
            $user->hasPermission('system.manage') || 
            $user->hasPermission('accounts.create')) {
            return 'admin';
        }
        
        // Check for manager permissions
        if ($user->hasPermission('teams.manage') || 
            $user->hasPermission('team.manage') || 
            $user->hasPermission('projects.manage')) {
            return 'manager';
        }
        
        // Check if user is a customer (portal access only)
        if ($user->hasPermission('portal.access') &&
            !$user->hasPermission('time.track') &&
            !$user->hasPermission('timers.create')) {
            return 'portal';
        }
        
        // Default to employee dashboard
        return 'employee';
    }
    
    /**
     * Check if user can access a specific dashboard type
     */
    private function userCanAccessDashboard($user, string $dashboardType): bool
    {
        // Super Admin can access any dashboard
        if ($user->isSuperAdmin()) {
            return true;
        }
        
        switch ($dashboardType) {
            case 'admin':
                return $user->hasPermission('admin.manage') ||
                       $user->hasPermission('system.manage') ||
                       $user->hasPermission('accounts.create');
                
            case 'manager':
                return $user->hasPermission('teams.manage') ||
                       $user->hasPermission('team.manage') ||
                       $user->hasPermission('projects.manage') ||
                       $user->hasPermission('admin.manage') ||
                       $user->hasPermission('system.manage');
                       
            case 'employee':
                return $user->hasPermission('time.track') ||
                       $user->hasPermission('timers.create');
                
            case 'portal':
                // Portal access is available to customers and can be accessed by others for account viewing
                return $user->hasPermission('portal.access') ||
                       $user->hasPermission('accounts.view');
                
            default:
                return false;
        }
    }
}
