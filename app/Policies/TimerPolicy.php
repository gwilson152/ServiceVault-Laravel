<?php

namespace App\Policies;

use App\Models\Timer;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TimerPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:read');
        }

        // Default: allow all authenticated users to view their own timers
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Timer $timer): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:read') && $timer->user_id === $user->id;
        }

        // User can only view their own timers
        return $timer->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:write');
        }

        // Default: allow all authenticated users to create timers
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Timer $timer): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:write') && $timer->user_id === $user->id;
        }

        // User can only update their own timers
        return $timer->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Timer $timer): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:delete') && $timer->user_id === $user->id;
        }

        // User can only delete their own timers
        return $timer->user_id === $user->id;
    }

    /**
     * Determine whether the user can sync timers across devices.
     */
    public function sync(User $user): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:sync');
        }

        // Default: allow all authenticated users to sync timers
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Timer $timer): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:write') && $timer->user_id === $user->id;
        }

        return $timer->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Timer $timer): bool
    {
        // Check token abilities if authenticated via API
        if ($user->currentAccessToken()) {
            return $user->tokenCan('timers:delete') && $timer->user_id === $user->id;
        }

        return $timer->user_id === $user->id;
    }
}
