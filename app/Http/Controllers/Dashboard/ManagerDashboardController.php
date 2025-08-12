<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Timer;
use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\Account;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class ManagerDashboardController extends Controller
{
    /**
     * Display the manager dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Verify manager access
        if (!$user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            abort(403, 'Access denied. Manager permissions required.');
        }

        // Get accounts the manager oversees
        $managedAccounts = $this->getManagedAccounts($user);
        
        // Get team overview statistics
        $teamStats = $this->getTeamStatistics($user, $managedAccounts);
        
        // Get pending approvals
        $pendingApprovals = $this->getPendingApprovals($user, $managedAccounts);
        
        // Get team activity
        $teamActivity = $this->getTeamActivity($user, $managedAccounts);
        
        // Get performance metrics
        $performanceMetrics = $this->getPerformanceMetrics($user, $managedAccounts);

        return Inertia::render('Dashboard/Manager/Index', [
            'title' => 'Team Management',
            'teamStats' => $teamStats,
            'pendingApprovals' => $pendingApprovals,
            'teamActivity' => $teamActivity,
            'performanceMetrics' => $performanceMetrics,
            'managedAccounts' => $managedAccounts,
            'dashboardType' => 'manager'
        ]);
    }
    
    /**
     * Team oversight interface
     */
    public function team(Request $request)
    {
        $user = $request->user();
        $managedAccounts = $this->getManagedAccounts($user);
        
        // Get team members with their current activity
        $teamMembers = User::whereHas('accounts', function ($query) use ($managedAccounts) {
                $query->whereIn('accounts.id', $managedAccounts->pluck('id'));
            })
            ->with(['accounts', 'roleTemplate'])
            ->withCount([
                'timers as active_timers_count' => function ($query) {
                    $query->whereIn('status', ['running', 'paused']);
                },
                'timeEntries as pending_entries_count' => function ($query) {
                    $query->where('status', 'pending');
                }
            ])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(20);
            
        // Get current team activity
        $currentActivity = Timer::whereIn('account_id', $managedAccounts->pluck('id'))
            ->whereIn('status', ['running', 'paused'])
            ->with(['user:id,name', 'account:id,name', 'project:id,name'])
            ->get();

        return Inertia::render('Dashboard/Manager/Team', [
            'title' => 'Team Overview',
            'teamMembers' => $teamMembers,
            'currentActivity' => $currentActivity,
            'managedAccounts' => $managedAccounts,
            'dashboardType' => 'manager'
        ]);
    }
    
    /**
     * Approval workflow interface
     */
    public function approvals(Request $request)
    {
        $user = $request->user();
        $managedAccounts = $this->getManagedAccounts($user);
        
        $pendingApprovals = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('status', 'pending')
            ->with(['user:id,name', 'account:id,name', 'project:id,name'])
            ->when($request->user_id, function ($query, $userId) {
                $query->where('user_id', $userId);
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
            
        // Get approval statistics
        $approvalStats = [
            'pending_count' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('status', 'pending')->count(),
            'approved_today' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('status', 'approved')
                ->whereDate('updated_at', Carbon::today())->count(),
            'rejected_today' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('status', 'rejected')
                ->whereDate('updated_at', Carbon::today())->count(),
            'average_approval_time' => $this->getAverageApprovalTime($managedAccounts)
        ];

        return Inertia::render('Dashboard/Manager/Approvals', [
            'title' => 'Approval Management',
            'pendingApprovals' => $pendingApprovals,
            'approvalStats' => $approvalStats,
            'teamMembers' => User::whereHas('accounts', function ($query) use ($managedAccounts) {
                $query->whereIn('accounts.id', $managedAccounts->pluck('id'));
            })->get(['id', 'name']),
            'managedAccounts' => $managedAccounts,
            'dashboardType' => 'manager'
        ]);
    }
    
    /**
     * Project management interface
     */
    public function projects(Request $request)
    {
        $user = $request->user();
        $managedAccounts = $this->getManagedAccounts($user);
        
        $projects = Project::whereIn('account_id', $managedAccounts->pluck('id'))
            ->with(['account:id,name'])
            ->withCount(['timers', 'timeEntries'])
            ->withSum('timeEntries as total_hours', 'duration')
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->when($request->account_id, function ($query, $accountId) {
                $query->where('account_id', $accountId);
            })
            ->paginate(20);
            
        // Get project statistics
        $projectStats = [
            'total_projects' => Project::whereIn('account_id', $managedAccounts->pluck('id'))->count(),
            'active_projects' => Project::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('status', 'active')->count(),
            'completed_projects' => Project::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('status', 'completed')->count(),
            'total_project_hours' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->sum('duration') / 3600
        ];

        return Inertia::render('Dashboard/Manager/Projects', [
            'title' => 'Project Management',
            'projects' => $projects,
            'projectStats' => $projectStats,
            'managedAccounts' => $managedAccounts,
            'dashboardType' => 'manager'
        ]);
    }
    
    /**
     * Team analytics and reports
     */
    public function analytics(Request $request)
    {
        $user = $request->user();
        $managedAccounts = $this->getManagedAccounts($user);
        $period = $request->input('period', '30'); // days
        $startDate = Carbon::now()->subDays($period);
        
        // Team performance analytics
        $teamAnalytics = [
            'productivity_trends' => $this->getTeamProductivityTrends($managedAccounts, $startDate),
            'utilization_rates' => $this->getUtilizationRates($managedAccounts, $startDate),
            'project_progress' => $this->getProjectProgress($managedAccounts, $startDate),
            'approval_metrics' => $this->getApprovalMetrics($managedAccounts, $startDate)
        ];
        
        // Department metrics
        $departmentMetrics = [
            'average_hours_per_employee' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('created_at', '>=', $startDate)
                ->sum('duration') / 3600 / $managedAccounts->sum('users_count'),
            'billable_vs_nonbillable' => $this->getBillableRatio($managedAccounts, $startDate),
            'cost_per_project' => $this->getCostPerProject($managedAccounts, $startDate)
        ];

        return Inertia::render('Dashboard/Manager/Analytics', [
            'title' => 'Team Analytics',
            'teamAnalytics' => $teamAnalytics,
            'departmentMetrics' => $departmentMetrics,
            'period' => $period,
            'managedAccounts' => $managedAccounts,
            'dashboardType' => 'manager'
        ]);
    }
    
    /**
     * Get accounts managed by the user
     */
    private function getManagedAccounts($user)
    {
        // Managers can oversee accounts they're assigned to with manage permissions
        return $user->accounts()
            ->whereHas('users', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->withCount(['users'])
            ->get();
    }
    
    /**
     * Get team statistics for dashboard
     */
    private function getTeamStatistics($user, $managedAccounts): array
    {
        $today = Carbon::today();
        $thisWeek = Carbon::now()->startOfWeek();
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'total_team_members' => User::whereHas('accounts', function ($query) use ($managedAccounts) {
                $query->whereIn('accounts.id', $managedAccounts->pluck('id'));
            })->count(),
            'active_timers' => Timer::whereIn('account_id', $managedAccounts->pluck('id'))
                ->whereIn('status', ['running', 'paused'])->count(),
            'team_hours_today' => Timer::whereIn('account_id', $managedAccounts->pluck('id'))
                ->whereDate('started_at', $today)
                ->sum('total_duration'),
            'team_hours_this_week' => Timer::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('started_at', '>=', $thisWeek)
                ->sum('total_duration'),
            'pending_approvals' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->where('status', 'pending')->count(),
            'projects_count' => Project::whereIn('account_id', $managedAccounts->pluck('id'))->count()
        ];
    }
    
    /**
     * Get pending approvals
     */
    private function getPendingApprovals($user, $managedAccounts)
    {
        return TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('status', 'pending')
            ->with(['user:id,name', 'account:id,name', 'project:id,name'])
            ->latest()
            ->limit(10)
            ->get();
    }
    
    /**
     * Get team activity
     */
    private function getTeamActivity($user, $managedAccounts)
    {
        return [
            'active_timers' => Timer::whereIn('account_id', $managedAccounts->pluck('id'))
                ->whereIn('status', ['running', 'paused'])
                ->with(['user:id,name', 'account:id,name', 'project:id,name'])
                ->get(),
            'recent_entries' => TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
                ->with(['user:id,name', 'account:id,name', 'project:id,name'])
                ->latest()
                ->limit(10)
                ->get()
        ];
    }
    
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($user, $managedAccounts): array
    {
        $lastWeek = Carbon::now()->subWeek();
        
        return [
            'team_productivity' => $this->calculateTeamProductivity($managedAccounts, $lastWeek),
            'approval_efficiency' => $this->calculateApprovalEfficiency($managedAccounts, $lastWeek),
            'project_completion_rate' => $this->calculateProjectCompletionRate($managedAccounts, $lastWeek)
        ];
    }
    
    /**
     * Helper methods for analytics
     */
    private function getAverageApprovalTime($managedAccounts): float
    {
        $approvals = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, approved_at)) as avg_hours')
            ->first();
            
        return $approvals->avg_hours ?? 0;
    }
    
    private function getTeamProductivityTrends($managedAccounts, $startDate): array
    {
        return TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as entries, SUM(duration) / 3600 as hours')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
    
    private function getUtilizationRates($managedAccounts, $startDate): array
    {
        // Placeholder implementation - would calculate based on working hours vs tracked hours
        return [
            'overall_utilization' => 75.5,
            'by_account' => $managedAccounts->map(function ($account) {
                return [
                    'account_id' => $account->id,
                    'account_name' => $account->name,
                    'utilization_rate' => rand(65, 85) // Placeholder
                ];
            })->toArray()
        ];
    }
    
    private function getProjectProgress($managedAccounts, $startDate): array
    {
        return Project::whereIn('account_id', $managedAccounts->pluck('id'))
            ->with(['account:id,name'])
            ->withCount(['timeEntries'])
            ->withSum('timeEntries as total_hours', 'duration')
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'account_name' => $project->account->name,
                    'total_hours' => ($project->total_hours ?? 0) / 3600,
                    'progress_percentage' => rand(20, 95) // Placeholder - would calculate based on tasks
                ];
            })
            ->toArray();
    }
    
    private function getApprovalMetrics($managedAccounts, $startDate): array
    {
        $total = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('created_at', '>=', $startDate)->count();
        $approved = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('created_at', '>=', $startDate)
            ->where('status', 'approved')->count();
        $rejected = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('created_at', '>=', $startDate)
            ->where('status', 'rejected')->count();
            
        return [
            'approval_rate' => $total > 0 ? ($approved / $total * 100) : 0,
            'rejection_rate' => $total > 0 ? ($rejected / $total * 100) : 0,
            'pending_rate' => $total > 0 ? (($total - $approved - $rejected) / $total * 100) : 0
        ];
    }
    
    private function getBillableRatio($managedAccounts, $startDate): array
    {
        $billable = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('created_at', '>=', $startDate)
            ->where('billable', true)
            ->sum('duration');
        $nonBillable = TimeEntry::whereIn('account_id', $managedAccounts->pluck('id'))
            ->where('created_at', '>=', $startDate)
            ->where('billable', false)
            ->sum('duration');
            
        return [
            'billable_hours' => $billable / 3600,
            'non_billable_hours' => $nonBillable / 3600,
            'billable_percentage' => ($billable + $nonBillable) > 0 ? ($billable / ($billable + $nonBillable) * 100) : 0
        ];
    }
    
    private function getCostPerProject($managedAccounts, $startDate): array
    {
        // Placeholder - would calculate based on billing rates and time entries
        return Project::whereIn('account_id', $managedAccounts->pluck('id'))
            ->with(['account:id,name'])
            ->get()
            ->map(function ($project) {
                return [
                    'project_id' => $project->id,
                    'project_name' => $project->name,
                    'estimated_cost' => rand(5000, 50000) // Placeholder
                ];
            })
            ->toArray();
    }
    
    private function calculateTeamProductivity($managedAccounts, $startDate): float
    {
        // Placeholder calculation
        return 82.5;
    }
    
    private function calculateApprovalEfficiency($managedAccounts, $startDate): float
    {
        // Placeholder calculation  
        return 91.2;
    }
    
    private function calculateProjectCompletionRate($managedAccounts, $startDate): float
    {
        // Placeholder calculation
        return 76.8;
    }
}
