<?php

namespace App\Policies;

use App\Models\TimeEntry;
use App\Models\User;

class TimeEntryPolicy
{
    /**
     * Determine whether the user can view any time entries.
     */
    public function viewAny(User $user): bool
    {
        // Users can view time entries if they have any time viewing permissions
        return $user->hasAnyPermission([
            'time.view.own',
            'time.view.all',
            'time.view.account',
            'time.view.team',
            'time.manage',
            'admin.manage',
            'admin.write',
        ]);
    }

    /**
     * Determine whether the user can view the specific time entry.
     */
    public function view(User $user, TimeEntry $timeEntry): bool
    {
        // Users can view their own time entries
        if ($timeEntry->user_id === $user->id) {
            return true;
        }

        // Service providers and managers can view time entries for accounts they manage
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.view.all', 'teams.manage', 'admin.manage', 'admin.write'])) {
            return true;
        }

        // Team members can view team time entries (if they have team permissions)
        if ($user->hasPermission('time.view.team')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can create time entries.
     */
    public function create(User $user): bool
    {
        // Users need time tracking permissions to create time entries
        return $user->hasAnyPermission([
            'time.track',
            'time.manage',
            'timers.create',
            'admin.manage',
            'admin.write',
        ]);
    }

    /**
     * Determine whether the user can update the time entry.
     */
    public function update(User $user, TimeEntry $timeEntry): bool
    {
        // Can't edit non-pending entries
        if ($timeEntry->status !== 'pending') {
            return false;
        }

        // Original creator can always edit their own pending entries
        if ($timeEntry->user_id === $user->id) {
            return true;
        }

        // Service providers and users with time management permissions can edit time entries
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.edit.all', 'admin.manage', 'admin.write'])) {
            return true;
        }

        // Team managers can edit entries from their team members
        if ($user->hasPermission('time.edit.team') || $user->hasPermission('teams.manage')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the time entry.
     */
    public function delete(User $user, TimeEntry $timeEntry): bool
    {
        // Can only delete pending entries
        if ($timeEntry->status !== 'pending') {
            return false;
        }

        // Original creator can always delete their own pending entries
        if ($timeEntry->user_id === $user->id) {
            return true;
        }

        // Service providers and managers can delete time entries
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.delete.all', 'admin.manage', 'admin.write'])) {
            return true;
        }

        // Team managers can delete entries from their team members
        if ($user->hasPermission('time.edit.team') || $user->hasPermission('teams.manage')) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can approve/reject time entries.
     */
    public function approve(User $user, TimeEntry $timeEntry): bool
    {
        // Entry must be pending
        if ($timeEntry->status !== 'pending') {
            return false;
        }

        // Can't approve your own entries
        if ($timeEntry->user_id === $user->id) {
            return false;
        }

        // Service providers, managers, and admins can approve
        if ($user->user_type === 'service_provider' ||
            $user->hasAnyPermission(['time.manage', 'time.approve', 'teams.manage', 'admin.manage', 'admin.write'])) {
            return true;
        }

        return false;
    }

    /**
     * Determine whether the user can restore the time entry.
     */
    public function restore(User $user, TimeEntry $timeEntry): bool
    {
        // Only admins can restore deleted time entries
        return $user->hasAnyPermission(['admin.manage', 'admin.write']);
    }

    /**
     * Determine whether the user can permanently delete the time entry.
     */
    public function forceDelete(User $user, TimeEntry $timeEntry): bool
    {
        // Only admins can force delete time entries
        return $user->hasAnyPermission(['admin.manage', 'admin.write']);
    }
}
