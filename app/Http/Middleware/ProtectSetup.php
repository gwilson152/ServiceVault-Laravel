<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ProtectSetup
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if system is already set up (cache for 5 minutes)
        $isSetup = Cache::remember('system_setup_status', 300, function () {
            return $this->isSystemSetup();
        });

        // If system is not set up, allow access to setup routes
        if (! $isSetup) {
            return $next($request);
        }

        // System is set up - require authentication and Super Admin role
        if (! Auth::check()) {
            return redirect()->route('login')->with('error', 'You must be logged in to access system setup.');
        }

        $user = Auth::user();

        // Handle case where user was authenticated before database reset
        if (! $user || ! $user->exists) {
            Auth::logout();

            return redirect()->route('login')->with('error', 'Your session has expired. Please log in again.');
        }

        // Only Super Admin can access setup after system is configured
        if (! $user->isSuperAdmin()) {
            return redirect()->route('dashboard')->with('error', 'Access denied. Only Super Administrators can access system setup.');
        }

        return $next($request);
    }

    /**
     * Determine if the system has been set up.
     */
    private function isSystemSetup(): bool
    {
        $setting = \App\Models\Setting::where('key', 'system.setup_complete')->first();

        return $setting && ($setting->value === true || $setting->value === 'true' || $setting->value === 1 || $setting->value === '1');
    }
}
