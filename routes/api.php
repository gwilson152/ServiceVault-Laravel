<?php

use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\DomainMappingController;
use App\Http\Controllers\Api\TimerController;
use App\Http\Middleware\CheckPermission;
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
});

// Protected routes requiring specific permissions
Route::middleware(['auth:web,sanctum', CheckPermission::class])->group(function () {
    
    // Admin-only routes
    Route::prefix('admin')->group(function () {
        // Future admin routes
    });
    
    // Manager-only routes  
    Route::prefix('manager')->group(function () {
        // Future manager routes
    });
});