<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\TimeEntry;
use App\Services\AuthRedirectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Redirect to appropriate dashboard based on user role.
     */
    public function index(Request $request, AuthRedirectService $redirectService): RedirectResponse
    {
        $user = $request->user();
        $redirectPath = $redirectService->getRedirectPath($user);

        return redirect($redirectPath);
    }

    /**
     * Show the admin dashboard.
     */
    public function admin(Request $request): Response
    {
        // Admin-specific stats
        $totalUsers = \App\Models\User::count();
        $totalAccounts = Account::count();
        $recentRegistrations = \App\Models\User::orderBy('created_at', 'desc')->take(5)->get();

        return Inertia::render('Dashboard/Admin', [
            'stats' => [
                'total_users' => $totalUsers,
                'total_accounts' => $totalAccounts,
                'recent_registrations' => $recentRegistrations,
            ],
        ]);
    }

    /**
     * Show the manager dashboard.
     */
    public function manager(Request $request): Response
    {
        // Manager-specific functionality
        return Inertia::render('Dashboard/Manager', [
            'stats' => $this->getBasicStats($request->user()),
        ]);
    }

    /**
     * Show the employee dashboard.
     */
    public function employee(Request $request): Response
    {
        return Inertia::render('Dashboard', [
            'stats' => $this->getBasicStats($request->user()),
            'recentEntries' => $this->getRecentEntries($request->user()),
        ]);
    }

    /**
     * Show the customer portal dashboard.
     */
    public function portal(Request $request): Response
    {
        $user = $request->user();

        // Customer portal specific data - limited view
        return Inertia::render('Portal/Dashboard', [
            'stats' => [
                'account_name' => $user->currentAccount->name ?? 'Personal',
                'tickets_open' => 0, // TODO: implement ticket system
            ],
        ]);
    }

    /**
     * Get basic stats for a user.
     */
    private function getBasicStats($user): array
    {
        $today = now()->startOfDay();
        $todayEntries = TimeEntry::where('user_id', $user->id)
            ->where('started_at', '>=', $today)
            ->with('billingRate')
            ->get();

        $weekStart = now()->startOfWeek();
        $weekEntries = TimeEntry::where('user_id', $user->id)
            ->where('started_at', '>=', $weekStart)
            ->get();

        return [
            'today_duration' => $todayEntries->sum('duration'),
            'today_earnings' => $todayEntries->sum(function ($entry) {
                if ($entry->billingRate) {
                    return ($entry->duration / 3600) * $entry->billingRate->rate;
                }

                return 0;
            }),
            'week_duration' => $weekEntries->sum('duration'),
            'active_projects' => TimeEntry::where('user_id', $user->id)
                ->whereNotNull('project_id')
                ->distinct('project_id')
                ->count(),
        ];
    }

    /**
     * Get recent time entries for a user.
     */
    private function getRecentEntries($user)
    {
        return TimeEntry::where('user_id', $user->id)
            ->with(['project', 'billingRate'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
    }
}
