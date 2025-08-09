# Widget System Architecture

Service Vault implements a comprehensive widget-based dashboard architecture with auto-discovery, permission filtering, and real-time data integration.

## Overview

### Widget System Goals
- **Permission-Driven UI**: Widgets only display for users with required permissions
- **Auto-Discovery**: Automatic registration of widget components from filesystem
- **Context Awareness**: Widgets adapt to service provider vs account user contexts
- **Real-Time Data**: Dynamic data loading with live updates
- **Production Performance**: Optimized caching and efficient data provisioning

### Technology Stack
- **Backend**: Laravel 12 with WidgetRegistryService
- **Frontend**: Vue.js 3.5 with dynamic component loading
- **Data Layer**: PostgreSQL + Redis for widget data caching
- **Authentication**: ABAC permission integration for widget filtering

## Widget Registry Service

### Core Architecture
The `WidgetRegistryService` manages both static widget definitions and auto-discovered widgets:

```php
class WidgetRegistryService
{
    // Static widget registry with 14+ pre-configured widgets
    private const WIDGET_REGISTRY = [
        'system-health' => [
            'name' => 'System Health',
            'component' => 'SystemHealthWidget',
            'permissions' => ['system.manage'],
            'context' => 'service_provider',
            'default_size' => ['w' => 4, 'h' => 2],
            'enabled_by_default' => true,
        ],
        // ... 13 more widgets
    ];
    
    // Auto-discovery from filesystem
    protected const WIDGET_DIRECTORIES = [
        'resources/js/Components/Widgets',
        'resources/js/Components/Dashboard/Widgets',
    ];
}
```

### Widget Categories
Widgets are organized into logical categories:

**Administration Widgets:**
- `system-health` - System status monitoring
- `system-stats` - User, account, timer statistics
- `user-management` - User administration shortcuts
- `account-management` - Customer account management
- `all-timers` - Admin monitoring of all active timers

**Service Delivery Widgets:**
- `ticket-overview` - Service tickets across accounts
- `my-tickets` - Assigned tickets for current user
- `account-activity` - Recent activity per account

**Time Management Widgets:**
- `time-tracking` - Active timer management
- `time-entries` - Recent time entries and approvals

**Financial Widgets:**
- `billing-overview` - Financial overview per account

**Analytics Widgets:**
- `team-performance` - Team productivity metrics

**Productivity Widgets:**
- `quick-actions` - Common actions based on role
- `account-users` - Account user management

## Permission System Integration

### Permission-Based Filtering
Each widget defines required permissions for access:

```php
// Example widget permission configurations
'system-health' => [
    'permissions' => ['system.manage'],      // Admin only
    'context' => 'service_provider'
],
'ticket-overview' => [
    'permissions' => ['tickets.view.all', 'tickets.view.account'],
    'context' => 'both'                     // Both contexts
],
'quick-actions' => [
    'permissions' => [],                    // Always available
    'context' => 'both'
],
```

### User Context Determination
The service determines user context based on permissions:

```php
private function determineUserContext(User $user): string
{
    // Service provider permissions
    if ($user->hasAnyPermission(['admin.manage', 'system.manage', 'users.manage'])) {
        return 'service_provider';
    }
    
    // Service delivery permissions
    if ($user->hasAnyPermission(['time.track', 'tickets.assign', 'teams.manage'])) {
        return 'service_provider';
    }
    
    // Default to account user
    return 'account_user';
}
```

## Auto-Discovery System

### Discovery Algorithm
The widget registry automatically discovers widget components:

```php
protected function discoverWidgets(): void
{
    foreach (self::WIDGET_DIRECTORIES as $directory) {
        $files = File::allFiles(base_path($directory));
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'vue' && 
                str_ends_with($file->getFilename(), 'Widget.vue')) {
                
                $widgetConfig = $this->extractWidgetConfig($file->getPathname());
                if ($widgetConfig) {
                    $this->discoveredWidgets[$widgetConfig['id']] = $widgetConfig;
                }
            }
        }
    }
}
```

### Widget Configuration Extraction
Widgets can define configuration via JavaScript exports:

```javascript
// Example widget configuration in Vue component
export const widgetConfig = {
    name: 'Custom Timer Widget',
    description: 'Advanced timer statistics and controls',
    category: 'time_management',
    permissions: ['time.track', 'timers.manage.own'],
    context: 'both',
    default_size: { w: 6, h: 4 },
    configurable: true,
    enabled_by_default: false
};
```

### Production Caching
Widget discovery is cached in production for performance:

