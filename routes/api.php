<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\DomainMappingController;
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
    
    // Service ticket timer endpoints
    Route::get('service-tickets/{serviceTicketId}/timers', [TimerController::class, 'forServiceTicket'])
        ->name('service-tickets.timers');
    
    Route::get('service-tickets/{serviceTicketId}/timers/active', [TimerController::class, 'activeForServiceTicket'])
        ->name('service-tickets.timers.active');

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

    // Service Ticket routes (comprehensive ticket management system)
    Route::apiResource('service-tickets', App\Http\Controllers\Api\ServiceTicketController::class);
    
    // Service ticket workflow and assignment endpoints
    Route::post('service-tickets/{serviceTicket}/transition', [App\Http\Controllers\Api\ServiceTicketController::class, 'transitionStatus'])
        ->name('service-tickets.transition');
    
    Route::post('service-tickets/{serviceTicket}/assign', [App\Http\Controllers\Api\ServiceTicketController::class, 'assign'])
        ->name('service-tickets.assign');
    
    Route::get('service-tickets/stats/dashboard', [App\Http\Controllers\Api\ServiceTicketController::class, 'statistics'])
        ->name('service-tickets.statistics');
    
    Route::get('service-tickets/my/assigned', [App\Http\Controllers\Api\ServiceTicketController::class, 'myTickets'])
        ->name('service-tickets.my-tickets');
    
    // Team assignment endpoints
    Route::post('service-tickets/{serviceTicket}/team/add', [App\Http\Controllers\Api\ServiceTicketController::class, 'addTeamMember'])
        ->name('service-tickets.team.add');
    
    Route::delete('service-tickets/{serviceTicket}/team/{teamMember}', [App\Http\Controllers\Api\ServiceTicketController::class, 'removeTeamMember'])
        ->name('service-tickets.team.remove');
    
    Route::get('service-tickets/{serviceTicket}/team', [App\Http\Controllers\Api\ServiceTicketController::class, 'getTeamMembers'])
        ->name('service-tickets.team.list');
    
    Route::post('service-tickets/{serviceTicket}/team/bulk', [App\Http\Controllers\Api\ServiceTicketController::class, 'bulkAssignTeam'])
        ->name('service-tickets.team.bulk-assign');
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