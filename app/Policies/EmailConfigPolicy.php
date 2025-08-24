<?php

namespace App\Policies;

use App\Models\EmailConfig;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailConfigPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any email configurations
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all configurations
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can view their account's configurations
        return $user->hasPermission('account.manage_settings');
    }

    /**
     * Determine whether the user can view the email configuration
     */
    public function view(User $user, EmailConfig $emailConfig): bool
    {
        // Super admins can view any configuration
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can view their account's configurations
        if ($user->hasPermission('account.manage_settings') && 
            $emailConfig->account_id === $user->account_id) {
            return true;
        }

        // Global configurations can be viewed by account managers
        if ($user->hasPermission('account.manage_settings') && 
            $emailConfig->account_id === null) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create email configurations
     */
    public function create(User $user): bool
    {
        // Super admins can create any configuration
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can create configurations for their account
        return $user->hasPermission('account.manage_settings');
    }

    /**
     * Determine whether the user can update the email configuration
     */
    public function update(User $user, EmailConfig $emailConfig): bool
    {
        // Super admins can update any configuration
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can update their account's configurations
        if ($user->hasPermission('account.manage_settings') && 
            $emailConfig->account_id === $user->account_id) {
            return true;
        }

        // Account managers cannot modify global configurations
        return false;
    }

    /**
     * Determine whether the user can delete the email configuration
     */
    public function delete(User $user, EmailConfig $emailConfig): bool
    {
        // Super admins can delete any configuration
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can delete their account's configurations
        if ($user->hasPermission('account.manage_settings') && 
            $emailConfig->account_id === $user->account_id) {
            return true;
        }

        // Cannot delete global configurations unless super admin
        return false;
    }

    /**
     * Determine whether the user can test the email configuration
     */
    public function test(User $user, EmailConfig $emailConfig): bool
    {
        // Same permissions as update - if you can modify it, you can test it
        return $this->update($user, $emailConfig);
    }
}