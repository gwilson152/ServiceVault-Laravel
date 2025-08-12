<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Timer;
use App\Models\TimeEntry;
use App\Models\User;
use App\Models\Ticket;
use App\Models\DomainMapping;
use App\Services\WidgetRegistryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Inertia\Inertia;
use Inertia\Response;

class DynamicDashboardController extends Controller
{
    protected WidgetRegistryService $widgetRegistry;

    public function __construct(WidgetRegistryService $widgetRegistry)
    {
        $this->widgetRegistry = $widgetRegistry;
    }

    /**
     * Display the dynamic dashboard based on user permissions and context
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $selectedAccountId = $request->get('account');

        // Get available widgets for this user
        $availableWidgets = $this->widgetRegistry->getAvailableWidgets($user);

        // Get user's saved layout or default layout
        $dashboardLayout = $this->getUserDashboardLayout($user);

        // Get account context for service provider users
        $accountContext = $this->getAccountContext($user, $selectedAccountId);

        // Get widget data based on available widgets
        $widgetData = $this->getWidgetData($user, $availableWidgets, $accountContext);

        // Get dashboard metadata
        $dashboardMeta = $this->getDashboardMetadata($user, $accountContext);

        return Inertia::render('Dashboard/Index', [
            'title' => $dashboardMeta['title'],
            'availableWidgets' => $availableWidgets,
            'dashboardLayout' => $dashboardLayout,
            'widgetData' => $widgetData,
            'accountContext' => $accountContext,
            'dashboardMeta' => $dashboardMeta,
            'widgetCategories' => $this->widgetRegistry->getCategories(),
        ]);
    }

    /**
     * Get user's dashboard layout or return default
     */
    private function getUserDashboardLayout(User $user): array
    {
        // Try to get user's saved layout from database/cache
        $savedLayout = Cache::get("user.{$user->id}.dashboard_layout");
        
        if ($savedLayout) {
            return $savedLayout;
        }

        // Return default layout based on user's role
        return $this->widgetRegistry->getDefaultLayout($user);
    }

    /**
     * Get account context for service provider users
     */
    private function getAccountContext(User $user, ?string $selectedAccountId): array
    {
        $context = [
            'userType' => $this->determineUserType($user),
            'selectedAccount' => null,
            'availableAccounts' => [],
            'canSwitchAccounts' => false,
        ];

        // If user is a service provider staff member
        if ($context['userType'] === 'service_provider') {
            // Get accounts this user can access
            $availableAccounts = $this->getAvailableAccounts($user);
            $context['availableAccounts'] = $availableAccounts->toArray();
            $context['canSwitchAccounts'] = count($availableAccounts) > 1;

            // Set selected account
            if ($selectedAccountId && $availableAccounts->contains('id', (int) $selectedAccountId)) {
                $context['selectedAccount'] = $availableAccounts->firstWhere('id', (int) $selectedAccountId)->toArray();
            } elseif ($availableAccounts->count() === 1) {
                $context['selectedAccount'] = $availableAccounts->first()->toArray();
            }
        } else {
            // Account user - can only see their own account
            if ($user->account) {
                $context['selectedAccount'] = $user->account->toArray();
                $context['availableAccounts'] = [$user->account->toArray()];
            }
        }

        return $context;
    }

