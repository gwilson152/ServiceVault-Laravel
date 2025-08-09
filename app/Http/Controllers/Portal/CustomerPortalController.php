<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\TimeEntry;
use App\Models\Project;
use App\Models\Account;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;

class CustomerPortalController extends Controller
{
    /**
     * Display the customer portal dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Verify portal access
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'portal.access')->exists() &&
            !$user->roleTemplates()->whereJsonContains('permissions', 'accounts.view')->exists()) {
            abort(403, 'Access denied. Portal access required.');
        }

        // Get customer's account (customers are usually assigned to one primary account)
        $customerAccount = $user->currentAccount ?? $user->accounts()->first();
        
        if (!$customerAccount) {
            return Inertia::render('Portal/NoAccess', [
                'title' => 'No Account Access',
                'message' => 'You have not been assigned to any accounts. Please contact your administrator.'
            ]);
        }
        
        // Get account overview statistics
        $accountStats = $this->getAccountStatistics($customerAccount);
        
        // Get recent project activity
        $recentActivity = $this->getRecentActivity($customerAccount);
        
        // Get billing information (if customer has billing access)
        $billingInfo = $this->getBillingInformation($user, $customerAccount);
        
        // Get upcoming milestones or deadlines
        $upcomingItems = $this->getUpcomingItems($customerAccount);

        return Inertia::render('Portal/Index', [
            'title' => 'Customer Portal',
            'customerAccount' => $customerAccount,
            'accountStats' => $accountStats,
            'recentActivity' => $recentActivity,
            'billingInfo' => $billingInfo,
            'upcomingItems' => $upcomingItems,
            'dashboardType' => 'portal'
        ]);
    }
    
    /**
     * View projects and tickets
     */
    public function projects(Request $request)
    {
        $user = $request->user();
        $customerAccount = $user->currentAccount ?? $user->accounts()->first();
        
        if (!$customerAccount) {
            abort(404, 'No account access.');
        }
        
        $projects = Project::where('account_id', $customerAccount->id)
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
            })
            ->withCount(['timeEntries', 'tasks'])
            ->withSum('timeEntries as total_hours', 'duration')
            ->latest()
            ->paginate(20);
            
        // Get project statistics
        $projectStats = [
            'total_projects' => Project::where('account_id', $customerAccount->id)->count(),
            'active_projects' => Project::where('account_id', $customerAccount->id)
                ->where('status', 'active')->count(),
            'completed_projects' => Project::where('account_id', $customerAccount->id)
                ->where('status', 'completed')->count(),
            'total_hours_tracked' => TimeEntry::where('account_id', $customerAccount->id)
                ->sum('duration') / 3600
        ];

        return Inertia::render('Portal/Projects', [
            'title' => 'Projects & Tickets',
            'projects' => $projects,
            'projectStats' => $projectStats,
            'customerAccount' => $customerAccount,
            'dashboardType' => 'portal'
        ]);
    }
    
    /**
     * View time tracking (if permitted)
     */
    public function timeTracking(Request $request)
    {
        $user = $request->user();
        $customerAccount = $user->currentAccount ?? $user->accounts()->first();
        
        if (!$customerAccount) {
            abort(404, 'No account access.');
        }
        
        // Check if customer can view time tracking
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'time.view')->exists()) {
            return Inertia::render('Portal/Restricted', [
                'title' => 'Time Tracking - Access Restricted',
                'message' => 'Time tracking visibility is restricted for your account level.'
            ]);
        }
        
        $timeEntries = TimeEntry::where('account_id', $customerAccount->id)
            ->with(['user:id,name', 'project:id,name'])
            ->when($request->project_id, function ($query, $projectId) {
                $query->where('project_id', $projectId);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->when($request->billable !== null, function ($query) use ($request) {
                $query->where('billable', $request->billable);
            })
            ->latest('date')
            ->paginate(20);
            
        // Get time tracking statistics
        $timeStats = [
            'total_hours' => TimeEntry::where('account_id', $customerAccount->id)
                ->sum('duration') / 3600,
            'billable_hours' => TimeEntry::where('account_id', $customerAccount->id)
                ->where('billable', true)
                ->sum('duration') / 3600,
            'this_month_hours' => TimeEntry::where('account_id', $customerAccount->id)
                ->where('date', '>=', Carbon::now()->startOfMonth())
                ->sum('duration') / 3600,
            'team_members' => TimeEntry::where('account_id', $customerAccount->id)
                ->distinct('user_id')->count('user_id')
        ];

        return Inertia::render('Portal/TimeTracking', [
            'title' => 'Time Tracking',
            'timeEntries' => $timeEntries,
            'timeStats' => $timeStats,
            'projects' => Project::where('account_id', $customerAccount->id)
                ->get(['id', 'name']),
            'customerAccount' => $customerAccount,
            'dashboardType' => 'portal'
        ]);
    }
    
    /**
     * View invoices and billing (if permitted)
     */
    public function billing(Request $request)
    {
        $user = $request->user();
        $customerAccount = $user->currentAccount ?? $user->accounts()->first();
        
        if (!$customerAccount) {
            abort(404, 'No account access.');
        }
        
        // Check if customer can view billing
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'billing.view')->exists()) {
            return Inertia::render('Portal/Restricted', [
                'title' => 'Billing - Access Restricted',
                'message' => 'Billing information is restricted for your account level.'
            ]);
        }
        
        $invoices = Invoice::where('account_id', $customerAccount->id)
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->date_from, function ($query, $dateFrom) {
                $query->whereDate('date', '>=', $dateFrom);
            })
            ->when($request->date_to, function ($query, $dateTo) {
                $query->whereDate('date', '<=', $dateTo);
            })
            ->latest('date')
            ->paginate(20);
            
        // Get billing statistics
        $billingStats = [
            'total_invoiced' => Invoice::where('account_id', $customerAccount->id)
                ->sum('total_amount'),
            'outstanding_amount' => Invoice::where('account_id', $customerAccount->id)
                ->whereIn('status', ['pending', 'sent'])
                ->sum('total_amount'),
            'paid_amount' => Invoice::where('account_id', $customerAccount->id)
                ->where('status', 'paid')
                ->sum('total_amount'),
            'overdue_amount' => Invoice::where('account_id', $customerAccount->id)
                ->where('status', 'overdue')
                ->sum('total_amount')
        ];

        return Inertia::render('Portal/Billing', [
            'title' => 'Billing & Invoices',
            'invoices' => $invoices,
            'billingStats' => $billingStats,
            'customerAccount' => $customerAccount,
            'dashboardType' => 'portal'
        ]);
    }
    
    /**
     * Account settings and profile
     */
    public function settings(Request $request)
    {
        $user = $request->user();
        $customerAccount = $user->currentAccount ?? $user->accounts()->first();
        
        if (!$customerAccount) {
            abort(404, 'No account access.');
        }
        
        // Get account information (limited to what customer can see)
        $accountInfo = [
            'name' => $customerAccount->name,
            'type' => $customerAccount->type,
            'status' => $customerAccount->status,
            'created_at' => $customerAccount->created_at
        ];
        
        // Get user profile information
        $userProfile = [
            'name' => $user->name,
            'email' => $user->email,
            'role_templates' => $user->roleTemplates->pluck('name')->toArray(),
            'created_at' => $user->created_at
        ];

        return Inertia::render('Portal/Settings', [
            'title' => 'Account Settings',
            'accountInfo' => $accountInfo,
            'userProfile' => $userProfile,
            'customerAccount' => $customerAccount,
            'dashboardType' => 'portal'
        ]);
    }
    
    /**
     * Get account statistics for dashboard
     */
    private function getAccountStatistics($account): array
    {
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'active_projects' => Project::where('account_id', $account->id)
                ->where('status', 'active')->count(),
            'completed_projects' => Project::where('account_id', $account->id)
                ->where('status', 'completed')->count(),
            'hours_this_month' => TimeEntry::where('account_id', $account->id)
                ->where('date', '>=', $thisMonth)
                ->sum('duration') / 3600,
            'team_members' => $account->users()->count(),
            'pending_tasks' => 0, // Placeholder for task management
            'completion_rate' => $this->calculateCompletionRate($account)
        ];
    }
    
    /**
     * Get recent activity for dashboard
     */
    private function getRecentActivity($account): array
    {
        $recentTimeEntries = TimeEntry::where('account_id', $account->id)
            ->with(['user:id,name', 'project:id,name'])
            ->latest()
            ->limit(10)
            ->get();
            
        $recentProjects = Project::where('account_id', $account->id)
            ->latest('updated_at')
            ->limit(5)
            ->get(['id', 'name', 'status', 'updated_at']);
            
        return [
            'recent_time_entries' => $recentTimeEntries,
            'recent_project_updates' => $recentProjects
        ];
    }
    
    /**
     * Get billing information (if accessible)
     */
    private function getBillingInformation($user, $account): ?array
    {
        if (!$user->roleTemplates()->whereJsonContains('permissions', 'billing.view')->exists()) {
            return null;
        }
        
        $thisMonth = Carbon::now()->startOfMonth();
        
        return [
            'current_month_hours' => TimeEntry::where('account_id', $account->id)
                ->where('date', '>=', $thisMonth)
                ->where('billable', true)
                ->sum('duration') / 3600,
            'outstanding_invoices' => Invoice::where('account_id', $account->id)
                ->whereIn('status', ['pending', 'sent'])
                ->count(),
            'last_payment_date' => Invoice::where('account_id', $account->id)
                ->where('status', 'paid')
                ->latest('date')
                ->value('date'),
            'next_invoice_date' => $this->calculateNextInvoiceDate($account)
        ];
    }
    
    /**
     * Get upcoming items (deadlines, milestones)
     */
    private function getUpcomingItems($account): array
    {
        // Placeholder for future task management integration
        return [
            'upcoming_deadlines' => [],
            'pending_approvals' => TimeEntry::where('account_id', $account->id)
                ->where('status', 'pending')
                ->count(),
            'scheduled_meetings' => [] // Future integration
        ];
    }
    
    /**
     * Calculate completion rate for account projects
     */
    private function calculateCompletionRate($account): float
    {
        $totalProjects = Project::where('account_id', $account->id)->count();
        $completedProjects = Project::where('account_id', $account->id)
            ->where('status', 'completed')->count();
            
        return $totalProjects > 0 ? ($completedProjects / $totalProjects * 100) : 0;
    }
    
    /**
     * Calculate next invoice date (placeholder)
     */
    private function calculateNextInvoiceDate($account): ?Carbon
    {
        // Placeholder - would calculate based on billing cycle
        return Carbon::now()->addMonth()->startOfMonth();
    }
}
