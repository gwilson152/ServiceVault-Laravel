<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\DomainMappingController;
use App\Http\Controllers\Api\EmailAdminController;
use App\Http\Controllers\Api\EmailConfigController;
use App\Http\Controllers\Api\EmailIngestionController;
use App\Http\Controllers\Api\EmailSystemController;
use App\Http\Controllers\Api\EmailTemplateController;
use App\Http\Controllers\Api\ImportAnalyticsController;
use App\Http\Controllers\Api\ImportJobController;
use App\Http\Controllers\Api\ImportProfileController;
use App\Http\Controllers\Api\ImportTemplateController;
use App\Http\Controllers\Api\FreescoutImportController;
use App\Http\Controllers\Api\PortalController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TimeEntryController;
use App\Http\Controllers\Api\TimerController;
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

// CSRF token endpoint for frontend token refresh
Route::get('/csrf-token', function (Request $request) {
    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
});

// API routes with authentication (web session + sanctum)
Route::middleware(['auth:sanctum'])->group(function () {

    // Search routes for selectors
    Route::prefix('search')->group(function () {
        Route::get('tickets', [SearchController::class, 'tickets']);
        Route::get('accounts', [SearchController::class, 'accounts']);
        Route::get('users', [SearchController::class, 'users']);
        Route::get('agents', [SearchController::class, 'agents']);
        Route::get('billing-rates', [SearchController::class, 'billingRates']);
        Route::get('role-templates', [SearchController::class, 'roleTemplates']);
    });

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
    Route::get('users/agents', [App\Http\Controllers\Api\UserController::class, 'agents'])
        ->name('users.agents');
    Route::get('users/assignable', [App\Http\Controllers\Api\UserController::class, 'assignableUsers'])
        ->name('users.assignable');
    Route::get('users/billing-agents', [App\Http\Controllers\Api\UserController::class, 'billingAgents'])
        ->name('users.billing-agents');

    // Full user management API
    Route::apiResource('users', App\Http\Controllers\Api\UserController::class);

    // User preferences routes
    Route::apiResource('user-preferences', App\Http\Controllers\Api\UserPreferenceController::class)
        ->parameter('user-preferences', 'key');
    Route::post('user-preferences/bulk', [App\Http\Controllers\Api\UserPreferenceController::class, 'bulkUpdate'])
        ->name('user-preferences.bulk-update');

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

    Route::get('timers/active-with-controls', [TimerController::class, 'activeWithControls'])
        ->name('timers.active-with-controls');

    Route::post('timers/{timer}/stop', [TimerController::class, 'stop'])
        ->name('timers.stop');

    Route::post('timers/{timer}/pause', [TimerController::class, 'pause'])
        ->name('timers.pause');

    Route::post('timers/{timer}/resume', [TimerController::class, 'resume'])
        ->name('timers.resume');

    Route::post('timers/{timer}/commit', [TimerController::class, 'commit'])
        ->name('timers.commit');

    Route::post('timers/{timer}/mark-committed', [TimerController::class, 'markCommitted'])
        ->name('timers.mark-committed');

    Route::post('timers/{timer}/cancel', [TimerController::class, 'cancel'])
        ->name('timers.cancel');

    Route::patch('timers/{timer}/duration', [TimerController::class, 'adjustDuration'])
        ->name('timers.adjust-duration');

    Route::post('timers/sync', [TimerController::class, 'sync'])
        ->name('timers.sync');

    Route::get('timers/user/statistics', [TimerController::class, 'statistics'])
        ->name('timers.statistics');

    Route::post('timers/bulk', [TimerController::class, 'bulk'])
        ->name('timers.bulk');

    Route::post('timers/bulk-active-for-tickets', [TimerController::class, 'bulkActiveForTickets'])
        ->name('timers.bulk-active-for-tickets');

    // Ticket timer endpoints
    Route::get('tickets/{ticketId}/timers', [TimerController::class, 'forTicket'])
        ->name('tickets.timers');

    Route::post('tickets/{ticketId}/timers/start', [TimerController::class, 'startForTicket'])
        ->name('tickets.timers.start');

    Route::get('tickets/{ticketId}/timers/active', [TimerController::class, 'activeForTicket'])
        ->name('tickets.timers.active');

    // Time Entry routes with approval workflow
    Route::apiResource('time-entries', TimeEntryController::class)->names([
        'index' => 'time-entries.api.index',
        'store' => 'time-entries.api.store',
        'show' => 'time-entries.api.show',
        'update' => 'time-entries.api.update',
        'destroy' => 'time-entries.api.destroy',
    ]);

    // Time entry approval workflow endpoints
    Route::post('time-entries/{timeEntry}/approve', [TimeEntryController::class, 'approve'])
        ->name('time-entries.approve');

    Route::post('time-entries/{timeEntry}/reject', [TimeEntryController::class, 'reject'])
        ->name('time-entries.reject');

    Route::post('time-entries/{timeEntry}/unapprove', [TimeEntryController::class, 'unapprove'])
        ->name('time-entries.unapprove');

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

    Route::get('accounts/selector', [AccountController::class, 'selector'])
        ->name('accounts.selector');

    // Account users endpoint
    Route::get('accounts/{account}/users', [AccountController::class, 'users'])
        ->name('accounts.users');

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
    Route::apiResource('tickets', TicketController::class)->names([
        'index' => 'tickets.api.index',
        'store' => 'tickets.api.store',
        'show' => 'tickets.api.show',
        'update' => 'tickets.api.update',
        'destroy' => 'tickets.api.destroy',
    ]);

    // Ticket workflow and assignment endpoints
    Route::post('tickets/{ticket}/transition', [TicketController::class, 'transitionStatus'])
        ->name('tickets.transition');

    Route::post('tickets/{ticket}/priority', [TicketController::class, 'updatePriority'])
        ->name('tickets.priority');

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
    Route::get('tickets/{ticket}/time-summary', [TicketController::class, 'timeSummary'])
        ->name('tickets.time-summary');

    // Ticket add-on endpoints
    Route::get('tickets/{ticket}/addons', [App\Http\Controllers\Api\TicketAddonController::class, 'forTicket'])
        ->name('tickets.addons');
    Route::post('ticket-addons/{ticketAddon}/complete', [App\Http\Controllers\Api\TicketAddonController::class, 'complete'])
        ->name('ticket-addons.complete');

    // Related tickets endpoint
    Route::get('tickets/{ticket}/related', [TicketController::class, 'relatedTickets'])
        ->name('tickets.related');

    // Ticket activity and audit trail
    Route::get('tickets/{ticket}/activity', [TicketController::class, 'activity'])
        ->name('tickets.activity');
    Route::get('tickets/{ticket}/activity-stats', [TicketController::class, 'activityStats'])
        ->name('tickets.activity-stats');

    // Ticket billing endpoints
    Route::get('tickets/{ticket}/billing-summary', [TicketController::class, 'billingSummary'])
        ->name('tickets.billing-summary');
    Route::get('tickets/{ticket}/billing-rate', [TicketController::class, 'getBillingRate'])
        ->name('tickets.billing-rate.get');
    Route::post('tickets/{ticket}/billing-rate', [TicketController::class, 'setBillingRate'])
        ->name('tickets.billing-rate.set');
    Route::get('tickets/{ticket}/invoices', [TicketController::class, 'getInvoices'])
        ->name('tickets.invoices');
    Route::get('tickets/{ticket}/billing-report', [TicketController::class, 'billingReport'])
        ->name('tickets.billing-report');

    // Ticket status and assignment endpoints
    Route::put('tickets/{ticket}/status', [TicketController::class, 'updateStatus'])
        ->name('tickets.status.update');
    Route::put('tickets/{ticket}/assignment', [TicketController::class, 'updateAssignment'])
        ->name('tickets.assignment.update');

    // Ticket statuses and management
    Route::get('ticket-statuses', [TicketController::class, 'getStatuses'])
        ->name('ticket-statuses.list');

    // Ticket Addon Management routes
    Route::apiResource('ticket-addons', App\Http\Controllers\Api\TicketAddonController::class);

    // Addon approval workflow endpoints
    Route::post('ticket-addons/{ticketAddon}/approve', [App\Http\Controllers\Api\TicketAddonController::class, 'approve'])
        ->name('ticket-addons.approve');

    Route::post('ticket-addons/{ticketAddon}/reject', [App\Http\Controllers\Api\TicketAddonController::class, 'reject'])
        ->name('ticket-addons.reject');

    Route::post('ticket-addons/{ticketAddon}/unapprove', [App\Http\Controllers\Api\TicketAddonController::class, 'unapprove'])
        ->name('ticket-addons.unapprove');

    // Bulk addon approval
    Route::post('ticket-addons/bulk/approve', [App\Http\Controllers\Api\TicketAddonController::class, 'bulkApprove'])
        ->name('ticket-addons.bulk-approve');

    Route::post('ticket-addons/bulk/reject', [App\Http\Controllers\Api\TicketAddonController::class, 'bulkReject'])
        ->name('ticket-addons.bulk-reject');

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
    Route::get('ticket-statuses/options', [App\Http\Controllers\Api\TicketStatusController::class, 'options'])
        ->name('ticket-statuses.options');
    Route::post('ticket-statuses/reorder', [App\Http\Controllers\Api\TicketStatusController::class, 'reorder'])
        ->name('ticket-statuses.reorder');
    Route::get('ticket-statuses/{ticketStatus}/transitions', [App\Http\Controllers\Api\TicketStatusController::class, 'transitions'])
        ->name('ticket-statuses.transitions');

    Route::apiResource('ticket-statuses', App\Http\Controllers\Api\TicketStatusController::class);

    // Ticket Category Management routes
    // Specific routes must be defined BEFORE apiResource to avoid route conflicts
    Route::get('ticket-categories/options', [App\Http\Controllers\Api\TicketCategoryController::class, 'options'])
        ->name('ticket-categories.options');
    Route::get('ticket-categories/statistics', [App\Http\Controllers\Api\TicketCategoryController::class, 'statistics'])
        ->name('ticket-categories.statistics');
    Route::get('ticket-categories/sla-status', [App\Http\Controllers\Api\TicketCategoryController::class, 'sla-status'])
        ->name('ticket-categories.sla-status');
    Route::post('ticket-categories/reorder', [App\Http\Controllers\Api\TicketCategoryController::class, 'reorder'])
        ->name('ticket-categories.reorder');

    Route::apiResource('ticket-categories', App\Http\Controllers\Api\TicketCategoryController::class);

    // Ticket Priority Management routes
    Route::get('ticket-priorities/options', [App\Http\Controllers\Api\TicketPriorityController::class, 'options'])
        ->name('ticket-priorities.options');
    Route::post('ticket-priorities/reorder', [App\Http\Controllers\Api\TicketPriorityController::class, 'reorder'])
        ->name('ticket-priorities.reorder');

    Route::apiResource('ticket-priorities', App\Http\Controllers\Api\TicketPriorityController::class);

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

    // Settings Management routes (Admin access required)
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingController::class, 'index'])
            ->name('settings.index');

        // System Settings
        Route::put('system', [SettingController::class, 'updateSystemSettings'])
            ->name('settings.system.update');

        // Email Settings
        Route::get('email', [SettingController::class, 'getEmailSettings'])
            ->name('settings.email.get');
        Route::put('email', [SettingController::class, 'updateEmailSettings'])
            ->name('settings.email.update');
        Route::post('email/test-smtp', [SettingController::class, 'testSmtp'])
            ->name('settings.email.test-smtp');
        Route::post('email/test-imap', [SettingController::class, 'testImap'])
            ->name('settings.email.test-imap');
        Route::post('email/test-m365', [SettingController::class, 'testM365'])
            ->name('settings.email.test-m365');
        Route::post('email/m365-folders', [SettingController::class, 'getM365Folders'])
            ->name('settings.email.m365-folders');
        Route::get('email/test-folder-hierarchy', [SettingController::class, 'testFolderHierarchy'])
            ->name('settings.email.test-folder-hierarchy');

        // Ticket Configuration
        Route::get('ticket-config', [SettingController::class, 'getTicketConfig'])
            ->name('settings.ticket-config');
        Route::put('workflow-transitions', [SettingController::class, 'updateWorkflowTransitions'])
            ->name('settings.workflow-transitions.update');

        // Billing Configuration
        Route::get('billing-config', [SettingController::class, 'getBillingConfig'])
            ->name('settings.billing-config');

        // Timer Settings
        Route::get('timer', [SettingController::class, 'getTimerSettings'])
            ->name('settings.timer');
        Route::put('timer', [SettingController::class, 'updateTimerSettings'])
            ->name('settings.timer.update');

        // User Management Settings
        Route::get('user-management', [SettingController::class, 'getUserManagementSettings'])
            ->name('settings.user-management');
        Route::put('user-management', [SettingController::class, 'updateUserManagementSettings'])
            ->name('settings.user-management.update');

        // Tax Settings
        Route::get('tax', [SettingController::class, 'getTaxSettings'])
            ->name('settings.tax');
        Route::put('tax', [SettingController::class, 'updateTaxSettings'])
            ->name('settings.tax.update');

        // Advanced Settings
        Route::get('advanced', [SettingController::class, 'getAdvancedSettings'])
            ->name('settings.advanced');
        Route::put('advanced', [SettingController::class, 'updateAdvancedSettings'])
            ->name('settings.advanced.update');

        // Nuclear Reset (Super Admin only)
        Route::post('nuclear-reset', [SettingController::class, 'nuclearReset'])
            ->name('settings.nuclear-reset');
    });

    // Billing Rate Management routes
    Route::apiResource('billing-rates', App\Http\Controllers\Api\BillingRateController::class);

    // Billing Management routes
    Route::prefix('billing')->group(function () {
        // Invoice Management
        Route::apiResource('invoices', App\Http\Controllers\Api\InvoiceController::class);

        // Additional invoice endpoints
        Route::get('unbilled-items', [App\Http\Controllers\Api\InvoiceController::class, 'unbilledItems'])
            ->name('billing.unbilled-items');
        Route::post('invoices/{invoice}/send', [App\Http\Controllers\Api\InvoiceController::class, 'send'])
            ->name('invoices.send');
        Route::post('invoices/{invoice}/mark-paid', [App\Http\Controllers\Api\InvoiceController::class, 'markPaid'])
            ->name('invoices.mark-paid');
        Route::get('invoices/{invoice}/pdf', [App\Http\Controllers\Api\InvoiceController::class, 'pdf'])
            ->name('invoices.pdf');

        // Line item management for draft invoices
        Route::get('invoices/{invoice}/available-items', [App\Http\Controllers\Api\InvoiceController::class, 'getAvailableItems'])
            ->name('invoices.available-items');
        Route::post('invoices/{invoice}/line-items', [App\Http\Controllers\Api\InvoiceController::class, 'addLineItem'])
            ->name('invoices.line-items.add');
        Route::put('invoices/{invoice}/line-items/{lineItem}', [App\Http\Controllers\Api\InvoiceController::class, 'updateLineItem'])
            ->name('invoices.line-items.update');
        Route::delete('invoices/{invoice}/line-items/{lineItem}', [App\Http\Controllers\Api\InvoiceController::class, 'removeLineItem'])
            ->name('invoices.line-items.remove');
        Route::post('invoices/{invoice}/line-items/reorder', [App\Http\Controllers\Api\InvoiceController::class, 'reorderLineItems'])
            ->name('invoices.line-items.reorder');
        Route::post('invoices/{invoice}/separators', [App\Http\Controllers\Api\InvoiceController::class, 'addSeparator'])
            ->name('invoices.separators.add');

        // Payment Management
        Route::apiResource('payments', App\Http\Controllers\Api\PaymentController::class);

        // Billing Settings
        Route::apiResource('billing-settings', App\Http\Controllers\Api\BillingSettingController::class)
            ->names([
                'index' => 'billing.settings.index',
                'store' => 'billing.settings.store',
                'show' => 'billing.settings.show',
                'update' => 'billing.settings.update',
                'destroy' => 'billing.settings.destroy',
            ]);

        // Billing Reports
        Route::get('reports/dashboard', [App\Http\Controllers\Api\BillingReportController::class, 'dashboard'])
            ->name('billing.reports.dashboard');
        Route::get('reports/revenue', [App\Http\Controllers\Api\BillingReportController::class, 'revenue'])
            ->name('billing.reports.revenue');
        Route::get('reports/outstanding', [App\Http\Controllers\Api\BillingReportController::class, 'outstanding'])
            ->name('billing.reports.outstanding');
        Route::get('reports/payments', [App\Http\Controllers\Api\BillingReportController::class, 'payments'])
            ->name('billing.reports.payments');
    });

    // Portal API routes (customer portal dashboard data)
    Route::prefix('portal')->group(function () {
        Route::get('stats', [PortalController::class, 'stats'])
            ->name('portal.stats');
        Route::get('recent-tickets', [PortalController::class, 'recentTickets'])
            ->name('portal.recent-tickets');
        Route::get('recent-activity', [PortalController::class, 'recentActivity'])
            ->name('portal.recent-activity');
    });
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

    Route::post('timers/{timer}/cancel', [TimerController::class, 'adminCancelTimer'])
        ->middleware('check_permission:admin.write')
        ->name('admin.timers.cancel');
});

