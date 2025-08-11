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
    
    // Full user management API
    Route::apiResource('users', App\Http\Controllers\Api\UserController::class);
    
    // Additional user detail endpoints
    Route::get('users/{user}/tickets', [App\Http\Controllers\Api\UserController::class, 'tickets'])
        ->name('users.tickets');
    Route::get('users/{user}/time-entries', [App\Http\Controllers\Api\UserController::class, 'timeEntries'])
        ->name('users.time-entries');
    Route::get('users/{user}/activity', [App\Http\Controllers\Api\UserController::class, 'activity'])
        ->name('users.activity');
    Route::get('users/{user}/accounts', [App\Http\Controllers\Api\UserController::class, 'accounts'])
        ->name('users.accounts');
    
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
    
    Route::post('tickets/{ticketId}/timers/start', [TimerController::class, 'startForTicket'])
        ->name('tickets.timers.start');
    
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
    
    Route::get('time-entries/stats/recent', [TimeEntryController::class, 'recentStats'])
        ->name('time-entries.recent-stats');

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
    // Note: Specific routes MUST come before the resource route to avoid parameter binding conflicts
    
    Route::get('tickets/stats/dashboard', [TicketController::class, 'statistics'])
        ->name('tickets.statistics');
    
    Route::get('tickets/my/assigned', [TicketController::class, 'myTickets'])
        ->name('tickets.my-tickets');
    
    Route::get('tickets/filter-counts', [TicketController::class, 'filterCounts'])
        ->name('tickets.filter-counts');
    
    // Resource route (creates routes with {ticket} parameter binding)
    Route::apiResource('tickets', TicketController::class);
    
    // Ticket workflow and assignment endpoints
    Route::post('tickets/{ticket}/transition', [TicketController::class, 'transitionStatus'])
        ->name('tickets.transition');
    
    Route::post('tickets/{ticket}/assign', [TicketController::class, 'assign'])
        ->name('tickets.assign');
    
    // Team assignment endpoints
    Route::post('tickets/{ticket}/team/add', [TicketController::class, 'addTeamMember'])
        ->name('tickets.team.add');
    
    Route::delete('tickets/{ticket}/team/{teamMember}', [TicketController::class, 'removeTeamMember'])
        ->name('tickets.team.remove');
        
    // Ticket comment endpoints
    Route::get('tickets/{ticket}/comments', [App\Http\Controllers\Api\TicketCommentController::class, 'index'])
        ->name('tickets.comments.index');
    Route::post('tickets/{ticket}/comments', [App\Http\Controllers\Api\TicketCommentController::class, 'store'])
        ->name('tickets.comments.store');
    Route::put('tickets/{ticket}/comments/{comment}', [App\Http\Controllers\Api\TicketCommentController::class, 'update'])
        ->name('tickets.comments.update');
    Route::delete('tickets/{ticket}/comments/{comment}', [App\Http\Controllers\Api\TicketCommentController::class, 'destroy'])
        ->name('tickets.comments.destroy');
        
    // Ticket time tracking endpoints  
    Route::get('tickets/{ticket}/time-entries', [TimeEntryController::class, 'forTicket'])
        ->name('tickets.time-entries');

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
    // Specific routes must be defined BEFORE apiResource to avoid route conflicts
    Route::get('ticket-categories/options', [App\Http\Controllers\Api\TicketCategoryController::class, 'options'])
        ->name('ticket-categories.options');
    Route::get('ticket-categories/statistics', [App\Http\Controllers\Api\TicketCategoryController::class, 'statistics'])
        ->name('ticket-categories.statistics');
    Route::get('ticket-categories/sla-status', [App\Http\Controllers\Api\TicketCategoryController::class, 'sla-status'])
        ->name('ticket-categories.sla-status');
    
    Route::apiResource('ticket-categories', App\Http\Controllers\Api\TicketCategoryController::class);

    // Role Template Management routes (Admin access required)
    // Note: Specific routes MUST come before the resource route to avoid parameter binding conflicts
    
    Route::get('role-templates/create', [App\Http\Controllers\Api\RoleTemplateController::class, 'create'])
        ->name('role-templates.create');
    
    Route::get('role-templates/create/preview/widgets', [App\Http\Controllers\Api\RoleTemplateController::class, 'previewWidgetsForCreate'])
        ->name('role-templates.create.preview-widgets');
    
    Route::get('role-templates/permissions/available', [App\Http\Controllers\Api\RoleTemplateController::class, 'permissions'])
        ->name('role-templates.permissions');
    
    // Resource route (creates routes with {roleTemplate} parameter binding)
    Route::apiResource('role-templates', App\Http\Controllers\Api\RoleTemplateController::class);
    
    Route::get('role-templates/{roleTemplate}/preview/widgets', [App\Http\Controllers\Api\RoleTemplateController::class, 'previewWidgets'])
        ->name('role-templates.preview-widgets');
    Route::post('role-templates/{roleTemplate}/clone', [App\Http\Controllers\Api\RoleTemplateController::class, 'clone'])
        ->name('role-templates.clone');

    // Widget Assignment Management routes
    Route::get('role-templates/{roleTemplate}/widgets', [App\Http\Controllers\Api\RoleTemplateController::class, 'getWidgets'])
        ->name('role-templates.get-widgets');
    Route::put('role-templates/{roleTemplate}/widgets', [App\Http\Controllers\Api\RoleTemplateController::class, 'updateWidgets'])
        ->name('role-templates.update-widgets');

    // Dashboard Preview routes (Admin access required)
    Route::get('role-templates/{roleTemplate}/preview/dashboard', [App\Http\Controllers\Api\DashboardPreviewController::class, 'previewDashboard'])
        ->name('role-templates.preview-dashboard');
    Route::get('role-templates/{roleTemplate}/preview/widgets', [App\Http\Controllers\Api\DashboardPreviewController::class, 'previewWidgets'])
        ->name('role-templates.preview-widgets-detailed');
    Route::get('role-templates/{roleTemplate}/preview/navigation', [App\Http\Controllers\Api\DashboardPreviewController::class, 'previewNavigation'])
        ->name('role-templates.preview-navigation');
    Route::get('role-templates/{roleTemplate}/preview/layout', [App\Http\Controllers\Api\DashboardPreviewController::class, 'previewLayout'])
        ->name('role-templates.preview-layout');

    // Widget Permission Management routes (Admin access required)
    Route::apiResource('widget-permissions', App\Http\Controllers\Api\WidgetPermissionController::class);
    Route::post('widget-permissions/sync', [App\Http\Controllers\Api\WidgetPermissionController::class, 'sync'])
        ->name('widget-permissions.sync');
    Route::get('widget-permissions/categories/list', [App\Http\Controllers\Api\WidgetPermissionController::class, 'categories'])
        ->name('widget-permissions.categories');
    Route::post('widget-permissions/{widgetPermission}/assign-to-role', [App\Http\Controllers\Api\WidgetPermissionController::class, 'assignToRole'])
        ->name('widget-permissions.assign-to-role');
    Route::delete('widget-permissions/{widgetPermission}/remove-from-role', [App\Http\Controllers\Api\WidgetPermissionController::class, 'removeFromRole'])
        ->name('widget-permissions.remove-from-role');
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