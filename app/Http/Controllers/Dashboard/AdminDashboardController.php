<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Account;
use App\Models\Timer;
use App\Models\TimeEntry;
use App\Models\RoleTemplate;
use App\Models\DomainMapping;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class AdminDashboardController extends Controller
{
    /**
     * Display the admin dashboard overview
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Verify admin access
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'admin.manage')->exists()) {
            abort(403, 'Access denied. Admin permissions required.');
        }

        // Get system-wide statistics
        $stats = $this->getSystemStatistics();
        
        // Get recent system activity
        $recentActivity = $this->getRecentSystemActivity();
        
        // Get system health metrics
        $systemHealth = $this->getSystemHealthMetrics();

        return Inertia::render('Dashboard/Admin/Index', [
            'title' => 'System Administration',
            'stats' => $stats,
            'recentActivity' => $recentActivity,
            'systemHealth' => $systemHealth,
            'dashboardType' => 'admin'
        ]);
    }
    
    /**
     * User management interface
     */
    public function users(Request $request)
    {
        $users = User::with(['accounts', 'roleTemplates'])
            ->withCount(['timers', 'timeEntries'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
            })
            ->paginate(20);

        return Inertia::render('Dashboard/Admin/Users', [
            'title' => 'User Management',
            'users' => $users,
            'dashboardType' => 'admin'
        ]);
    }
    
    /**
     * Account hierarchy management
     */
    public function accounts(Request $request)
    {
        $accounts = Account::with(['parent', 'children', 'users'])
            ->withCount(['users', 'projects'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(20);

        return Inertia::render('Dashboard/Admin/Accounts', [
            'title' => 'Account Management',
            'accounts' => $accounts,
            'dashboardType' => 'admin'
        ]);
    }
    
    /**
     * Role template administration
     */
    public function roleTemplates(Request $request)
    {
        $roleTemplates = RoleTemplate::withCount(['users'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(20);

        return Inertia::render('Dashboard/Admin/RoleTemplates', [
            'title' => 'Role Template Management',
            'roleTemplates' => $roleTemplates,
            'dashboardType' => 'admin'
        ]);
    }
    
    /**
     * Domain mapping configuration
     */
    public function domainMappings(Request $request)
    {
        $domainMappings = DomainMapping::with(['account'])
            ->when($request->search, function ($query, $search) {
                $query->where('domain_pattern', 'like', "%{$search}%");
            })
            ->orderBy('priority', 'desc')
            ->paginate(20);

        return Inertia::render('Dashboard/Admin/DomainMappings', [
            'title' => 'Domain Mapping Configuration',
            'domainMappings' => $domainMappings,
            'dashboardType' => 'admin'
        ]);
    }
    
    /**
     * System settings interface
     */
    public function settings(Request $request)
    {
        // Get system configuration settings
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'mail_from_address' => config('mail.from.address'),
            'sanctum_expiration' => config('sanctum.expiration'),
            'broadcast_connection' => config('broadcasting.default'),
            'cache_store' => config('cache.default'),
            'queue_connection' => config('queue.default')
        ];

        return Inertia::render('Dashboard/Admin/Settings', [
            'title' => 'System Settings',
            'settings' => $settings,
            'dashboardType' => 'admin'
        ]);
    }
    
    /**
     * Get system-wide statistics
     */
    private function getSystemStatistics(): array
    {
        $now = Carbon::now();
        $lastMonth = $now->copy()->subMonth();
        
        return [
            'total_users' => User::count(),
            'total_accounts' => Account::count(),
            'active_timers' => Timer::where('status', 'running')->count(),
            'total_time_entries' => TimeEntry::count(),
            'users_this_month' => User::where('created_at', '>=', $lastMonth)->count(),
            'time_tracked_today' => TimeEntry::whereDate('started_at', $now->toDateString())
                ->sum('duration'),
            'pending_approvals' => TimeEntry::where('status', 'pending')->count(),
            'domain_mappings' => DomainMapping::where('is_active', true)->count()
        ];
    }
    
    /**
     * Get recent system activity
     */
    private function getRecentSystemActivity(): array
    {
        $recentUsers = User::latest()->limit(5)->get(['id', 'name', 'email', 'created_at']);
        $recentTimers = Timer::with(['user:id,name', 'account:id,name'])
            ->latest()->limit(10)->get();
        $recentTimeEntries = TimeEntry::with(['user:id,name', 'account:id,name'])
            ->latest()->limit(10)->get();
            
        return [
            'recent_users' => $recentUsers,
            'recent_timers' => $recentTimers,
            'recent_time_entries' => $recentTimeEntries
        ];
    }
    
    /**
     * Get system health metrics
     */
    private function getSystemHealthMetrics(): array
    {
        try {
            // Check database connection
            $dbStatus = \DB::connection()->getPdo() ? 'healthy' : 'error';
        } catch (\Exception $e) {
            $dbStatus = 'error';
        }
        
        try {
            // Check Redis connection using Laravel's Redis facade
            \Illuminate\Support\Facades\Redis::ping();
            $redisStatus = 'healthy';
        } catch (\Exception $e) {
            $redisStatus = 'error';
        }
        
        return [
            'database' => $dbStatus,
            'redis' => $redisStatus,
            'storage_disk' => disk_free_space(storage_path()) > 1000000000 ? 'healthy' : 'warning',
            'queue_jobs' => \DB::table('jobs')->count(),
            'failed_jobs' => \DB::table('failed_jobs')->count()
        ];
    }
}
