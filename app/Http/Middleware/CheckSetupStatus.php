<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Models\Account;
use App\Models\RoleTemplate;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class CheckSetupStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip setup check for setup routes, API routes, and system routes
        if ($request->is('setup*') || $request->is('api/*') || $request->is('_*')) {
            return $next($request);
        }

        // Check if system is already set up (cache for 5 minutes)
        $isSetup = Cache::remember('system_setup_status', 300, function () {
            return $this->isSystemSetup();
        });

        // If system is not set up, redirect to setup page
        if (!$isSetup) {
            return redirect()->route('setup.index');
        }

        return $next($request);
    }

    /**
     * Determine if the system has been set up.
     */
    private function isSystemSetup(): bool
    {
        // Check if we have at least one admin user and one account
        return User::count() > 0 
            && Account::count() > 0 
            && RoleTemplate::where('is_system_role', true)->count() > 0;
    }
}
