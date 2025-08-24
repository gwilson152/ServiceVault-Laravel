<?php

namespace App\Policies;

use App\Models\EmailTemplate;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any email templates
     */
    public function viewAny(User $user): bool
    {
        // Super admins can view all templates
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can view templates
        return $user->hasPermission('account.manage_settings');
    }

    /**
     * Determine whether the user can view the email template
     */
    public function view(User $user, EmailTemplate $emailTemplate): bool
    {
        // Super admins can view any template
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can view their account's templates
        if ($user->hasPermission('account.manage_settings') && 
            $emailTemplate->account_id === $user->account_id) {
            return true;
        }

        // Global templates can be viewed by account managers
        if ($user->hasPermission('account.manage_settings') && 
            $emailTemplate->account_id === null) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create email templates
     */
    public function create(User $user): bool
    {
        // Super admins can create any template
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can create templates for their account
        return $user->hasPermission('account.manage_settings');
    }

    /**
     * Determine whether the user can update the email template
     */
    public function update(User $user, EmailTemplate $emailTemplate): bool
    {
        // Super admins can update any template
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can update their account's templates
        if ($user->hasPermission('account.manage_settings') && 
            $emailTemplate->account_id === $user->account_id) {
            return true;
        }

        // Account managers cannot modify global templates
        return false;
    }

    /**
     * Determine whether the user can delete the email template
     */
    public function delete(User $user, EmailTemplate $emailTemplate): bool
    {
        // Super admins can delete any template
        if ($user->hasPermission('system.configure')) {
            return true;
        }

        // Account managers can delete their account's templates
        if ($user->hasPermission('account.manage_settings') && 
            $emailTemplate->account_id === $user->account_id) {
            return true;
        }

        // Cannot delete global templates unless super admin
        return false;
    }
}