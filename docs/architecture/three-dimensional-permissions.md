# Three-Dimensional Permission System Architecture

Service Vault implements a sophisticated **Three-Dimensional Permission System** that provides comprehensive access control across functional operations, dashboard widgets, and page navigation. This architecture enables fine-grained permission management while maintaining usability and flexibility.

## System Architecture Overview

The permission system operates on three interconnected dimensions:

```
┌─────────────────────────────────────────────────────────┐
│                Three-Dimensional Permissions           │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐     │
│  │ Functional  │  │   Widget    │  │    Page     │     │
│  │Permissions  │  │Permissions  │  │Permissions  │     │
│  │             │  │             │  │             │     │
│  │• Features   │  │• Dashboard  │  │• Routes     │     │
│  │• Operations │  │• Components │  │• Navigation │     │
│  │• API Access │  │• Widgets    │  │• UI Access │     │
│  └─────────────┘  └─────────────┘  └─────────────┘     │
│        │                │                │              │
│        └────────────────┼────────────────┘              │
│                         │                               │
│         ┌───────────────▼───────────────┐               │
│         │        Role Templates         │               │
│         │    (Permission Blueprints)    │               │
│         └───────────────────────────────┘               │
└─────────────────────────────────────────────────────────┘
```

## Core Components

### 1. Role Template System

**Role Templates** serve as permission blueprints that define complete user capabilities:

```php
class RoleTemplate extends Model
{
    protected $fillable = [
        'name',
        'description',
        'context',                    // service_provider, account_user, both
        'permissions',               // Functional permissions array
        'widget_permissions',        // Widget access permissions array
        'page_permissions',         // Page/route permissions array
        'dashboard_layout',         // Default widget layout configuration
        'is_system_role',           // System-protected role flag
        'is_default',               // Default role for context flag
        'is_modifiable'             // Can be edited flag
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'widget_permissions' => 'array',
        'page_permissions' => 'array',
        'dashboard_layout' => 'array',
        'is_system_role' => 'boolean',
        'is_default' => 'boolean',
        'is_modifiable' => 'boolean',
    ];
}
```

### 2. Context Awareness

The system recognizes three operational contexts:

**Service Provider Context (`service_provider`):**
- Internal staff managing customer accounts
- Full system access capabilities
- Cross-account data access permissions
- Administrative and oversight functions

**Account User Context (`account_user`):**
- Customer organization staff
- Account-scoped data access
- Limited to assigned account hierarchy
- Customer-facing features only

**Universal Context (`both`):**
- Roles that work in either context
- Context-adaptive feature sets
- Dynamic permission inheritance

### 3. Permission Inheritance Model

```
Super Admin (System Role)
    ↓ [Inherits All Permissions]
Admin (Service Provider)
    ↓ [Hierarchical Inheritance]
Manager (Service Provider)
    ↓ [Scope Inheritance]
Employee (Service Provider)

Account Manager (Account User)
    ↓ [Account Hierarchy Inheritance]
Account User (Account User)
```

## Dimension 1: Functional Permissions

Traditional feature-based access control for system operations:

### Permission Categories

**Administrative:**
```php
'admin.manage'              // Complete administrative access
'system.configure'          // System-wide configuration
'users.manage'              // User account management
'accounts.manage'           // Customer account management
'roles.manage'              // Role template management
```

**Service Delivery:**
```php
'tickets.view.all'          // View all tickets across accounts
'tickets.view.account'      // View tickets for accessible accounts
'tickets.view.assigned'     // View assigned tickets only
'tickets.create'            // Create new service tickets
'tickets.assign'            // Assign tickets to team members
'tickets.manage'            // Complete ticket lifecycle management
```

**Time Management:**
```php
'time.track'                // Track time on service tickets
'time.approve'              // Approve time entries
'timers.create'             // Create new timers
'timers.manage.own'         // Manage personal timers
'timers.manage.team'        // Manage team member timers
'timers.view.all'           // View all system timers (admin)
```

