<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// User-specific private channels
Broadcast::channel('user.{userId}', function (User $user, string $userId) {
    return (int) $user->id === (int) $userId;
});

// Timer synchronization channels
Broadcast::channel('user.{userId}.timers', function (User $user, string $userId) {
    return (int) $user->id === (int) $userId;
});

// Account-wide channels (for managers/admins)
Broadcast::channel('account.{accountId}', function (User $user, string $accountId) {
    return $user->hasPermissionForAccount('view_account_data', $accountId);
});

// Account timer channels (for team leads to monitor team timers)
Broadcast::channel('account.{accountId}.timers', function (User $user, string $accountId) {
    return $user->hasPermissionForAccount('manage_team_timers', $accountId) ||
           $user->hasPermissionForAccount('view_team_timers', $accountId);
});

// System-wide admin channels
Broadcast::channel('admin.system', function (User $user) {
    return $user->hasPermission('manage_system');
});

// Account management channels
Broadcast::channel('admin.accounts', function (User $user) {
    return $user->hasAnyPermission([
        'manage_system',
        'manage_accounts',
        'view_all_accounts'
    ]);
});

// Ticket-specific channels for real-time messaging
Broadcast::channel('ticket.{ticketId}', function (User $user, string $ticketId) {
    $ticket = \App\Models\Ticket::find($ticketId);
    return $ticket && $ticket->canBeViewedBy($user);
});