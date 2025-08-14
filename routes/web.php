<?php

use App\Http\Controllers\DynamicDashboardController;
use App\Http\Controllers\Dashboard\AdminDashboardController;
use App\Http\Controllers\Dashboard\EmployeeDashboardController;
use App\Http\Controllers\Dashboard\ManagerDashboardController;
use App\Http\Controllers\Portal\CustomerPortalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SetupController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Setup routes (no middleware applied)
Route::prefix('setup')->group(function () {
    Route::get('/', [SetupController::class, 'index'])->name('setup.index');
    Route::post('/', [SetupController::class, 'store'])->name('setup.store');
});

// Public invitation routes (no authentication required)
Route::prefix('invitations')->name('invitations.')->group(function () {
    Route::get('accept/{token}', function ($token) {
        return Inertia::render('Invitations/Accept', ['token' => $token]);
    })->name('accept.form');
    
    Route::get('api/{token}', [App\Http\Controllers\UserInvitationController::class, 'showByToken'])
        ->name('api.show');
    
    Route::post('api/{token}/accept', [App\Http\Controllers\UserInvitationController::class, 'accept'])
        ->name('api.accept');
});

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Dynamic dashboard route - serves widgets based on user permissions
Route::get('/dashboard', [DynamicDashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// Admin Dashboard Routes
Route::middleware(['auth', 'verified', 'dashboard.role:admin'])->prefix('dashboard/admin')->name('dashboard.admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('index');
    Route::get('/users', [AdminDashboardController::class, 'users'])->name('users');
    Route::get('/accounts', [AdminDashboardController::class, 'accounts'])->name('accounts');
    Route::get('/role-templates', [AdminDashboardController::class, 'roleTemplates'])->name('role-templates');
    Route::get('/domain-mappings', [AdminDashboardController::class, 'domainMappings'])->name('domain-mappings');
    Route::get('/settings', [AdminDashboardController::class, 'settings'])->name('settings');
});

// Manager Dashboard Routes  
Route::middleware(['auth', 'verified', 'dashboard.role:manager'])->prefix('dashboard/manager')->name('dashboard.manager.')->group(function () {
    Route::get('/', [ManagerDashboardController::class, 'index'])->name('index');
    Route::get('/team', [ManagerDashboardController::class, 'team'])->name('team');
    Route::get('/approvals', [ManagerDashboardController::class, 'approvals'])->name('approvals');
    Route::get('/analytics', [ManagerDashboardController::class, 'analytics'])->name('analytics');
});

// Employee Dashboard Routes
Route::middleware(['auth', 'verified', 'dashboard.role:employee'])->prefix('dashboard/employee')->name('dashboard.employee.')->group(function () {
    Route::get('/', [EmployeeDashboardController::class, 'index'])->name('index');
    Route::get('/timers', [EmployeeDashboardController::class, 'timers'])->name('timers');
    Route::get('/time-entries', [EmployeeDashboardController::class, 'timeEntries'])->name('time-entries');
    Route::get('/analytics', [EmployeeDashboardController::class, 'analytics'])->name('analytics');
});

// Customer Portal Routes
Route::middleware(['auth', 'verified', 'dashboard.role:portal'])->prefix('portal')->name('portal.')->group(function () {
    Route::get('/', [CustomerPortalController::class, 'index'])->name('index');
    Route::get('/time-tracking', [CustomerPortalController::class, 'timeTracking'])->name('time-tracking');
    Route::get('/billing', [CustomerPortalController::class, 'billing'])->name('billing');
    Route::get('/settings', [CustomerPortalController::class, 'settings'])->name('settings');
});

// Main application routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Tickets management
    Route::get('/tickets', [App\Http\Controllers\Api\TicketController::class, 'indexView'])->name('tickets.index');
    Route::get('/tickets/create', [App\Http\Controllers\Api\TicketController::class, 'create'])->name('tickets.create');
    Route::get('/tickets/{ticket}', function ($ticket) {
        return Inertia::render('Tickets/Show', ['ticketId' => $ticket]);
    })->name('tickets.show');
    
    
    // Placeholder routes for navigation (to be implemented later)
    Route::get('/time-entries/{tab?}', function ($tab = null) {
        return Inertia::render('TimeEntries/Index', [
            'activeTab' => $tab ?: 'time-entries'
        ]);
    })->name('time-entries.index')->where('tab', '(time-entries|timers)');
    
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');
    
    Route::get('/settings/{tab?}', function ($tab = null) {
        return Inertia::render('Settings/Index', [
            'activeTab' => $tab
        ]);
    })->name('settings.index')->where('tab', '(system|email|tickets|billing|timer|users)');
    
    // User Management
    Route::get('/users', function () {
        return Inertia::render('Users/Index');
    })->name('users.index');
    
    Route::get('/users/{user}', function ($user) {
        return Inertia::render('Users/Show', ['userId' => $user]);
    })->name('users.show');
    
    // Billing Management
    Route::get('/billing', function () {
        return Inertia::render('Billing/Index');
    })->name('billing.index');
    
    // Account Management
    Route::get('/accounts', function () {
        return Inertia::render('Accounts/Index');
    })->name('accounts.index');
    
    Route::get('/accounts/{account}', function ($account) {
        return Inertia::render('Accounts/Show', ['accountId' => $account]);
    })->name('accounts.show');
    
    // Roles & Permissions Management
    Route::get('/roles', function () {
        return Inertia::render('Roles/Index');
    })->name('roles.index');
    
    Route::get('/roles/{roleTemplate}', function ($roleTemplate) {
        return Inertia::render('Roles/Show', ['roleTemplateId' => $roleTemplate]);
    })->name('roles.show');
    
    Route::get('/roles/{roleTemplate}/edit', function ($roleTemplate) {
        return Inertia::render('Roles/Edit', ['roleTemplateId' => $roleTemplate]);
    })->name('roles.edit');
    
    Route::get('/roles/create', function () {
        return Inertia::render('Roles/Create');
    })->name('roles.create');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