**Billing & Financial:**
```php
'billing.view.account'      // View account billing information
'billing.manage'            // Manage billing and invoicing
'billing.reports'           // Generate billing reports
'rates.manage'              // Manage billing rates
```

### Permission Implementation

```php
// Permission checking in controllers
class TicketController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', ServiceTicket::class);
        
        // Additional permission-based filtering
        if ($request->user()->hasPermission('tickets.view.all')) {
            return $this->getAllTickets();
        } elseif ($request->user()->hasPermission('tickets.view.account')) {
            return $this->getAccountTickets();
        } else {
            return $this->getAssignedTickets();
        }
    }
}
```

## Dimension 2: Widget Permissions

Granular dashboard widget access control:

### Widget Permission Structure

**Widget-Specific Permissions:**
```php
'widgets.dashboard.system-health'       // System Health widget
'widgets.dashboard.system-stats'        // System Statistics widget
'widgets.dashboard.user-management'     // User Management widget
'widgets.dashboard.ticket-overview'     // Service Tickets Overview
'widgets.dashboard.my-tickets'          // My Tickets widget
'widgets.dashboard.time-tracking'       // Active Timers widget
'widgets.dashboard.billing-overview'    // Billing Overview widget
'widgets.dashboard.account-activity'    // Account Activity widget
'widgets.dashboard.quick-actions'       // Quick Actions widget
```

**Global Widget Permissions:**
```php
'widgets.configure'         // Configure widget settings
'dashboard.customize'       // Full dashboard customization
'widgets.manage'           // Widget assignment management
```

### Widget Registry Integration

```php
class WidgetRegistryService
{
    public function getAvailableWidgets(User $user, ?string $context = null): array
    {
        $userContext = $this->determineUserContext($user);
        $allWidgets = $this->getAllWidgets();
        
        return collect($allWidgets)
            ->filter(function ($widget) use ($user, $userContext) {
                // Context filtering
                if ($widget['context'] !== 'both' && $widget['context'] !== $userContext) {
                    return false;
                }
                
                // Widget permission checking
                if (!$this->userHasWidgetPermission($user, $widget['id'])) {
                    return false;
                }
                
                // Functional permission validation
                if (!empty($widget['permissions'])) {
                    return $user->hasAnyPermission($widget['permissions']);
                }
                
                return true;
            })
            ->values()
            ->toArray();
    }
    
    public function userHasWidgetPermission(User $user, ?string $widgetId): bool
    {
        $roleTemplate = $user->roles()->with('template')->first()?->template;
        
        if (!$roleTemplate) return false;
        
        $widgetPermissionKey = "widgets.dashboard.{$widgetId}";
        
        return $roleTemplate->hasWidgetPermission($widgetPermissionKey) ||
               $roleTemplate->hasWidgetPermission('widgets.configure') ||
               $roleTemplate->hasWidgetPermission('dashboard.customize');
    }
}
```

## Dimension 3: Page Permissions

Route and navigation-level access control:

### Page Permission Categories

**Administrative Pages:**
```php
'pages.admin.dashboard'     // Admin dashboard access
'pages.admin.users'         // User management pages
'pages.admin.accounts'      // Account management pages
'pages.admin.settings'      // System settings pages
'pages.admin.roles'         // Role management interface
```

**Service Delivery Pages:**
```php
'pages.tickets.index'       // Ticket listing page
'pages.tickets.create'      // Ticket creation page
'pages.tickets.show'        // Ticket detail view
'pages.tickets.manage'      // Ticket management interface
```

**Time Management Pages:**
```php
'pages.time.entries'        // Time entries management
'pages.timers.dashboard'    // Timer dashboard
'pages.reports.time'        // Time tracking reports
```

**Portal Pages:**
```php
'pages.portal.dashboard'    // Customer portal dashboard
'pages.portal.tickets'      // Customer ticket interface
'pages.portal.billing'      // Customer billing interface
```