    /**
     * Get data for all widgets based on user permissions and account context
     */
    private function getWidgetData(User $user, array $availableWidgets, array $accountContext): array
    {
        $data = [];
        $selectedAccount = null;
        
        // Convert selectedAccount array back to model if needed
        if (isset($accountContext['selectedAccount']) && is_array($accountContext['selectedAccount'])) {
            $accountId = $accountContext['selectedAccount']['id'] ?? null;
            if ($accountId) {
                $selectedAccount = Account::find($accountId);
            }
        } elseif (isset($accountContext['selectedAccount']) && $accountContext['selectedAccount'] instanceof Account) {
            $selectedAccount = $accountContext['selectedAccount'];
        }

        foreach ($availableWidgets as $widget) {
            $widgetId = $widget['id'];
            
            // Get widget-specific data
            switch ($widgetId) {
                case 'system-health':
                    $data[$widgetId] = $this->getSystemHealthData();
                    break;
                
                case 'system-stats':
                    $data[$widgetId] = $this->getSystemStatsData();
                    break;

                case 'ticket-overview':
                    $data[$widgetId] = $this->getTicketOverviewData($user, $selectedAccount);
                    break;

                case 'my-tickets':
                    $data[$widgetId] = $this->getMyTicketsData($user, $selectedAccount);
                    break;

                case 'time-tracking':
                    $data[$widgetId] = $this->getTimeTrackingData($user);
                    break;

                case 'time-entries':
                    $data[$widgetId] = $this->getTimeEntriesData($user, $selectedAccount);
                    break;

                case 'billing-overview':
                    $data[$widgetId] = $this->getBillingOverviewData($user, $selectedAccount);
                    break;

                case 'team-performance':
                    $data[$widgetId] = $this->getTeamPerformanceData($user, $selectedAccount);
                    break;

                case 'account-activity':
                    $data[$widgetId] = $this->getAccountActivityData($user, $selectedAccount);
                    break;

                case 'account-users':
                    $data[$widgetId] = $this->getAccountUsersData($user, $selectedAccount);
                    break;

                case 'user-management':
                    $data[$widgetId] = $this->getUserManagementData();
                    break;

                case 'account-management':
                    $data[$widgetId] = $this->getAccountManagementData();
                    break;

                case 'quick-actions':
                    $data[$widgetId] = $this->getQuickActionsData($user);
                    break;

                default:
                    $data[$widgetId] = [];
            }
        }

        return $data;
    }

    /**
     * Get dashboard metadata
     */
    private function getDashboardMetadata(User $user, array $accountContext): array
    {
        $title = 'Dashboard';
        
        if ($user->isSuperAdmin()) {
            $title = 'System Administration';
        } elseif ($user->hasAnyPermission(['teams.manage', 'admin.manage'])) {
            $title = 'Management Dashboard';
        } elseif ($accountContext['userType'] === 'service_provider') {
            $title = 'Service Dashboard';
        } else {
            $title = 'Account Portal';
        }

        if ($accountContext['selectedAccount']) {
            $title .= ' - ' . $accountContext['selectedAccount']['name'];
        }

        return [
            'title' => $title,
            'userType' => $accountContext['userType'],
            'userName' => $user->name,
            'lastLogin' => isset($user->last_login_at) ? $user->last_login_at?->format('M j, Y g:i A') : null,
        ];
    }

    /**
     * Determine user type (service_provider or account_user)
     */
    private function determineUserType(User $user): string
    {
        // Check if user has service provider permissions
        if ($user->hasAnyPermission(['admin.manage', 'system.manage', 'users.manage', 'time.track', 'teams.manage'])) {
            return 'service_provider';
        }
        
        return 'account_user';
    }

    /**
     * Get accounts available to this service provider user
     */
    private function getAvailableAccounts(User $user)
    {
        if ($user->isSuperAdmin()) {
            // Super admin can see all accounts
            return Account::orderBy('name')->get();
        }

        // Get the account this user belongs to
        if ($user->account) {
            return collect([$user->account]);
        }

        return collect();
    }

    // Widget data methods

    private function getSystemHealthData(): array
    {
        try {
            $dbStatus = DB::connection()->getPdo() ? 'healthy' : 'error';
        } catch (\Exception $e) {
            $dbStatus = 'error';
        }
        
        try {
            \Illuminate\Support\Facades\Redis::ping();
            $redisStatus = 'healthy';
        } catch (\Exception $e) {
            $redisStatus = 'error';
        }
        
        return [
            'database' => $dbStatus,
            'redis' => $redisStatus,
            'storage_disk' => disk_free_space(storage_path()) > 1000000000 ? 'healthy' : 'warning',
            'queue_jobs' => DB::table('jobs')->count(),
            'failed_jobs' => DB::table('failed_jobs')->count()
        ];
    }

    private function getSystemStatsData(): array
    {
        $now = now();
        $lastMonth = $now->subMonth();

        return [
            'total_users' => User::count(),
            'total_accounts' => Account::count(),
            'active_timers' => Timer::where('status', 'running')->count(),
            'total_time_entries' => TimeEntry::count(),
            'users_this_month' => User::where('created_at', '>=', $lastMonth)->count(),
            'time_tracked_today' => TimeEntry::whereDate('started_at', $now->toDateString())->sum('duration'),
            'pending_approvals' => TimeEntry::where('status', 'pending')->count(),
            'domain_mappings' => DomainMapping::where('is_active', true)->count()
        ];
    }