// Import System routes
Route::prefix('import')->middleware('check_permission:system.import')->group(function () {
    // Import Profile Management
    Route::apiResource('profiles', ImportProfileController::class);
    Route::post('profiles/test-connection', [ImportProfileController::class, 'testConnection'])
        ->name('import.profiles.test-connection');
    Route::post('profiles/{profile}/test-connection', [ImportProfileController::class, 'testConnection'])
        ->name('import.profiles.test-existing');
    Route::get('profiles/{profile}/schema', [ImportProfileController::class, 'getSchema'])
        ->name('import.profiles.schema');
    Route::get('profiles/{profile}/introspect', [ImportProfileController::class, 'introspectSchema'])
        ->name('import.profiles.introspect');
    Route::get('profiles/{profile}/introspect-emails', [ImportProfileController::class, 'introspectEmails'])
        ->name('import.profiles.introspect-emails');
    Route::get('profiles/{profile}/introspect-time-tracking', [ImportProfileController::class, 'introspectTimeTracking'])
        ->name('import.profiles.introspect-time-tracking');
    Route::get('profiles/{profile}/preview', [ImportProfileController::class, 'preview'])
        ->name('import.profiles.preview');
    Route::post('profiles/{profile}/preview-table', [ImportProfileController::class, 'previewTable'])
        ->name('import.profiles.preview-table');
    Route::post('profiles/{profile}/preview-query', [ImportProfileController::class, 'previewQuery'])
        ->name('import.profiles.preview-query');

    // Profile Duplication
    Route::post('profiles/{profile}/duplicate', [ImportProfileController::class, 'duplicate'])
        ->name('import.profiles.duplicate');

    // Field Mapping Management
    Route::get('profiles/{profile}/mappings', [ImportProfileController::class, 'getMappings'])
        ->name('import.profiles.mappings.get');
    Route::post('profiles/{profile}/mappings', [ImportProfileController::class, 'saveMappings'])
        ->name('import.profiles.mappings.save');

    // Database Introspection
    // (FreeScout-specific routes removed)

    // Import Job Management
    Route::apiResource('jobs', ImportJobController::class)->except(['update']);
    Route::post('profiles/{profile}/import', [ImportJobController::class, 'executeImport'])
        ->name('import.jobs.execute');
    Route::get('jobs/{importJob}/status', [ImportJobController::class, 'status'])
        ->name('import.jobs.status');
    Route::post('jobs/{importJob}/cancel', [ImportJobController::class, 'cancel'])
        ->name('import.jobs.cancel');
    Route::post('jobs/{importJob}/retry', [ImportJobController::class, 'retry'])
        ->name('import.jobs.retry');
    Route::get('jobs/{importJob}/errors', [ImportJobController::class, 'errors'])
        ->name('import.jobs.errors');
    Route::get('jobs/stats', [ImportJobController::class, 'stats'])
        ->name('import.jobs.stats');

    // Template Application
    Route::put('profiles/{profile}/template', [ImportProfileController::class, 'applyTemplate'])
        ->name('import.profiles.apply-template');

    // Query Builder API
    Route::post('profiles/{profile}/builder/validate', [ImportProfileController::class, 'validateQuery'])
        ->name('import.profiles.validate-query');
    Route::post('profiles/{profile}/queries', [ImportProfileController::class, 'saveQuery'])
        ->name('import.profiles.save-query');
    Route::post('profiles/{profile}/builder/analyze-joins', [ImportProfileController::class, 'analyzeJoins'])
        ->name('import.profiles.analyze-joins');
    Route::post('profiles/{profile}/simulate', [ImportProfileController::class, 'simulate'])
        ->name('import.profiles.simulate');
    Route::post('profiles/{profile}/execute-query', [ImportProfileController::class, 'executeQuery'])
        ->name('import.profiles.execute-query');

    // Import Template Management
    Route::get('templates', [ImportTemplateController::class, 'index'])
        ->name('import.templates.index');
    Route::get('templates/{template}', [ImportTemplateController::class, 'show'])
        ->name('import.templates.show');

    // Import Analytics and Reporting
    Route::prefix('analytics')->group(function () {
        Route::get('dashboard', [ImportAnalyticsController::class, 'dashboard'])
            ->name('import.analytics.dashboard');
        Route::get('profiles/{profile}/stats', [ImportAnalyticsController::class, 'profileStats'])
            ->name('import.analytics.profile-stats');
        Route::get('jobs/{job}/details', [ImportAnalyticsController::class, 'jobDetails'])
            ->name('import.analytics.job-details');
        Route::get('records', [ImportAnalyticsController::class, 'records'])
            ->name('import.analytics.records');
        Route::get('duplicate-analysis', [ImportAnalyticsController::class, 'duplicateAnalysis'])
            ->name('import.analytics.duplicate-analysis');
        Route::get('performance-metrics', [ImportAnalyticsController::class, 'performanceMetrics'])
            ->name('import.analytics.performance-metrics');
        Route::get('trends', [ImportAnalyticsController::class, 'trends'])
            ->name('import.analytics.trends');
    });

    // FreeScout Import Routes
    Route::prefix('freescout')->group(function () {
        Route::post('validate-config', [FreescoutImportController::class, 'validateConfig'])
            ->name('import.freescout.validate-config');
        
        Route::post('preview', [FreescoutImportController::class, 'previewImport'])
            ->name('import.freescout.preview');
        
        Route::post('execute', [FreescoutImportController::class, 'executeImport'])
            ->name('import.freescout.execute');
        
        Route::get('job/{jobId}/status', [FreescoutImportController::class, 'getImportStatus'])
            ->name('import.freescout.job-status');
        
        Route::post('analyze-relationships', [FreescoutImportController::class, 'analyzeRelationships'])
            ->name('import.freescout.analyze-relationships');
    });
});

