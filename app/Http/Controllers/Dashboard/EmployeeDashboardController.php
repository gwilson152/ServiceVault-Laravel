<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Timer;
use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class EmployeeDashboardController extends Controller
{
    /**
     * Display the employee dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get user's accessible accounts
        $accessibleAccounts = $user->accounts()->with(['projects'])->get();
        
        // Get active timers for multi-timer display
        $activeTimers = Timer::where('user_id', $user->id)
            ->whereIn('status', ['running', 'paused'])
            ->with(['account:id,name', 'project:id,name', 'billingRate'])
            ->get();
            
        // Get user statistics
        $stats = $this->getUserStatistics($user);
        
        // Get recent time entries
        $recentTimeEntries = TimeEntry::where('user_id', $user->id)
            ->with(['account:id,name', 'project:id,name'])
            ->latest()
            ->limit(10)
            ->get();
            
        // Get quick start options (projects and billing rates)
        $quickStartOptions = $this->getQuickStartOptions($user, $accessibleAccounts);

        return Inertia::render('Dashboard/Employee/Index', [
            'title' => 'My Dashboard',
            'activeTimers' => $activeTimers,
            'stats' => $stats,
            'recentTimeEntries' => $recentTimeEntries,
            'quickStartOptions' => $quickStartOptions,
            'accessibleAccounts' => $accessibleAccounts,
            'dashboardType' => 'employee'
        ]);
    }
    
    /**
     * Timer management interface with multi-timer support
     */
    public function timers(Request $request)
    {
        $user = $request->user();
        
        $timers = Timer::where('user_id', $user->id)
            ->with(['account:id,name', 'project:id,name', 'billingRate'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->account_id, function ($query, $accountId) {
                $query->where('account_id', $accountId);
            })
            ->latest()
            ->paginate(20);
            
        // Get timer statistics
        $timerStats = [
            'active_count' => Timer::where('user_id', $user->id)
                ->whereIn('status', ['running', 'paused'])->count(),
            'running_count' => Timer::where('user_id', $user->id)
                ->where('status', 'running')->count(),
            'paused_count' => Timer::where('user_id', $user->id)
                ->where('status', 'paused')->count(),
            'today_duration' => Timer::where('user_id', $user->id)
                ->whereDate('started_at', Carbon::today())
                ->sum('total_duration')
        ];

        return Inertia::render('Dashboard/Employee/Timers', [
            'title' => 'My Timers',
            'timers' => $timers,
            'timerStats' => $timerStats,
            'accessibleAccounts' => $user->accounts()->get(['id', 'name']),
            'dashboardType' => 'employee'
        ]);
    }
    
    /**
     * Time entries interface
     */
    public function timeEntries(Request $request)
    {
        $user = $request->user();
        
        $timeEntries = TimeEntry::where('user_id', $user->id)
            ->with(['account:id,name', 'project:id,name'])
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->account_id, function ($query, $accountId) {
                $query->where('account_id', $accountId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            })
            ->latest()
            ->paginate(20);
            
        // Get time entry statistics
        $entryStats = [
            'pending_count' => TimeEntry::where('user_id', $user->id)
                ->where('status', 'pending')->count(),
            'approved_count' => TimeEntry::where('user_id', $user->id)
                ->where('status', 'approved')->count(),
            'total_hours' => TimeEntry::where('user_id', $user->id)
                ->sum('duration') / 3600,
            'billable_hours' => TimeEntry::where('user_id', $user->id)
                ->where('billable', true)->sum('duration') / 3600
        ];

        return Inertia::render('Dashboard/Employee/TimeEntries', [
            'title' => 'My Time Entries',
            'timeEntries' => $timeEntries,
            'entryStats' => $entryStats,
            'accessibleAccounts' => $user->accounts()->get(['id', 'name']),
            'dashboardType' => 'employee'
        ]);
    }
    
    /**
     * Personal analytics and reports
     */
    public function analytics(Request $request)
    {
        $user = $request->user();
        $period = $request->input('period', '30'); // days
        $startDate = Carbon::now()->subDays($period);
        
        // Time tracking analytics
        $timeAnalytics = [
            'daily_hours' => $this->getDailyHours($user, $startDate),
            'project_breakdown' => $this->getProjectBreakdown($user, $startDate),
            'account_breakdown' => $this->getAccountBreakdown($user, $startDate),
            'productivity_trends' => $this->getProductivityTrends($user, $startDate)
        ];
        
        // Personal metrics
        $personalMetrics = [
            'average_daily_hours' => TimeEntry::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->sum('duration') / 3600 / $period,
            'total_projects' => TimeEntry::where('user_id', $user->id)
                ->where('created_at', '>=', $startDate)
                ->distinct('project_id')->count('project_id'),
            'completion_rate' => $this->getTaskCompletionRate($user, $startDate)
        ];

        return Inertia::render('Dashboard/Employee/Analytics', [
            'title' => 'My Analytics',
            'timeAnalytics' => $timeAnalytics,
            'personalMetrics' => $personalMetrics,
            'period' => $period,
            'dashboardType' => 'employee'
        ]);
    }
    
    /**
     * Get user statistics for dashboard overview
     */
    private function getUserStatistics($user): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'active_timers' => Timer::where('user_id', $user->id)
                ->whereIn('status', ['running', 'paused'])->count(),
            'time_today' => Timer::where('user_id', $user->id)
                ->whereDate('started_at', $today)
                ->sum('total_duration'),
            'time_this_week' => Timer::where('user_id', $user->id)
                ->where('started_at', '>=', $thisWeek)
                ->sum('total_duration'),
            'time_this_month' => Timer::where('user_id', $user->id)
                ->where('started_at', '>=', $thisMonth)
                ->sum('total_duration'),
            'pending_entries' => TimeEntry::where('user_id', $user->id)
                ->where('status', 'pending')->count(),
            'approved_entries' => TimeEntry::where('user_id', $user->id)
                ->where('status', 'approved')->count()
        ];
    }
    
    /**
     * Get quick start options for new timers
     */
    private function getQuickStartOptions($user, $accessibleAccounts): array
    {
        $recentProjects = Project::whereIn('account_id', $accessibleAccounts->pluck('id'))
            ->whereHas('timers', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->with(['account:id,name'])
            ->distinct()
            ->limit(10)
            ->get(['id', 'name', 'account_id']);
            
        $recentDescriptions = Timer::where('user_id', $user->id)
            ->whereNotNull('description')
            ->distinct('description')
            ->latest()
            ->limit(10)
            ->pluck('description');
            
        return [
            'recent_projects' => $recentProjects,
            'recent_descriptions' => $recentDescriptions,
            'favorite_accounts' => $accessibleAccounts->take(5)
        ];
    }
    
    /**
     * Get daily hours for analytics
     */
    private function getDailyHours($user, $startDate): array
    {
        return TimeEntry::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(duration) / 3600 as hours')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
    
    /**
     * Get project breakdown for analytics
     */
    private function getProjectBreakdown($user, $startDate): array
    {
        return TimeEntry::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->with('project:id,name')
            ->selectRaw('project_id, SUM(duration) / 3600 as hours, COUNT(*) as entries')
            ->groupBy('project_id')
            ->orderByDesc('hours')
            ->get()
            ->toArray();
    }
    
    /**
     * Get account breakdown for analytics
     */
    private function getAccountBreakdown($user, $startDate): array
    {
        return TimeEntry::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->with('account:id,name')
            ->selectRaw('account_id, SUM(duration) / 3600 as hours, COUNT(*) as entries')
            ->groupBy('account_id')
            ->orderByDesc('hours')
            ->get()
            ->toArray();
    }
    
    /**
     * Get productivity trends
     */
    private function getProductivityTrends($user, $startDate): array
    {
        // Simple productivity calculation based on time entries per day
        return TimeEntry::where('user_id', $user->id)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as entries, SUM(duration) / 3600 as hours')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                $item['productivity_score'] = ($item['entries'] * 0.3) + ($item['hours'] * 0.7);
                return $item;
            })
            ->toArray();
    }
    
    /**
     * Get task completion rate (placeholder for future implementation)
     */
    private function getTaskCompletionRate($user, $startDate): float
    {
        // Placeholder - would calculate based on task management system
        return 85.5;
    }
}