### Navigation Service Integration

```php
class NavigationService
{
    public function getNavigationForUser(User $user): array
    {
        $navigationItems = $this->getAllNavigationItems();
        
        return collect($navigationItems)
            ->filter(function ($item) use ($user) {
                if (!isset($item['permission'])) return true;
                
                // Check page permission
                if (str_starts_with($item['permission'], 'pages.')) {
                    return $user->hasPagePermission($item['permission']);
                }
                
                // Check functional permission
                return $user->hasPermission($item['permission']);
            })
            ->values()
            ->toArray();
    }
}
```

## Database Schema

### Role Templates Table

```sql
CREATE TABLE role_templates (
    id BIGINT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    context VARCHAR(50) NOT NULL, -- service_provider, account_user, both
    permissions JSON,              -- Functional permissions array
    widget_permissions JSON,       -- Widget access permissions
    page_permissions JSON,         -- Page/route permissions
    dashboard_layout JSON,         -- Default widget layout
    is_system_role BOOLEAN DEFAULT FALSE,
    is_default BOOLEAN DEFAULT FALSE,
    is_modifiable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Role Template Widgets Table

```sql
CREATE TABLE role_template_widgets (
    id BIGINT PRIMARY KEY,
    role_template_id BIGINT REFERENCES role_templates(id),
    widget_id VARCHAR(255) NOT NULL,
    enabled BOOLEAN DEFAULT TRUE,
    enabled_by_default BOOLEAN DEFAULT FALSE,
    configurable BOOLEAN DEFAULT TRUE,
    display_order INTEGER DEFAULT 0,
    widget_config JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Widget Permissions Table

```sql
CREATE TABLE widget_permissions (
    widget_id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    context VARCHAR(50) DEFAULT 'both',
    permission_key VARCHAR(255) UNIQUE,
    enabled_by_default BOOLEAN DEFAULT FALSE,
    configurable BOOLEAN DEFAULT TRUE,
    default_config JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Permission Resolution Algorithm

### User Permission Check Flow

```php
class User extends Model
{
    public function hasPermission(string $permission): bool
    {
        // 1. Super Admin check (bypass all other checks)
        if ($this->isSuperAdmin()) {
            return true;
        }
        
        // 2. Get user's role template
        $roleTemplate = $this->roles()
            ->with('template')
            ->first()
            ?->template;
            
        if (!$roleTemplate) return false;
        
        // 3. Check functional permissions
        return in_array($permission, $roleTemplate->getAllPermissions());
    }
    
    public function hasWidgetPermission(string $widgetPermission): bool
    {
        if ($this->isSuperAdmin()) return true;
        
        $roleTemplate = $this->roles()
            ->with('template')
            ->first()
            ?->template;
            
        if (!$roleTemplate) return false;
        
        return $roleTemplate->hasWidgetPermission($widgetPermission);
    }
    
    public function hasPagePermission(string $pagePermission): bool
    {
        if ($this->isSuperAdmin()) return true;
        
        $roleTemplate = $this->roles()
            ->with('template')
            ->first()
            ?->template;
            
        if (!$roleTemplate) return false;
        
        return $roleTemplate->hasPagePermission($pagePermission);
    }
}
```

### Role Template Permission Methods

```php
class RoleTemplate extends Model
{
    public function getAllPermissions(): array
    {
        return $this->permissions ?? [];
    }
    
    public function getAllWidgetPermissions(): array  
    {
        return $this->widget_permissions ?? [];
    }
    
    public function getAllPagePermissions(): array
    {
        return $this->page_permissions ?? [];
    }
    
    public function hasWidgetPermission(string $permission): bool
    {
        return in_array($permission, $this->getAllWidgetPermissions());
    }
    
    public function hasPagePermission(string $permission): bool
    {
        return in_array($permission, $this->getAllPagePermissions());
    }
    
    public function isModifiable(): bool
    {
        return $this->is_modifiable && !$this->is_system_role;
    }
}
```

## Security Features

### Authorization Policies

```php
class RoleTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.read');
    }
    
    public function view(User $user, RoleTemplate $roleTemplate): bool
    {
        return $user->hasPermission('admin.read');
    }
    
    public function create(User $user): bool
    {
        return $user->hasPermission('admin.write');
    }
    
    public function update(User $user, RoleTemplate $roleTemplate): bool
    {
        return $user->hasPermission('admin.write') && $roleTemplate->isModifiable();
    }
    
    public function delete(User $user, RoleTemplate $roleTemplate): bool
    {
        return $user->hasPermission('admin.write') && 
               $roleTemplate->isModifiable() &&
               $roleTemplate->roles()->count() === 0;
    }
}
```

### Middleware Integration

```php
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!$request->user()?->hasPermission($permission)) {
            throw new UnauthorizedException();
        }
        
        return $next($request);
    }
}