// Email Configuration Management Routes (Admin/Manager)
Route::middleware(['auth:sanctum'])->prefix('email-configs')->group(function () {
    // List email configurations with filtering
    Route::get('/', [EmailConfigController::class, 'index'])
        ->name('email-configs.index');
    
    // Create new email configuration
    Route::post('/', [EmailConfigController::class, 'store'])
        ->name('email-configs.store');
    
    // Get driver information and requirements
    Route::get('drivers', [EmailConfigController::class, 'getDriverInfo'])
        ->name('email-configs.drivers');
    
    // Show specific email configuration
    Route::get('{emailConfig}', [EmailConfigController::class, 'show'])
        ->name('email-configs.show');
    
    // Update email configuration
    Route::put('{emailConfig}', [EmailConfigController::class, 'update'])
        ->name('email-configs.update');
    
    // Delete email configuration
    Route::delete('{emailConfig}', [EmailConfigController::class, 'destroy'])
        ->name('email-configs.destroy');
    
    // Test email configuration connection
    Route::post('{emailConfig}/test-connection', [EmailConfigController::class, 'testConnection'])
        ->name('email-configs.test-connection');
    
    // Send test email using configuration
    Route::post('{emailConfig}/send-test', [EmailConfigController::class, 'sendTestEmail'])
        ->name('email-configs.send-test');
    
    // Set configuration as default
    Route::post('{emailConfig}/set-default', [EmailConfigController::class, 'setDefault'])
        ->name('email-configs.set-default');
});

