<?php

namespace App\Policies;

use App\Models\ImportJob;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImportJobPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'system.import',
            'system.import.execute',
            'import.jobs.execute',
            'import.jobs.monitor'
        ]);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ImportJob $importJob): bool
    {
        // Users can view jobs they created or if they have monitoring permissions
        if ($importJob->created_by === $user->id) {
            return true;
        }

        return $user->hasAnyPermission([
            'system.import',
            'import.jobs.monitor'
        ]);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasAnyPermission([
            'system.import.execute',
            'import.jobs.execute'
        ]);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ImportJob $importJob): bool
    {
        // Import jobs generally shouldn't be updatable after creation
        // Only allow for administrative purposes
        return $user->hasPermission('system.import');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ImportJob $importJob): bool
    {
        // Only allow deletion of completed/failed jobs by admins or job creators
        if (!in_array($importJob->status, ['completed', 'failed', 'cancelled'])) {
            return false;
        }

        if ($importJob->created_by === $user->id) {
            return true;
        }

        return $user->hasPermission('system.import');
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, ImportJob $importJob): bool
    {
        // Only allow cancellation of running/pending jobs
        if (!in_array($importJob->status, ['running', 'pending'])) {
            return false;
        }

        // Users can cancel their own jobs or if they have admin permissions
        if ($importJob->created_by === $user->id) {
            return true;
        }

        return $user->hasAnyPermission([
            'system.import',
            'import.jobs.monitor'
        ]);
    }

    /**
     * Determine whether the user can get status of the model.
     */
    public function getStatus(User $user, ImportJob $importJob): bool
    {
        // Users can get status of jobs they created or if they have monitoring permissions
        if ($importJob->created_by === $user->id) {
            return true;
        }

        return $user->hasAnyPermission([
            'system.import',
            'import.jobs.monitor'
        ]);
    }

    /**
     * Determine whether the user can execute import.
     */
    public function execute(User $user): bool
    {
        return $user->hasAnyPermission([
            'system.import.execute',
            'import.jobs.execute'
        ]);
    }

    /**
     * Determine whether the user can monitor all jobs.
     */
    public function monitor(User $user): bool
    {
        return $user->hasAnyPermission([
            'system.import',
            'import.jobs.monitor'
        ]);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ImportJob $importJob): bool
    {
        return $user->hasPermission('system.import');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ImportJob $importJob): bool
    {
        return $user->hasPermission('system.import');
    }
}