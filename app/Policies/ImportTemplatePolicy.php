<?php

namespace App\Policies;

use App\Models\ImportTemplate;
use App\Models\User;

class ImportTemplatePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('system.import');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImportTemplate $importTemplate): bool
    {
        return $user->hasPermission('system.import');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Only super admins can create custom templates
        return $user->hasPermission('system.configure');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImportTemplate $importTemplate): bool
    {
        // Only super admins can modify templates, and only non-system templates
        return $user->hasPermission('system.configure') && ! $importTemplate->is_system;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImportTemplate $importTemplate): bool
    {
        // Only super admins can delete templates, and only non-system templates
        return $user->hasPermission('system.configure') && ! $importTemplate->is_system;
    }
}