// Email Template Management Routes (Admin/Manager)
Route::middleware(['auth:sanctum'])->prefix('email-templates')->group(function () {
    // List email templates with filtering
    Route::get('/', [EmailTemplateController::class, 'index'])
        ->name('email-templates.index');
    
    // Create new email template
    Route::post('/', [EmailTemplateController::class, 'store'])
        ->name('email-templates.store');
    
    // Get template types and available variables
    Route::get('types', [EmailTemplateController::class, 'getTemplateTypes'])
        ->name('email-templates.types');
    
    // Show specific email template
    Route::get('{emailTemplate}', [EmailTemplateController::class, 'show'])
        ->name('email-templates.show');
    
    // Update email template
    Route::put('{emailTemplate}', [EmailTemplateController::class, 'update'])
        ->name('email-templates.update');
    
    // Delete email template
    Route::delete('{emailTemplate}', [EmailTemplateController::class, 'destroy'])
        ->name('email-templates.destroy');
    
    // Preview email template with sample data
    Route::post('{emailTemplate}/preview', [EmailTemplateController::class, 'preview'])
        ->name('email-templates.preview');
    
    // Duplicate email template
    Route::post('{emailTemplate}/duplicate', [EmailTemplateController::class, 'duplicate'])
        ->name('email-templates.duplicate');
});