// Route usage
Route::middleware(['auth', 'check_permission:admin.manage'])
    ->group(function () {
        Route::apiResource('role-templates', RoleTemplateController::class);
    });
```

## Performance Optimizations

### Caching Strategy

```php
class PermissionService
{
    public function getUserPermissions(User $user): array
    {
        return Cache::tags(['permissions', "user:{$user->id}"])
            ->remember("user_permissions:{$user->id}", 3600, function () use ($user) {
                return [
                    'functional' => $user->getAllPermissions(),
                    'widgets' => $user->getAllWidgetPermissions(),
                    'pages' => $user->getAllPagePermissions(),
                ];
            });
    }
    
    public function clearUserPermissions(User $user): void
    {
        Cache::tags("user:{$user->id}")->flush();
    }
}
```

### Eager Loading Strategies

```php
// Efficient permission loading
$user = User::with(['roles.template'])->find($userId);

// Bulk permission checking
$users = User::with(['roles.template'])
    ->whereHas('roles.template', function ($query) {
        $query->whereJsonContains('permissions', 'admin.manage');
    })
    ->get();
```

## Dashboard Preview System

### Mock Data Generation

```php
class MockUserService
{
    public function createMockUser(RoleTemplate $roleTemplate, string $context): User
    {
        $mockUser = new User([
            'name' => $this->getMockUserName($roleTemplate),
            'email' => $this->getMockUserEmail($roleTemplate),
        ]);
        
        $mockUser->id = -1;
        $mockUser->exists = true;
        
        $mockRole = $this->createMockRole($roleTemplate, $context);
        $mockUser->setRelation('roles', collect([$mockRole]));
        
        return $mockUser;
    }
    
