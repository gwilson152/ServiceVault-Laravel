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
     * Implements hierarchical permission inheritance through account relationships.
     */
    public function hasPermissionForAccount(User $user, string $permission, Account $account): bool
    {
        // Cache key for user permissions on this account
        $cacheKey = "user_permissions:{$user->id}:account:{$account->id}";

        // Cache permissions for 5 minutes as per original design
        return Cache::remember($cacheKey, 300, function () use ($user, $permission, $account) {
            // Check direct permissions for this account
            if ($this->hasDirectPermission($user, $permission, $account)) {
                return true;
            }

            // Check inherited permissions from parent accounts
            return $account->ancestors()->contains(function ($parentAccount) use ($user, $permission) {
                return $this->hasDirectPermission($user, $permission, $parentAccount);
            });
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
     * Get all permissions a user has for an account (including inherited).
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

            // Get inherited permissions from parent accounts
            foreach ($account->ancestors() as $parentAccount) {
                $parentRoleTemplates = $this->getUserRoleTemplatesForAccount($user, $parentAccount);
                foreach ($parentRoleTemplates as $roleTemplate) {
                    $permissions = array_merge($permissions, $roleTemplate->permissions ?? []);
                }
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
            $userAccounts = $user->accounts()->get();

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
                'reports.view_account'
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
                'invoices.view_own',
                'timeentries.view_own'
            ],
            'billing_administrator' => [
                'invoices.generate',
                'payments.track',
                'rates.manage',
                'billing.full_access'
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
}