```php
// Cache discovered widgets for 1 hour in production
if (config('app.env') === 'production') {
    $cached = Cache::get(self::CACHE_KEY);
    if ($cached !== null) {
        $this->discoveredWidgets = $cached;
        return;
    }
}

// ... discovery logic ...

if (config('app.env') === 'production') {
    Cache::put(self::CACHE_KEY, $discovered, self::CACHE_TTL);
}
```

## Dynamic Dashboard Controller

### Dashboard Data Provisioning
The `DynamicDashboardController` serves widget data based on user permissions:

```php
public function index(Request $request): Response
{
    $user = $request->user();
    
    // Get available widgets for this user
    $availableWidgets = $this->widgetRegistry->getAvailableWidgets($user);
    
    // Get account context for service provider users
    $accountContext = $this->getAccountContext($user, $request->get('account'));
    
    // Get widget data based on available widgets
    $widgetData = $this->getWidgetData($user, $availableWidgets, $accountContext);
    
    return Inertia::render('Dashboard/Index', [
        'availableWidgets' => $availableWidgets,
        'widgetData' => $widgetData,
        'accountContext' => $accountContext,
    ]);
}
```

### Widget Data Methods
Each widget has a dedicated data provisioning method:

```php
private function getWidgetData(User $user, array $availableWidgets, array $accountContext): array
{
    $data = [];
    
    foreach ($availableWidgets as $widget) {
        switch ($widget['id']) {
            case 'system-health':
                $data[$widget['id']] = $this->getSystemHealthData();
                break;
            case 'system-stats':
                $data[$widget['id']] = $this->getSystemStatsData();
                break;
            case 'ticket-overview':
                $data[$widget['id']] = $this->getTicketOverviewData($user, $selectedAccount);
                break;
            // ... additional widget data methods
        }
    }
    
    return $data;
}
```

## Frontend Architecture

### Widget Loader Component
The `WidgetLoader.vue` component handles dynamic widget rendering:

```vue
<template>
  <div class="widget-loader">
    <!-- Loading State -->
    <div v-if="isLoading" class="widget-loading">
      <div class="animate-pulse">...</div>
    </div>

    <!-- Widget Component -->
    <component
      v-else-if="widgetComponent && !hasError"
      :is="widgetComponent"
      v-bind="widgetProps"
      @refresh="$emit('refresh')"
      @configure="$emit('configure')"
    />

    <!-- Error State -->
    <div v-else-if="hasError" class="widget-error">
      <p class="text-sm text-red-600">Failed to load widget</p>
      <button @click="retryLoad">Retry</button>
    </div>
  </div>
</template>

<script setup>
// Dynamic component loading with error handling
const widgetComponents = {
  'SystemHealthWidget': () => import('@/Components/Widgets/SystemHealthWidget.vue'),
  'SystemStatsWidget': () => import('@/Components/Widgets/SystemStatsWidget.vue'),
  'QuickActionsWidget': () => import('@/Components/Widgets/QuickActionsWidget.vue'),
  'UserManagementWidget': () => import('@/Components/Widgets/UserManagementWidget.vue'),
  'AccountManagementWidget': () => import('@/Components/Widgets/AccountManagementWidget.vue'),
  'TicketOverviewWidget': () => import('@/Components/Widgets/TicketOverviewWidget.vue'),
  'TimeTrackingWidget': () => import('@/Components/Widgets/TimeTrackingWidget.vue'),
  'MyTicketsWidget': () => import('@/Components/Widgets/MyTicketsWidget.vue'),
  'TimeEntriesWidget': () => import('@/Components/Widgets/TimeEntriesWidget.vue'),
  'AllTimersWidget': () => import('@/Components/Widgets/AllTimersWidget.vue'),
  'BillingOverviewWidget': () => import('@/Components/Widgets/BillingOverviewWidget.vue'),
  'AccountActivityWidget': () => import('@/Components/Widgets/AccountActivityWidget.vue'),
};

const loadWidget = async () => {
  try {
    const componentLoader = widgetComponents[props.widget.component];
    const component = await componentLoader();
    widgetComponent.value = component.default || component;
  } catch (error) {
    hasError.value = true;
    errorMessage.value = error.message;
  }
};
</script>
```

### Dashboard Layout System
The dashboard uses a responsive grid system:

