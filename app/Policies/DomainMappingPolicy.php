<?php

namespace App\Policies;

use App\Models\DomainMapping;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class DomainMappingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Only System Administrators and Account Managers can manage domain mappings
        return $user->hasAnyPermission([
            'manage_system_settings',
            'manage_account_settings',
            'manage_domain_mappings'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, DomainMapping $domainMapping): bool
    {
        // Can view if they can manage domain mappings or if it's for their account
        return $this->viewAny($user) || 
               $user->hasPermissionForAccount('view_account_settings', $domainMapping->account);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'manage_system_settings',
            'manage_account_settings',
            'manage_domain_mappings'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, DomainMapping $domainMapping): bool
    {
        // System admins can update any, account managers can update their account's mappings
        return $user->hasPermission('manage_system_settings') ||
               $user->hasPermissionForAccount('manage_account_settings', $domainMapping->account);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, DomainMapping $domainMapping): bool
    {
        return $this->update($user, $domainMapping);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, DomainMapping $domainMapping): bool
    {
        return $this->delete($user, $domainMapping);
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, DomainMapping $domainMapping): bool
    {
        // Only system administrators can permanently delete
        return $user->hasPermission('manage_system_settings');
    }
}