    private function getTicketOverviewData(User $user, ?Account $account): array
    {
        $query = Ticket::with(['account', 'agent', 'customer', 'createdBy']);

        // Filter by account if specified
        if ($account) {
            $query->where('account_id', $account->id);
        } elseif (!$user->isSuperAdmin()) {
            // Non-admin users see only tickets from their account
            if ($user->account) {
                $query->where('account_id', $user->account->id);
            }
        }

        $tickets = $query->orderBy('created_at', 'desc')->take(10)->get();

        return [
            'recent_tickets' => $tickets,
            'stats' => [
                'total' => $query->count(),
                'open' => $query->where('status', 'open')->count(),
                'in_progress' => $query->where('status', 'in_progress')->count(),
                'resolved' => $query->where('status', 'resolved')->count(),
            ]
        ];
    }

    private function getMyTicketsData(User $user, ?Account $account): array
    {
        $query = Ticket::with(['account', 'agent', 'customer'])
            ->where('agent_id', $user->id);

        if ($account) {
            $query->where('account_id', $account->id);
        }

        $tickets = $query->orderBy('created_at', 'desc')->take(5)->get();

        return [
            'tickets' => $tickets,
            'count' => $query->count(),
        ];
    }

    private function getTimeTrackingData(User $user): array
    {
        $activeTimers = Timer::where('user_id', $user->id)
            ->where('status', 'running')
            ->with(['ticket', 'billingRate'])
            ->get();

        return [
            'active_timers' => $activeTimers,
            'total_duration_today' => $this->getTotalDurationToday($user),
        ];
    }

    private function getTimeEntriesData(User $user, ?Account $account): array
    {
        $query = TimeEntry::with(['user', 'ticket', 'billingRate'])
            ->where('user_id', $user->id);

        if ($account) {
            $query->whereHas('ticket', function ($q) use ($account) {
                $q->where('account_id', $account->id);
            });
        }

        $entries = $query->orderBy('created_at', 'desc')->take(10)->get();

        return [
            'recent_entries' => $entries,
            'pending_approval' => $query->where('status', 'pending')->count(),
        ];
    }

    private function getBillingOverviewData(User $user, ?Account $account): array
    {
        // Placeholder for billing data
        return [
            'total_billable_time' => 0,
            'total_amount' => 0,
            'pending_invoices' => 0,
        ];
    }

    private function getTeamPerformanceData(User $user, ?Account $account): array
    {
        // Placeholder for team performance metrics
        return [
            'team_productivity' => 0,
            'tickets_resolved' => 0,
            'average_resolution_time' => 0,
        ];
    }

    private function getAccountActivityData(User $user, ?Account $account): array
    {
        if (!$account) {
            return ['recent_activity' => []];
        }

        // Get recent activity for the account
        $recentTickets = Ticket::where('account_id', $account->id)
            ->with(['agent', 'customer', 'createdBy'])
            ->orderBy('updated_at', 'desc')
            ->take(5)
            ->get();

        return [
            'recent_activity' => $recentTickets,
        ];
    }

    private function getAccountUsersData(User $user, ?Account $account): array
    {
        if (!$account) {
            return ['users' => [], 'count' => 0];
        }

        $users = $account->users()->take(10)->get();

        return [
            'users' => $users,
            'count' => $account->users()->count(),
        ];
    }

    private function getUserManagementData(): array
    {
        return [
            'total_users' => User::count(),
            'recent_users' => User::orderBy('created_at', 'desc')->take(5)->get(),
            'pending_invitations' => 0, // TODO: implement invitations
        ];
    }

    private function getAccountManagementData(): array
    {
        return [
            'total_accounts' => Account::count(),
            'recent_accounts' => Account::orderBy('created_at', 'desc')->take(5)->get(),
        ];
    }

    private function getQuickActionsData(User $user): array
    {
        $actions = [];

        if ($user->hasPermission('tickets.create')) {
            $actions[] = ['name' => 'Create Ticket', 'route' => 'tickets.create', 'icon' => 'plus'];
        }

        if ($user->hasPermission('time.track')) {
            $actions[] = ['name' => 'Start Timer', 'action' => 'start-timer', 'icon' => 'play'];
        }

        if ($user->hasPermission('users.manage')) {
            $actions[] = ['name' => 'Invite User', 'route' => 'users.invite', 'icon' => 'user-plus'];
        }

        return ['actions' => $actions];
    }

    private function getTotalDurationToday(User $user): int
    {
        return TimeEntry::where('user_id', $user->id)
            ->whereDate('started_at', now()->toDateString())
            ->sum('duration');
    }
}