```vue
<template>
  <div class="widget-grid">
    <div
      v-for="widget in availableWidgets"
      :key="widget.id"
      :class="getWidgetClasses(widget)"
      class="widget-container"
    >
      <!-- Widget Header -->
      <div class="widget-header">
        <h3>{{ widget.name }}</h3>
        <div class="widget-actions">
          <button v-if="widget.configurable" @click="configureWidget(widget)">
            Configure
          </button>
        </div>
      </div>

      <!-- Widget Content -->
      <div class="widget-content">
        <WidgetLoader
          :widget="widget"
          :widget-data="widgetData[widget.id]"
          :account-context="accountContext"
        />
      </div>
    </div>
  </div>
</template>

<style scoped>
.widget-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
  gap: 1.5rem;
}

@media (min-width: 768px) {
  .widget-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>
```

## Widget Component Library

### Standard Widget Structure
All widgets follow a consistent structure:

```vue
<template>
  <div class="widget">
    <!-- Widget content based on widgetData prop -->
    <div v-if="widgetData" class="widget-body">
      <!-- Widget-specific content -->
    </div>
    
    <!-- Loading state -->
    <div v-else class="widget-loading">Loading...</div>
  </div>
</template>

<script setup>
// Standard widget props
const props = defineProps({
  widgetData: {
    type: [Object, Array],
    default: null
  },
  widgetConfig: {
    type: Object,
    required: true
  },
  accountContext: {
    type: Object,
    default: () => ({})
  }
});

// Widget configuration export
export const widgetConfig = {
  name: 'Widget Name',
  description: 'Widget description',
  category: 'category_name',
  permissions: ['required.permission'],
  context: 'both', // 'service_provider', 'account_user', 'both'
  default_size: { w: 4, h: 3 },
  configurable: true,
  enabled_by_default: true
};
</script>
```

### Example Widget Implementation
```vue
<!-- SystemHealthWidget.vue -->
<template>
  <div class="system-health-widget">
    <div class="grid grid-cols-2 gap-3">
      <div class="health-item">
        <span class="text-sm text-gray-600">Database</span>
        <span :class="getHealthBadgeClass(widgetData?.database)">
          {{ widgetData?.database || 'unknown' }}
        </span>
      </div>
      
      <div class="health-item">
        <span class="text-sm text-gray-600">Redis</span>
        <span :class="getHealthBadgeClass(widgetData?.redis)">
          {{ widgetData?.redis || 'unknown' }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
// Widget configuration
export const widgetConfig = {
  name: 'System Health',
  description: 'Monitor system status, database, Redis, and queue health',
  category: 'administration',
  permissions: ['system.manage'],
  context: 'service_provider',
  default_size: { w: 4, h: 2 },
  configurable: true,
  enabled_by_default: true
};

const getHealthBadgeClass = (status) => {
  return {
    'bg-green-100 text-green-800': status === 'healthy',
    'bg-red-100 text-red-800': status === 'error',
    'bg-yellow-100 text-yellow-800': status === 'warning',
    'bg-gray-100 text-gray-800': !status || status === 'unknown'
  };
};
</script>
```

## Account Context System

### Context Switching
Service provider users can switch between customer accounts:

```php
private function getAccountContext(User $user, ?string $selectedAccountId): array
{
    $context = [
        'userType' => $this->determineUserType($user),
        'selectedAccount' => null,
        'availableAccounts' => [],
        'canSwitchAccounts' => false,
    ];

    if ($context['userType'] === 'service_provider') {
        $availableAccounts = $this->getAvailableAccounts($user);
        $context['availableAccounts'] = $availableAccounts->toArray();
        $context['canSwitchAccounts'] = count($availableAccounts) > 1;

        if ($selectedAccountId && $availableAccounts->contains('id', (int) $selectedAccountId)) {
            $context['selectedAccount'] = $availableAccounts
                ->firstWhere('id', (int) $selectedAccountId)->toArray();
        }
    } else {
        // Account user - only see their own account
        $userAccount = $user->accounts()->first();
        if ($userAccount) {
            $context['selectedAccount'] = $userAccount->toArray();
        }
    }

    return $context;
}
```

### Account-Aware Widget Data
Widgets receive account context for data filtering:

```php
private function getTicketOverviewData(User $user, ?Account $account): array
{
    $query = ServiceTicket::with(['account', 'assignedUser', 'createdBy']);

    // Filter by account if specified
    if ($account) {
        $query->where('account_id', $account->id);
    } elseif (!$user->isSuperAdmin()) {
        // Non-admin users see only tickets from accounts they can access
        $accountIds = $user->accounts()->pluck('accounts.id');
        $query->whereIn('account_id', $accountIds);
    }

    return [
        'recent_tickets' => $query->orderBy('created_at', 'desc')->take(10)->get(),
        'stats' => [
            'total' => $query->count(),
            'open' => $query->where('status', 'open')->count(),
            'in_progress' => $query->where('status', 'in_progress')->count(),
        ]
    ];
}
```

