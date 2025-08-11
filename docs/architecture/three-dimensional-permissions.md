# Three-Dimensional Permission System

Comprehensive access control across functional operations, dashboard widgets, and page navigation.

## Architecture Overview

Three interconnected permission dimensions:

```
┌─────────────────────────────────────────┐
│        Three-Dimensional Permissions   │
├─────────────────────────────────────────┤
│  Functional     Widget        Page      │
│  • API Access  • Dashboard   • Routes   │
│  • Features    • Components  • Navigation│
│  • Operations  • Widgets     • UI Access│
│       ↓            ↓            ↓       │
│         Role Templates (Blueprints)     │
└─────────────────────────────────────────┘
```

## Core Components

### 1. Permission Dimensions

**Functional Permissions** - What users can DO
- `accounts.manage` - Account management operations
- `tickets.view.account` - View account tickets
- `timers.admin` - Timer administrative control
- `admin.read` - Administrative data access

**Widget Permissions** - What users can SEE
- `widgets.dashboard.system-health` - System Health widget
- `widgets.dashboard.ticket-overview` - Ticket Overview widget
- `widgets.dashboard.all-timers` - All Active Timers (admin)

**Page Permissions** - What pages users can ACCESS
- `pages.admin.system` - System Administration page
- `pages.tickets.manage` - Ticket Management page
- `pages.billing.overview` - Billing Overview page

### 2. Role Template System

Role templates combine all three permission types:

```php
class RoleTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'context',
        'permissions',              // Functional permissions array
        'widget_permissions',       // Widget permissions array  
        'page_permissions',        // Page permissions array
        'dashboard_layout'         // Default widget layout
    ];
}
```

### 3. Context Awareness

**Service Provider Context**: Internal staff with cross-account access
**Account User Context**: Customer users with account-scoped access
**Universal Context**: Roles that work in both contexts

## Implementation

### Permission Checking
```php
// Functional permission
$user->hasPermission('tickets.view.account');

// Widget permission (UI filtering)
$user->hasPermission('widgets.dashboard.ticket-overview');

// Page permission (route access)
$user->hasPermission('pages.tickets.manage');
```

### Dashboard Preview System
Role templates include dashboard configuration with widget assignments:

```php
'dashboard_layout' => [
    'widgets' => [
        [
            'id' => 'ticket-overview',
            'component' => 'TicketOverviewWidget',
            'position' => ['x' => 0, 'y' => 0, 'w' => 6, 'h' => 4],
            'permissions' => ['widgets.dashboard.ticket-overview']
        ]
    ]
]
```

### Context-Aware Permission Resolution
```php
public function hasPermission(string $permission): bool
{
    if (!$this->roleTemplate) return false;
    
    // Super Admin bypass
    if ($this->roleTemplate->isSuperAdmin()) return true;
    
    // Check all three dimensions
    return in_array($permission, array_merge(
        $this->roleTemplate->permissions ?? [],
        $this->roleTemplate->widget_permissions ?? [],
        $this->roleTemplate->page_permissions ?? []
    ));
}
```

## Database Schema

### Role Templates Table
```sql
CREATE TABLE role_templates (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    context VARCHAR(50),                    -- service_provider, account_user, both
    permissions JSONB,                      -- Functional permissions
    widget_permissions JSONB,               -- Widget permissions
    page_permissions JSONB,                 -- Page permissions
    dashboard_layout JSONB,                 -- Default dashboard config
    is_system_role BOOLEAN DEFAULT FALSE,
    is_modifiable BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Permission Caching
Permissions cached in Redis for 5 minutes per user:
```php
Cache::remember("user_permissions_{$user->id}", 300, function() use ($user) {
    return $user->roleTemplate->getAllPermissions();
});
```

## API Integration

### Role Management Endpoints
- `GET /api/role-templates` - List role templates
- `GET /api/role-templates/{id}/preview/dashboard` - Preview dashboard
- `POST /api/role-templates/{id}/widgets` - Update widget assignments

### Widget Permission Management  
- `GET /api/widget-permissions` - Available widget permissions
- `POST /api/widget-permissions/sync` - Sync widget registry
- `POST /api/widget-permissions/{id}/assign-to-role` - Assign to role

## Key Benefits

1. **Granular Control**: Fine-grained permissions across all system areas
2. **UI Consistency**: Dashboard adapts to user permissions automatically
3. **Context Awareness**: Different permission sets for different user types
4. **Preview System**: Test role configurations before deployment
5. **Scalable**: Easy to add new permissions and widgets