<?php

namespace App\Policies;

use App\Models\Timer;
use App\Models\User;

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

        // Check if user has timer viewing permissions
        return $user->hasAnyPermission(['timers.read', 'timers.write', 'time.track']);
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

        // Check if user has timer creation permissions
        return $user->hasAnyPermission(['timers.create', 'timers.write', 'time.track']);
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

        // Check if user has timer permissions and owns the timer
        return $user->hasAnyPermission(['timers.write', 'time.track']) && $timer->user_id === $user->id;
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

        // Check if user has timer permissions and owns the timer
        return $user->hasAnyPermission(['timers.write', 'time.track']) && $timer->user_id === $user->id;
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

        // Check if user has timer permissions
        return $user->hasAnyPermission(['timers.write', 'time.track']);
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