// Email Administration Routes (Super Admin only)
Route::middleware(['auth:sanctum,web'])->prefix('email-admin')->group(function () {
    // Dashboard and overview
    Route::get('dashboard', [EmailAdminController::class, 'dashboard'])
        ->name('email-admin.dashboard');
    
    // Processing logs management
    Route::get('processing-logs', [EmailAdminController::class, 'getProcessingLogs'])
        ->name('email-admin.processing-logs');
    Route::get('processing-logs/{emailId}', [EmailAdminController::class, 'getProcessingLogDetail'])
        ->name('email-admin.processing-log-detail');
    
    // Retry and management operations
    Route::post('retry-processing', [EmailAdminController::class, 'retryProcessing'])
        ->name('email-admin.retry-processing');
    
    // Queue monitoring and management
    Route::get('queue-status', [EmailAdminController::class, 'getQueueStatus'])
        ->name('email-admin.queue-status');
    Route::delete('failed-jobs', [EmailAdminController::class, 'clearFailedJobs'])
        ->name('email-admin.clear-failed-jobs');
    
    // System health monitoring
    Route::get('system-health', [EmailAdminController::class, 'getSystemHealth'])
        ->name('email-admin.system-health');
});

// Manager-only routes
Route::prefix('manager')->middleware(['auth:web,sanctum'])->group(function () {
    // Future manager routes
});