    public function generateMockWidgetData(array $widgets, RoleTemplate $roleTemplate): array
    {
        $data = [];
        
        foreach ($widgets as $widget) {
            $data[$widget['id']] = $this->getMockDataForWidget(
                $widget['id'], 
                $roleTemplate
            );
        }
        
        return $data;
    }
}
```

### Preview API Implementation

```php
class DashboardPreviewController extends Controller
{
    public function previewDashboard(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $context = $request->input('context', $roleTemplate->context);
        $mockUser = $this->mockUserService->createMockUser($roleTemplate, $context);
        
        // Generate complete preview data
        $availableWidgets = $this->widgetService->getWidgetsForRolePreview($roleTemplate, $context);
        $dashboardLayout = $this->widgetService->getDefaultLayoutForRole($roleTemplate);
        $widgetData = $this->generateMockWidgetData($availableWidgets, $roleTemplate, $context);
        $navigation = $this->navigationService->getNavigationForUser($mockUser);
        
        return response()->json([
            'data' => [
                'role_template' => ['id' => $roleTemplate->id, 'name' => $roleTemplate->name],
                'mock_user' => ['name' => $mockUser->name, 'context' => $context],
                'dashboard' => [
                    'available_widgets' => $availableWidgets,
                    'layout' => $dashboardLayout,
                    'widget_data' => $widgetData,
                    'navigation' => $navigation,
                    'title' => $this->generateDashboardTitle($roleTemplate, $context),
                ]
            ]
        ]);
    }
}
```

## Widget Assignment Interface

### Drag & Drop Implementation

The widget assignment interface provides advanced drag & drop functionality:

```vue
<template>
  <div class="widget-assignment-modal">
    <!-- Available Widgets Panel -->
    <div class="available-widgets">
      <div
        v-for="widget in availableWidgets"
        :key="widget.id"
        :draggable="true"
        @dragstart="startDrag(widget, 'available')"
        class="widget-item draggable"
      >
        {{ widget.name }}
      </div>
    </div>
    
    <!-- Assigned Widgets Panel -->
    <div
      class="assigned-widgets"
      @drop="handleDrop($event, 'assigned')"
      @dragover.prevent
      @dragenter.prevent
    >
      <div
        v-for="(widget, index) in assignedWidgets"
        :key="widget.id"
        :draggable="true"
        @dragstart="startDrag(widget, 'assigned', index)"
      >
        <widget-configuration :widget="widget" @update="updateWidget" />
      </div>
    </div>
    
    <!-- Layout Designer -->
    <div class="layout-designer">
      <grid-layout
        :layout="dashboardLayout"
        :col-num="12"
        :row-height="60"
        :is-draggable="true"
        :is-resizable="true"
        @layout-updated="onLayoutUpdate"
      >
        <grid-item
          v-for="item in dashboardLayout"
          :key="item.i"
          :x="item.x"
          :y="item.y"
          :w="item.w"
          :h="item.h"
          :i="item.i"
        >
          <widget-preview :widget-id="item.i" />
        </grid-item>
      </grid-layout>
    </div>
  </div>
