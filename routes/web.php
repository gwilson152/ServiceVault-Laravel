<?php

use App\Http\Controllers\DashboardController;
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

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// Default dashboard route redirects based on user role
Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

// Role-based dashboard routes
Route::middleware(['auth', 'verified'])->group(function () {
    // Admin Dashboard
    Route::get('/dashboard/admin', [DashboardController::class, 'admin'])->name('dashboard.admin');
    
    // Manager Dashboard
    Route::get('/dashboard/manager', [DashboardController::class, 'manager'])->name('dashboard.manager');
    
    // Employee Dashboard
    Route::get('/dashboard/employee', [DashboardController::class, 'employee'])->name('dashboard.employee');
    
    // Customer Portal
    Route::get('/portal', [DashboardController::class, 'portal'])->name('portal.dashboard');
    
    // Timer management (frontend routes)
    Route::get('/timers', function () {
        return Inertia::render('Timer/Index');
    })->name('timers.web.index');
    
    // Placeholder routes for navigation (to be implemented later)
    Route::get('/time-entries', function () {
        return Inertia::render('TimeEntries/Index');
    })->name('time-entries.index');
    
    Route::get('/reports', function () {
        return Inertia::render('Reports/Index');
    })->name('reports.index');
    
    Route::get('/settings', function () {
        return Inertia::render('Settings/Index');
    })->name('settings.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
