<?php

namespace App\Services;

use App\Models\User;
use App\Models\Account;
use App\Models\RoleTemplate;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Collection;

class PermissionService
{
    /**
     * Check if a user has a specific permission for an account.
     */
    public function hasPermissionForAccount(User $user, string $permission, Account $account): bool
    {
        // Cache key for user permissions on this account
        $cacheKey = "user_permissions:{$user->id}:account:{$account->id}";

        // Cache permissions for 5 minutes as per original design
        return Cache::remember($cacheKey, 300, function () use ($user, $permission, $account) {
            // Check direct permissions for this account
            return $this->hasDirectPermission($user, $permission, $account);
        });
    }

    /**
     * Check if user has direct permission on specific account (no inheritance).
     */
    public function hasDirectPermission(User $user, string $permission, Account $account): bool
    {
        // Get user's role templates for this account
        $roleTemplates = $this->getUserRoleTemplatesForAccount($user, $account);

        // Check if any role template has the required permission
        return $roleTemplates->contains(function ($roleTemplate) use ($permission) {
            $permissions = $roleTemplate->permissions ?? [];
            return in_array($permission, $permissions) || in_array('*', $permissions);
        });
    }

    /**
     * Get all role templates assigned to user for specific account.
     */
    public function getUserRoleTemplatesForAccount(User $user, Account $account): Collection
    {
        // Get role templates directly from user (simplified approach)
        // In a more complex setup, this could filter by account context
        return $user->roleTemplates ?? collect();
    }

    /**
     * Get all permissions a user has for an account.
     */
    public function getUserPermissionsForAccount(User $user, Account $account): array
    {
        $cacheKey = "user_all_permissions:{$user->id}:account:{$account->id}";

        return Cache::remember($cacheKey, 300, function () use ($user, $account) {
            $permissions = [];

            // Get direct permissions for this account
            $roleTemplates = $this->getUserRoleTemplatesForAccount($user, $account);
            foreach ($roleTemplates as $roleTemplate) {
                $permissions = array_merge($permissions, $roleTemplate->permissions ?? []);
            }

            return array_unique($permissions);
        });
    }

    /**
     * Clear permission cache for user.
     */
    public function clearUserPermissionCache(User $user): void
    {
        Cache::forget("user_permissions:{$user->id}:*");
        Cache::forget("user_all_permissions:{$user->id}:*");
    }

    /**
     * Get accounts accessible to user with specific permission.
     */
    public function getAccessibleAccounts(User $user, string $permission = null): Collection
    {
        $cacheKey = "user_accessible_accounts:{$user->id}:" . ($permission ?? 'any');

        return Cache::remember($cacheKey, 300, function () use ($user, $permission) {
            // Get user's accessible accounts (for now, just their primary account)
            $userAccounts = $user->accounts();

            if (!$permission) {
                return $userAccounts;
            }

            return $userAccounts->filter(function ($account) use ($user, $permission) {
                return $this->hasPermissionForAccount($user, $permission, $account);
            });
        });
    }

    /**
     * Default permissions for role templates.
     */
    public static function getDefaultRolePermissions(): array
    {
        return [
            'super_administrator' => [
                'system.manage_roles',
                'system.manage_licenses',
                'system.full_access',
                '*'
            ],
            'system_administrator' => [
                'system.manage_users',
                'system.manage_settings',
                'accounts.create',
                'accounts.manage_all'
            ],
            'account_manager' => [
                'accounts.manage',
                'users.assign',
                'rates.customize',
                'reports.view_account',
                // Basic billing permissions for account managers
                'billing.view.account',
                'invoices.view.account',
                'payments.view.account',
                'widgets.billing.overview'
            ],
            'team_lead' => [
                'team.oversight',
                'timeentries.approve',
                'projects.manage',
                'reports.view_team'
            ],
            'employee' => [
                'timers.manage_own',
                'timeentries.create',
                'tickets.manage_assigned',
                'reports.view_own'
            ],
            'customer' => [
                'portal.access',
                'tickets.view_own',
                'timeentries.view_own',
                // Customer billing permissions
                'billing.view.own',
                'invoices.view.own',
                'payments.view.own',
                'pages.billing.own'
            ],
            'billing_administrator' => [
                // Full billing management access
                'billing.full_access',
                'billing.admin',
                'billing.configure',
                'billing.manage',
                'billing.view.all',
                'billing.reports',
                
                // Invoice management
                'invoices.create',
                'invoices.edit',
                'invoices.delete',
                'invoices.view.all',
                'invoices.send',
                'invoices.generate',
                'invoices.void',
                'invoices.duplicate',
                
                // Payment management
                'payments.create',
                'payments.edit',
                'payments.delete',
                'payments.view.all',
                'payments.track',
                'payments.refund',
                'payments.process',
                
                // Billing rates
                'billing.rates.create',
                'billing.rates.edit',
                'billing.rates.delete',
                'billing.rates.view',
                'billing.rates.manage',
                
                // Addons and templates
                'billing.addons.create',
                'billing.addons.edit',
                'billing.addons.delete',
                'billing.addons.view',
                'billing.addons.manage',
                
                // Widget permissions
                'widgets.billing.overview',
                'widgets.billing.invoices',
                'widgets.billing.payments',
                'widgets.billing.rates',
                
                // Page permissions
                'pages.billing.overview',
                'pages.billing.invoices',
                'pages.billing.payments',
                'pages.billing.rates',
                'pages.billing.reports'
            ],
            
            'billing_manager' => [
                // Billing management (account-scoped)
                'billing.manage',
                'billing.view.account',
                'billing.reports',
                
                // Invoice management (account-scoped)
                'invoices.create',
                'invoices.edit.account',
                'invoices.view.account',
                'invoices.send',
                'invoices.generate',
                
                // Payment management (account-scoped)
                'payments.create',
                'payments.edit.account',
                'payments.view.account',
                'payments.track',
                
                // Billing rates (account-scoped)
                'billing.rates.view',
                'billing.rates.manage.account',
                
                // Widget permissions
                'widgets.billing.overview',
                'widgets.billing.invoices',
                'widgets.billing.payments',
                
                // Page permissions
                'pages.billing.overview',
                'pages.billing.invoices',
                'pages.billing.payments'
            ],
            
            'billing_clerk' => [
                // Basic billing operations
                'billing.view.account',
                
                // Invoice operations
                'invoices.create',
                'invoices.view.account',
                'invoices.send',
                
                // Payment operations
                'payments.create',
                'payments.view.account',
                
                // Widget permissions
                'widgets.billing.overview',
                'widgets.billing.invoices',
                'widgets.billing.payments',
                
                // Page permissions
                'pages.billing.overview'
            ]
        ];
    }

