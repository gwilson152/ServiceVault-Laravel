<?php

namespace App\Policies;

use App\Models\ImportProfile;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImportProfilePolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check for any import-related permissions
        return $user->hasAnyPermission([
            'system.import',
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasAnyPermission([
            'system.import',
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasAnyPermission([
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasAnyPermission([
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can test connection for the model.
     */
    public function testConnection(User $user, ImportProfile $importProfile = null): bool
    {
        return $user->hasAnyPermission([
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can preview import for the model.
     */
    public function preview(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasAnyPermission([
            'system.import',
            'system.import.configure',
            'system.import.execute',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can execute import for the model.
     */
    public function execute(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasAnyPermission([
            'system.import.execute',
            'import.jobs.execute'
        ]);
    }

    /**
     * Determine whether the user can get schema for the model.
     */
    public function getSchema(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasAnyPermission([
            'system.import.configure',
            'import.profiles.manage'
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasPermission('system.import.configure');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImportProfile $importProfile): bool
    {
        return $user->hasPermission('system.import.configure');
    }
}