</template>
```

### Assignment API Integration

```php
class RoleTemplateController extends Controller
{
    public function updateWidgets(RoleTemplate $roleTemplate, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'widgets' => 'required|array',
            'widgets.*.widget_id' => 'required|string',
            'widgets.*.enabled' => 'boolean',
            'widgets.*.enabled_by_default' => 'boolean',
            'layout' => 'array',
            'layout.*.i' => 'required|string',
            'layout.*.x' => 'required|integer|min:0|max:11',
            'layout.*.y' => 'required|integer|min:0',
            'layout.*.w' => 'required|integer|min:1|max:12',
            'layout.*.h' => 'required|integer|min:1|max:10',
        ]);
        
        DB::beginTransaction();
        
        try {
            // Update widget assignments
            $roleTemplate->widgets()->delete();
            
            foreach ($validated['widgets'] as $index => $widgetData) {
                $roleTemplate->widgets()->create([
                    'widget_id' => $widgetData['widget_id'],
                    'enabled' => $widgetData['enabled'] ?? true,
                    'enabled_by_default' => $widgetData['enabled_by_default'] ?? false,
                    'display_order' => $index + 1,
                    'widget_config' => $widgetData['widget_config'] ?? [],
                ]);
            }
            
            // Update widget permissions and layout
            $widgetPermissions = collect($validated['widgets'])
                ->pluck('widget_id')
                ->map(fn($id) => "widgets.dashboard.{$id}")
                ->toArray();
                
            $roleTemplate->update([
                'widget_permissions' => array_merge(
                    $this->getGlobalWidgetPermissions($roleTemplate),
                    $widgetPermissions
                ),
                'dashboard_layout' => $validated['layout'] ?? []
            ]);
            
            DB::commit();
            
            return response()->json([
                'message' => 'Widget assignments updated successfully',
                'data' => [
                    'widgets_count' => count($validated['widgets']),
                    'layout_items' => count($validated['layout'] ?? [])
                ]
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

## Testing Strategy

### Unit Tests

```php
class ThreeDimensionalPermissionTest extends TestCase
{
    public function test_user_has_functional_permission()
    {
        $user = User::factory()->create();
        $roleTemplate = RoleTemplate::factory()->create([
            'permissions' => ['tickets.create', 'time.track']
        ]);
        
        $user->roles()->create(['role_template_id' => $roleTemplate->id]);
        
        $this->assertTrue($user->hasPermission('tickets.create'));
        $this->assertFalse($user->hasPermission('admin.manage'));
    }
    
    public function test_user_has_widget_permission()
    {
        $user = User::factory()->create();
        $roleTemplate = RoleTemplate::factory()->create([
            'widget_permissions' => ['widgets.dashboard.system-health']
        ]);
        
        $user->roles()->create(['role_template_id' => $roleTemplate->id]);
        
        $this->assertTrue($user->hasWidgetPermission('widgets.dashboard.system-health'));
        $this->assertFalse($user->hasWidgetPermission('widgets.dashboard.admin-only'));
    }
    
    public function test_super_admin_has_all_permissions()
    {
        $superAdmin = User::factory()->create(['email' => 'admin@servicevault.local']);
        
        $this->assertTrue($superAdmin->hasPermission('any.permission'));
        $this->assertTrue($superAdmin->hasWidgetPermission('any.widget'));
        $this->assertTrue($superAdmin->hasPagePermission('any.page'));
    }
}
```

### Feature Tests

```php
class DashboardPreviewTest extends TestCase
{
    public function test_dashboard_preview_returns_correct_widgets()
    {
        $roleTemplate = RoleTemplate::factory()->create([
            'widget_permissions' => ['widgets.dashboard.system-health']
        ]);
        
        $response = $this->actingAs($this->adminUser())
            ->get("/api/role-templates/{$roleTemplate->id}/preview/dashboard");
            
        $response->assertOk()
            ->assertJsonPath('data.dashboard.available_widgets.0.id', 'system-health')
            ->assertJsonStructure([
                'data' => [
                    'role_template' => ['id', 'name'],
                    'mock_user' => ['name', 'context'],
                    'dashboard' => [
                        'available_widgets',
                        'layout',
                        'widget_data',
                        'navigation'
                    ]
                ]
            ]);
    }
}
```

## Deployment Considerations

### Migration Strategy

```php
// Migration for existing installations
class AddThreeDimensionalPermissions extends Migration
{
    public function up()
    {
        Schema::table('role_templates', function (Blueprint $table) {
            $table->json('widget_permissions')->nullable();
            $table->json('page_permissions')->nullable();
            $table->json('dashboard_layout')->nullable();
        });
        
        // Migrate existing permissions
        $this->migrateExistingPermissions();
    }
    
    private function migrateExistingPermissions()
    {
        RoleTemplate::chunk(100, function ($roleTemplates) {
            foreach ($roleTemplates as $template) {
                $template->update([
                    'widget_permissions' => $this->generateDefaultWidgetPermissions($template),
                    'page_permissions' => $this->generateDefaultPagePermissions($template),
                ]);
            }
        });
    }
}
```

### Configuration

```php
// config/permissions.php
return [
    'three_dimensional' => [
        'enabled' => true,
        'cache_ttl' => 3600,
        'super_admin_email' => env('SUPER_ADMIN_EMAIL', 'admin@servicevault.local'),
    ],
    
    'widgets' => [
        'auto_discovery' => true,
        'cache_registry' => true,
        'default_size' => ['w' => 4, 'h' => 3],
    ],
    
    'preview' => [
        'enabled' => true,
        'mock_data_cache' => 300,
        'contexts' => ['service_provider', 'account_user'],
    ]
];
```

---

**Service Vault Three-Dimensional Permission System** - Comprehensive architecture providing granular access control across functional operations, dashboard widgets, and page navigation with live preview capabilities.