// Email Ingestion Routes (Public endpoints for webhooks)
Route::prefix('email')->group(function () {
    // Health check for email service
    Route::get('health', [EmailIngestionController::class, 'health'])
        ->name('email.health');
    
    // Generic email ingestion endpoint
    Route::post('ingest', [EmailIngestionController::class, 'receiveEmail'])
        ->name('email.ingest');
    
    // Provider-specific endpoints
    Route::post('ingest/sendgrid', [EmailIngestionController::class, 'receiveSendGrid'])
        ->name('email.ingest.sendgrid');
    Route::post('ingest/mailgun', [EmailIngestionController::class, 'receiveMailgun'])
        ->name('email.ingest.mailgun');
    Route::post('ingest/postmark', [EmailIngestionController::class, 'receivePostmark'])
        ->name('email.ingest.postmark');
    Route::post('ingest/aws-ses', [EmailIngestionController::class, 'receiveAwsSes'])
        ->name('email.ingest.aws-ses');
    
    // Immediate processing (for testing)
    Route::post('process-immediate', [EmailIngestionController::class, 'processImmediate'])
        ->name('email.process-immediate');
    
    // Status checking
    Route::get('status/{emailId}', [EmailIngestionController::class, 'getStatus'])
        ->name('email.status');
});

// Email System Configuration (Application-wide)
Route::prefix('email-system')->middleware(['auth'])->group(function () {
    // System Configuration
    Route::get('config', [EmailSystemController::class, 'getConfig'])
        ->name('email-system.config.get');
    
    Route::put('config', [EmailSystemController::class, 'updateConfig'])
        ->name('email-system.config.update');
    
    Route::post('test', [EmailSystemController::class, 'testConfig'])
        ->name('email-system.config.test');
    
    Route::get('status', [EmailSystemController::class, 'getSystemStatus'])
        ->name('email-system.status');
    
    // Domain Mappings (Business Account Routing)
    Route::get('domain-mappings', [EmailSystemController::class, 'getDomainMappings'])
        ->name('email-system.domain-mappings.index');
    
    Route::post('domain-mappings', [EmailSystemController::class, 'createDomainMapping'])
        ->name('email-system.domain-mappings.store');
    
    Route::put('domain-mappings/{mapping}', [EmailSystemController::class, 'updateDomainMapping'])
        ->name('email-system.domain-mappings.update');
    
    Route::delete('domain-mappings/{mapping}', [EmailSystemController::class, 'deleteDomainMapping'])
        ->name('email-system.domain-mappings.delete');
    
    Route::post('domain-mappings/test', [EmailSystemController::class, 'testDomainMapping'])
        ->name('email-system.domain-mappings.test');
    
    // Helper endpoints
    Route::get('pattern-examples', [EmailSystemController::class, 'getPatternExamples'])
        ->name('email-system.pattern-examples');
    
    Route::get('provider-defaults', [EmailSystemController::class, 'getProviderDefaults'])
        ->name('email-system.provider-defaults');
});
