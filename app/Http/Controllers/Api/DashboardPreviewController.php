<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RoleTemplate;
use App\Services\WidgetRegistryService;
use App\Services\NavigationService;
use App\Services\MockUserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DashboardPreviewController extends Controller
{
    public function __construct(
        private WidgetRegistryService $widgetService,
        private NavigationService $navigationService,
        private MockUserService $mockUserService
    ) {}

    /**
     * Generate complete dashboard preview for a role template
     */
    public function previewDashboard(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $context = $request->input('context', $roleTemplate->context);
        
        // Create mock user with this role template
        $mockUser = $this->mockUserService->createMockUser($roleTemplate, $context);
        
        // Get widgets available to this role
        $availableWidgets = $this->widgetService->getWidgetsForRolePreview($roleTemplate, $context);
        
        // Get default dashboard layout
        $dashboardLayout = $this->widgetService->getDefaultLayoutForRole($roleTemplate);
        
        // Get mock widget data
        $widgetData = $this->generateMockWidgetData($availableWidgets, $roleTemplate, $context);
        
        // Get navigation items for this role
        $navigation = $this->navigationService->getNavigationForUser($mockUser);
        
        return response()->json([
            'data' => [
                'role_template' => [
                    'id' => $roleTemplate->id,
                    'name' => $roleTemplate->name,
                    'context' => $context,
                ],
                'mock_user' => [
                    'name' => $this->mockUserService->getMockUserName($roleTemplate),
                    'context' => $context,
                    'permissions' => $roleTemplate->getAllPermissions(),
                ],
                'dashboard' => [
                    'available_widgets' => $availableWidgets,
                    'layout' => $dashboardLayout,
                    'widget_data' => $widgetData,
                    'navigation' => $navigation,
                    'title' => $this->generateDashboardTitle($roleTemplate, $context),
                ],
                'stats' => [
                    'total_widgets' => count($availableWidgets),
                    'functional_permissions' => count($roleTemplate->getAllPermissions()),
                    'widget_permissions' => count($roleTemplate->getAllWidgetPermissions()),
                    'page_permissions' => count($roleTemplate->getAllPagePermissions()),
                    'navigation_items' => count($navigation),
                ],
            ],
        ]);
    }

    /**
     * Get widget preview for a role template
     */
    public function previewWidgets(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $context = $request->input('context', $roleTemplate->context);
        
        $availableWidgets = $this->widgetService->getWidgetsForRolePreview($roleTemplate, $context);
        $mockWidgetData = $this->generateMockWidgetData($availableWidgets, $roleTemplate, $context);
        
        return response()->json([
            'data' => [
                'widgets' => $availableWidgets,
                'widget_data' => $mockWidgetData,
                'categories' => collect($availableWidgets)->groupBy('category')->keys(),
                'context' => $context,
            ],
        ]);
    }

    /**
     * Get navigation preview for a role template
     */
    public function previewNavigation(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $context = $request->input('context', $roleTemplate->context);
        
        // Create mock user with this role template
        $mockUser = $this->mockUserService->createMockUser($roleTemplate, $context);
        
        // Get navigation items for this role
        $navigation = $this->navigationService->getNavigationForUser($mockUser);
        
        // Get grouped navigation for better organization
        $groupedNavigation = $this->navigationService->getGroupedNavigationForUser($mockUser);
        
        return response()->json([
            'data' => [
                'navigation' => $navigation,
                'grouped_navigation' => $groupedNavigation,
                'group_labels' => $this->navigationService->getGroupLabels(),
                'context' => $context,
                'stats' => [
                    'total_items' => count($navigation),
                    'groups' => count($groupedNavigation),
                ],
            ],
        ]);
    }

    /**
     * Get dashboard layout preview for a role template
     */
    public function previewLayout(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $context = $request->input('context', $roleTemplate->context);
        
        $layout = $this->widgetService->getDefaultLayoutForRole($roleTemplate);
        $availableWidgets = $this->widgetService->getWidgetsForRolePreview($roleTemplate, $context);
        
        return response()->json([
            'data' => [
                'layout' => $layout,
                'widgets_count' => count($availableWidgets),
                'grid_settings' => [
                    'max_cols' => 12,
                    'row_height' => 100,
                    'margin' => [10, 10],
                ],
                'context' => $context,
            ],
        ]);
    }

    /**
     * Generate mock widget data for preview
     */
    private function generateMockWidgetData(array $widgets, RoleTemplate $roleTemplate, string $context): array
    {
        $data = [];
        
        foreach ($widgets as $widget) {
            $widgetId = $widget['id'];
            $data[$widgetId] = $this->getMockDataForWidget($widgetId, $roleTemplate, $context);
        }
        
        return $data;
    }

    /**
     * Get mock data for specific widget
     */
    private function getMockDataForWidget(string $widgetId, RoleTemplate $roleTemplate, string $context): array
    {
        return match ($widgetId) {
            'system-health' => [
                'database' => 'healthy',
                'redis' => 'healthy',
                'storage_disk' => 'healthy',
                'queue_jobs' => 12,
                'failed_jobs' => 0,
                '_preview_mode' => true,
            ],
            'system-stats' => [
                'total_users' => 156,
                'total_accounts' => 42,
                'active_timers' => 8,
                'total_time_entries' => 2847,
                'users_this_month' => 23,
                'time_tracked_today' => 25200, // 7 hours in seconds
                'pending_approvals' => 5,
                'domain_mappings' => 3,
                '_preview_mode' => true,
            ],
            'ticket-overview' => [
                'recent_tickets' => $this->generateMockTickets($context),
                'stats' => [
                    'total' => 89,
                    'open' => 23,
                    'in_progress' => 15,
                    'resolved' => 51,
                ],
                '_preview_mode' => true,
            ],
            'my-tickets' => [
                'tickets' => $this->generateMockTickets($context, 5),
                'count' => 12,
                '_preview_mode' => true,
            ],
            'time-tracking' => [
                'active_timers' => $this->generateMockTimers(),
                'total_duration_today' => 18000, // 5 hours in seconds
                '_preview_mode' => true,
            ],
            'billing-overview' => [
                'total_billable_time' => 86400, // 24 hours in seconds
                'total_amount' => 4250.00,
                'pending_invoices' => 3,
                '_preview_mode' => true,
            ],
            'account-activity' => [
                'recent_activity' => $this->generateMockActivity($context),
                '_preview_mode' => true,
            ],
            'user-management' => [
                'total_users' => 156,
                'recent_users' => $this->generateMockUsers(),
                'pending_invitations' => 7,
                '_preview_mode' => true,
            ],
            'account-management' => [
                'total_accounts' => 42,
                'recent_accounts' => $this->generateMockAccounts(),
                '_preview_mode' => true,
            ],
            'quick-actions' => [
                'actions' => $this->generateMockQuickActions($roleTemplate),
                '_preview_mode' => true,
            ],
            default => [
                '_preview_mode' => true,
                '_mock_data' => 'No specific mock data available for this widget',
            ],
        };
    }

    /**
     * Generate mock tickets data
     */
    private function generateMockTickets(string $context, int $count = 10): array
    {
        $tickets = [];
        $statuses = ['open', 'in_progress', 'pending', 'resolved'];
        $priorities = ['low', 'normal', 'high', 'urgent'];
        
        for ($i = 0; $i < $count; $i++) {
            $tickets[] = [
                'id' => 1000 + $i,
                'title' => "Sample Ticket #" . (1000 + $i),
                'description' => 'This is a mock ticket for preview purposes.',
                'status' => $statuses[array_rand($statuses)],
                'priority' => $priorities[array_rand($priorities)],
                'account' => ['id' => 1, 'name' => 'Sample Account'],
                'assigned_user' => ['id' => 1, 'name' => 'Sample User'],
                'created_at' => now()->subDays(rand(0, 30))->toISOString(),
            ];
        }
        
        return $tickets;
    }

    /**
     * Generate mock timers data
     */
    private function generateMockTimers(): array
    {
        return [
            [
                'id' => 1,
                'description' => 'Working on Sample Ticket #1001',
                'started_at' => now()->subHours(2)->toISOString(),
                'duration' => 7200, // 2 hours
                'ticket' => ['id' => 1001, 'title' => 'Sample Ticket #1001'],
                'is_active' => true,
            ],
            [
                'id' => 2,
                'description' => 'Code review for Sample Project',
                'started_at' => now()->subMinutes(45)->toISOString(),
                'duration' => 2700, // 45 minutes
                'project' => ['id' => 1, 'name' => 'Sample Project'],
                'is_active' => true,
            ],
        ];
    }

    /**
     * Generate other mock data methods
     */
    private function generateMockActivity(string $context): array
    {
        return [
            ['type' => 'ticket_created', 'description' => 'New ticket created: Sample Ticket #1005', 'time' => '2 minutes ago'],
            ['type' => 'user_logged_in', 'description' => 'John Doe logged in', 'time' => '15 minutes ago'],
            ['type' => 'timer_started', 'description' => 'Timer started on Sample Ticket #1002', 'time' => '1 hour ago'],
        ];
    }

    private function generateMockUsers(): array
    {
        return [
            ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com', 'created_at' => now()->subDays(2)->toISOString()],
            ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com', 'created_at' => now()->subDays(5)->toISOString()],
        ];
    }

    private function generateMockAccounts(): array
    {
        return [
            ['id' => 1, 'name' => 'Acme Corporation', 'created_at' => now()->subDays(10)->toISOString()],
            ['id' => 2, 'name' => 'Tech Solutions Inc', 'created_at' => now()->subDays(15)->toISOString()],
        ];
    }

    private function generateMockQuickActions(RoleTemplate $roleTemplate): array
    {
        $actions = [];
        $permissions = $roleTemplate->getAllPermissions();
        
        if (in_array('tickets.create', $permissions)) {
            $actions[] = ['name' => 'Create Ticket', 'route' => 'tickets.create', 'icon' => 'plus'];
        }
        
        if (in_array('time.track', $permissions)) {
            $actions[] = ['name' => 'Start Timer', 'action' => 'start-timer', 'icon' => 'play'];
        }
        
        if (in_array('users.manage', $permissions)) {
            $actions[] = ['name' => 'Invite User', 'route' => 'users.invite', 'icon' => 'user-plus'];
        }
        
        return $actions;
    }

    /**
     * Generate dashboard title for preview
     */
    private function generateDashboardTitle(RoleTemplate $roleTemplate, string $context): string
    {
        $contextName = $context === 'service_provider' ? 'Service Provider' : 'Account User';
        return "Dashboard Preview - {$roleTemplate->name} ({$contextName})";
    }
}