## Performance Optimization

### Widget Data Caching
Widget data can be cached for performance:

```php
// Cache frequently accessed widget data
public function getCachedWidgetData(string $widgetId, User $user): mixed
{
    $cacheKey = "widget_data.{$widgetId}.user.{$user->id}";
    
    return Cache::remember($cacheKey, 300, function () use ($widgetId, $user) {
        return $this->getWidgetData($user, [$this->getWidget($widgetId)], []);
    });
}
```

### Database Query Optimization
Widget queries are optimized with proper indexing:

```sql
-- Indexes for widget performance
CREATE INDEX CONCURRENTLY idx_timers_widget_active 
ON timers(status, user_id) 
WHERE status IN ('running', 'paused') AND deleted_at IS NULL;

CREATE INDEX CONCURRENTLY idx_tickets_widget_overview 
ON service_tickets(account_id, status, created_at) 
WHERE deleted_at IS NULL;

CREATE INDEX CONCURRENTLY idx_users_widget_stats 
ON users(created_at, updated_at) 
WHERE deleted_at IS NULL;
```

### Frontend Performance
- **Lazy Loading**: Widget components loaded only when needed
- **Error Boundaries**: Isolated error handling per widget
- **Efficient Rendering**: Minimal re-renders with Vue.js reactivity

## Security Considerations

### Permission Enforcement
Every widget enforces permissions at multiple levels:

1. **Registry Level**: Widgets filtered based on user permissions
2. **Data Level**: Widget data respects user access controls
3. **Component Level**: Frontend components validate permissions

### Account Isolation
Account-aware widgets ensure data isolation:

```php
// Account-scoped data access
private function getAccountUsersData(User $user, ?Account $account): array
{
    if (!$account) {
        return ['users' => [], 'count' => 0];
    }

    // Only return users from the specified account
    $users = $account->users()
        ->when(!$user->isSuperAdmin(), function ($query) use ($user) {
            // Non-admin users can only see accounts they belong to
            $query->whereIn('account_id', $user->accounts()->pluck('accounts.id'));
        })
        ->take(10)
        ->get();

    return [
        'users' => $users,
        'count' => $account->users()->count(),
    ];
}
```

## Future Extensions

### Phase 12C: Ticket-Specific Widgets
Planned widgets for enhanced ticket management:

- **TicketTimerStatsWidget**: Active timers summary for current user
- **TicketFiltersWidget**: Advanced filtering with saved views
- **RecentTimeEntriesWidget**: Latest time entries across tickets
- **TicketAssignmentWidget**: Bulk operations for managers
- **TicketBillingSummaryWidget**: Cost tracking per account

### Phase 12D: Universal Widget System
Extending widgets beyond the dashboard:

- **Page-Level Widget Framework**: Widget support on any page
- **Widget Areas**: Reusable widget containers (sidebar, footer, etc.)
- **Layout Persistence**: Save custom widget arrangements per page
- **Enhanced Registry**: Page-specific widget registration and filtering

## Monitoring & Analytics

### Widget Performance Metrics
```php
// Widget performance monitoring
class WidgetPerformanceMonitor
{
    public function recordWidgetLoad(string $widgetId, float $loadTime): void
    {
        $this->metrics->timing("widget.{$widgetId}.load_time", $loadTime);
        $this->metrics->increment("widget.{$widgetId}.loads");
    }
    
    public function recordWidgetError(string $widgetId, string $error): void
    {
        $this->metrics->increment("widget.{$widgetId}.errors");
        Log::warning("Widget error", [
            'widget_id' => $widgetId,
            'error' => $error
        ]);
    }
}
```

### Usage Analytics
- **Widget Usage**: Track which widgets are most used
- **Permission Patterns**: Analyze permission-based widget access
- **Performance Metrics**: Monitor widget load times and errors
- **User Engagement**: Track widget interactions and configurations

## Conclusion

The Service Vault widget system provides a flexible, permission-driven dashboard architecture that scales from individual user needs to enterprise-level customization. With auto-discovery, comprehensive caching, and account-aware data provisioning, it forms the foundation for a truly dynamic and personalized user experience.

The system's modular design allows for easy extension and customization while maintaining security and performance standards. The upcoming universal widget deployment will extend these capabilities across the entire application, creating a consistent and powerful user interface framework.