    /**
     * Check if user has system-level permission (not account-specific).
     */
    public function hasSystemPermission(User $user, string $permission): bool
    {
        // Check if user is super admin first (like User::hasPermission() does)
        if ($user->isSuperAdmin()) {
            return true;
        }

        $cacheKey = "user_system_permissions:{$user->id}";

        $systemPermissions = Cache::remember($cacheKey, 300, function () use ($user) {
            // Get system role templates (is_system_role = true)
            return $user->roleTemplates()
                ->where('is_system_role', true)
                ->get()
                ->flatMap(fn($role) => $role->permissions ?? [])
                ->unique()
                ->values()
                ->toArray();
        });

        return in_array($permission, $systemPermissions) || in_array('*', $systemPermissions);
    }

    /**
     * Filter users who have any of the specified permissions.
     * This properly handles Super Admin logic and other permission edge cases.
     *
     * @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Support\Collection $users
     * @param array $permissions
     * @return \Illuminate\Support\Collection
     */
    public static function filterUsersByPermissions($users, array $permissions): Collection
    {
        // If we have a query builder, execute it first
        if ($users instanceof \Illuminate\Database\Eloquent\Builder) {
            $users = $users->get();
        }

        return $users->filter(function ($user) use ($permissions) {
            return $user->hasAnyPermission($permissions);
        });
    }

    /**
     * Get users who can act as agents for a specific type.
     * This is a centralized method that all controllers should use.
     *
     * @param string $agentType ('timer', 'ticket', 'time', 'billing')
     * @param array $filters Additional filters (search, account_id)
     * @return Collection
     */
    public static function getAgentsForType(string $agentType, array $filters = []): Collection
    {
        $requiredPermissions = static::getAgentPermissions($agentType);
        
        // Start with all active users with role templates
        $query = User::with(['account', 'roleTemplate'])
            ->where('is_active', true)
            ->whereNotNull('role_template_id');
        
        // Apply search filter if provided
        if (!empty($filters['search'])) {
            $searchTerm = $filters['search'];
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }
        
        // Apply account filter if provided
        if (!empty($filters['account_id'])) {
            $query->where('account_id', $filters['account_id']);
        }
        
        // Order by name
        $query->orderBy('name');
        
        // Filter by permissions using centralized logic
        return static::filterUsersByPermissions($query, $requiredPermissions);
    }

    /**
     * Get the permissions required to be an agent for a specific type.
     * This centralizes the agent permission logic.
     *
     * @param string $agentType
     * @return array
     */
    public static function getAgentPermissions(string $agentType): array
    {
        return match($agentType) {
            'timer' => ['timers.act_as_agent', 'timers.assign', 'timers.manage', 'admin.write'],
            'ticket' => ['tickets.act_as_agent', 'tickets.assign', 'tickets.manage', 'admin.write'],
            'time' => ['time.act_as_agent', 'time.assign', 'time.manage', 'admin.write'],
            'billing' => ['billing.act_as_agent', 'billing.manage', 'billing.admin', 'admin.write'],
            default => ['timers.act_as_agent', 'timers.assign', 'timers.manage', 'admin.write']
        };
    }

    /**
     * Check if a user can view agents of a specific type.
     * This is used to determine if a user should see agent selection dropdowns.
     *
     * @param User $user
     * @param string $agentType
     * @return bool
     */
    public static function canViewAgents(User $user, string $agentType): bool
    {
        $viewPermissions = match($agentType) {
            'timer' => ['timers.assign', 'timers.manage', 'admin.write'],
            'ticket' => ['tickets.assign', 'tickets.manage', 'admin.write'],
            'time' => ['time.assign', 'time.manage', 'admin.write'],
            'billing' => ['billing.manage', 'billing.admin', 'admin.write'],
            default => ['timers.assign', 'timers.manage', 'admin.write']
        };

        return $user->hasAnyPermission($viewPermissions);
    }

    /**
     * Check if a user can assign work to others (and therefore see assignment dropdowns).
     *
     * @param User $user
     * @param string $workType ('timer', 'ticket', 'time', 'billing')
     * @return bool
     */
    public static function canAssignToOthers(User $user, string $workType = 'time'): bool
    {
        return static::canViewAgents($user, $workType);
    }
}
