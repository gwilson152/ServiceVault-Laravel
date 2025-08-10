<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\DomainMappingController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TimerController;
use App\Http\Controllers\Api\TimeEntryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// API routes with authentication (web session + sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Token management routes
    Route::prefix('auth')->group(function () {
        Route::get('tokens', [App\Http\Controllers\Api\TokenController::class, 'index']);
        Route::post('tokens', [App\Http\Controllers\Api\TokenController::class, 'store']);
        Route::get('tokens/abilities', [App\Http\Controllers\Api\TokenController::class, 'abilities']);
        Route::post('tokens/scope', [App\Http\Controllers\Api\TokenController::class, 'createWithScope']);
        Route::delete('tokens/revoke-all', [App\Http\Controllers\Api\TokenController::class, 'revokeAll']);
        Route::get('tokens/{token}', [App\Http\Controllers\Api\TokenController::class, 'show']);
        Route::put('tokens/{token}', [App\Http\Controllers\Api\TokenController::class, 'update']);
        Route::delete('tokens/{token}', [App\Http\Controllers\Api\TokenController::class, 'destroy']);
    });
    
    // User management routes
    Route::get('users/assignable', [App\Http\Controllers\Api\UserController::class, 'assignableUsers'])
        ->name('users.assignable');
    
    // Timer routes
    Route::apiResource('timers', TimerController::class);
    
    // Additional timer endpoints
    Route::get('timers/active/current', [TimerController::class, 'current'])
        ->name('timers.current');
    
    Route::get('timers/user/active', [TimerController::class, 'active'])
        ->name('timers.active');
    
    Route::post('timers/{timer}/stop', [TimerController::class, 'stop'])
        ->name('timers.stop');
    
    Route::post('timers/{timer}/pause', [TimerController::class, 'pause'])
        ->name('timers.pause');
    
    Route::post('timers/{timer}/resume', [TimerController::class, 'resume'])
        ->name('timers.resume');
    
    Route::post('timers/{timer}/commit', [TimerController::class, 'commit'])
        ->name('timers.commit');
    
    Route::patch('timers/{timer}/duration', [TimerController::class, 'adjustDuration'])
        ->name('timers.adjust-duration');
    
    Route::post('timers/sync', [TimerController::class, 'sync'])
        ->name('timers.sync');
    
    Route::get('timers/user/statistics', [TimerController::class, 'statistics'])
        ->name('timers.statistics');
    
    Route::post('timers/bulk', [TimerController::class, 'bulk'])
        ->name('timers.bulk');
    
    // Ticket timer endpoints
    Route::get('tickets/{ticketId}/timers', [TimerController::class, 'forTicket'])
        ->name('tickets.timers');
    
    Route::get('tickets/{ticketId}/timers/active', [TimerController::class, 'activeForTicket'])
        ->name('tickets.timers.active');

    // Time Entry routes with approval workflow
    Route::apiResource('time-entries', TimeEntryController::class);
    
    // Time entry approval workflow endpoints
    Route::post('time-entries/{timeEntry}/approve', [TimeEntryController::class, 'approve'])
        ->name('time-entries.approve');
    
    Route::post('time-entries/{timeEntry}/reject', [TimeEntryController::class, 'reject'])
        ->name('time-entries.reject');
    
    Route::post('time-entries/bulk/approve', [TimeEntryController::class, 'bulkApprove'])
        ->name('time-entries.bulk-approve');
    
    Route::post('time-entries/bulk/reject', [TimeEntryController::class, 'bulkReject'])
        ->name('time-entries.bulk-reject');
    
    Route::get('time-entries/stats/approvals', [TimeEntryController::class, 'approvalStats'])
        ->name('time-entries.approval-stats');

    // Account routes (hierarchical selector support)
    Route::apiResource('accounts', AccountController::class);
    
    Route::get('accounts/selector/hierarchical', [AccountController::class, 'selector'])
        ->name('accounts.selector');

    // Domain mapping routes (admin/account manager access)
    Route::apiResource('domain-mappings', DomainMappingController::class);
    
    Route::post('domain-mappings/preview', [DomainMappingController::class, 'preview'])
        ->name('domain-mappings.preview');
    
    Route::get('domain-mappings/validate/requirements', [DomainMappingController::class, 'validateRequirements'])
        ->name('domain-mappings.validate-requirements');

    // User invitation routes (admin/manager access)
    Route::apiResource('user-invitations', App\Http\Controllers\UserInvitationController::class);

    // Ticket routes (comprehensive ticket management system)
    Route::apiResource('tickets', TicketController::class);
    
    // Ticket workflow and assignment endpoints
    Route::post('tickets/{ticket}/transition', [TicketController::class, 'transitionStatus'])
        ->name('tickets.transition');
    
    Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign'])
        ->name('tickets.assign');
    
    Route::get('tickets/stats/dashboard', [TicketController::class, 'statistics'])
        ->name('tickets.statistics');
    
    Route::get('tickets/my/assigned', [TicketController::class, 'myTickets'])
        ->name('tickets.my-tickets');
    
    // Team assignment endpoints
    Route::post('tickets/{ticket}/team/add', [TicketController::class, 'addTeamMember'])
        ->name('tickets.team.add');
    
    Route::delete('tickets/{ticket}/team/{teamMember}', [TicketController::class, 'removeTeamMember'])
        ->name('tickets.team.remove');

    // Ticket Addon Management routes
    Route::apiResource('ticket-addons', App\Http\Controllers\Api\TicketAddonController::class);
    
    // Addon approval workflow endpoints
    Route::post('ticket-addons/{ticketAddon}/approve', [App\Http\Controllers\Api\TicketAddonController::class, 'approve'])
        ->name('ticket-addons.approve');
    
    Route::post('ticket-addons/{ticketAddon}/reject', [App\Http\Controllers\Api\TicketAddonController::class, 'reject'])
        ->name('ticket-addons.reject');

    // Addon Template Management routes
    Route::apiResource('addon-templates', App\Http\Controllers\Api\AddonTemplateController::class);
    
    // Create addon from template
    Route::post('addon-templates/{addonTemplate}/create-addon', [App\Http\Controllers\Api\AddonTemplateController::class, 'createAddon'])
        ->name('addon-templates.create-addon');

    // Navigation routes (permission-based menu system)
    Route::get('navigation', [App\Http\Controllers\Api\NavigationController::class, 'index'])
        ->name('navigation.index');
    Route::get('navigation/breadcrumbs', [App\Http\Controllers\Api\NavigationController::class, 'breadcrumbs'])
        ->name('navigation.breadcrumbs');
    Route::post('navigation/can-access', [App\Http\Controllers\Api\NavigationController::class, 'canAccess'])
        ->name('navigation.can-access');

    // Ticket Status Management routes
    Route::apiResource('ticket-statuses', App\Http\Controllers\Api\TicketStatusController::class);
    Route::get('ticket-statuses/options', [App\Http\Controllers\Api\TicketStatusController::class, 'options'])
        ->name('ticket-statuses.options');
    Route::get('ticket-statuses/{ticketStatus}/transitions', [App\Http\Controllers\Api\TicketStatusController::class, 'transitions'])
        ->name('ticket-statuses.transitions');

    // Ticket Category Management routes
    Route::apiResource('ticket-categories', App\Http\Controllers\Api\TicketCategoryController::class);
    Route::get('ticket-categories/options', [App\Http\Controllers\Api\TicketCategoryController::class, 'options'])
        ->name('ticket-categories.options');
    Route::get('ticket-categories/statistics', [App\Http\Controllers\Api\TicketCategoryController::class, 'statistics'])
        ->name('ticket-categories.statistics');
    Route::get('ticket-categories/sla-status', [App\Http\Controllers\Api\TicketCategoryController::class, 'slaStatus'])
        ->name('ticket-categories.sla-status');
});

// Admin-only routes
Route::prefix('admin')->middleware(['auth:web,sanctum'])->group(function () {
    // Timer management endpoints
    Route::get('timers/all-active', [TimerController::class, 'allActive'])
        ->middleware('check_permission:admin.read')
        ->name('admin.timers.all-active');
    
    Route::post('timers/{timer}/pause', [TimerController::class, 'adminPauseTimer'])
        ->middleware('check_permission:admin.write')
        ->name('admin.timers.pause');
    
    Route::post('timers/{timer}/resume', [TimerController::class, 'adminResumeTimer'])
        ->middleware('check_permission:admin.write')
        ->name('admin.timers.resume');
    
    Route::post('timers/{timer}/stop', [TimerController::class, 'adminStopTimer'])
        ->middleware('check_permission:admin.write')
        ->name('admin.timers.stop');
});

// Manager-only routes  
Route::prefix('manager')->middleware(['auth:web,sanctum'])->group(function () {
    // Future manager routes
});