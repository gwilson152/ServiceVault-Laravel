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
- `timers.admin` - Timer administrative control (required for timer assignment)
- `admin.read` - Administrative data access (read-only)
- `admin.write` - Administrative data modification (required for agent APIs)
- `admin.manage` - Administrative management operations

**Feature-Specific Agent Permissions** - Who can act as agents
- `timers.act_as_agent` - Can be assigned as timer agent
- `tickets.act_as_agent` - Can be assigned to tickets
- `time.act_as_agent` - Can be assigned time entries
- `billing.act_as_agent` - Can handle billing responsibilities

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
    
    // Check all three dimensions via getAllPermissions()
    return in_array($permission, $this->roleTemplate->getAllPermissions());
}

// RoleTemplate.getAllPermissions() - Updated Implementation
public function getAllPermissions(): array
{
    if ($this->isSuperAdmin()) {
        return $this->getAllPossiblePermissions();
    }
    
    // Merge all three permission dimensions for comprehensive checking
    return array_unique(array_merge(
        $this->permissions ?? [],
        $this->widget_permissions ?? [],
        $this->page_permissions ?? []
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

## Feature-Specific Agent Permission System

### Multi-Layer Agent Determination

Service Vault implements a sophisticated agent assignment system with three layers of validation:

```
┌─────────────────────────────────────────────────────────┐
│                Agent Permission Layers                 │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  Layer 1: Primary Agent Designation                    │
│  ┌─────────────────────────────────────────────────┐    │
│  │ user_type = 'agent'                             │    │
│  │ Direct agent designation                        │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
│  Layer 2: Feature-Specific Permissions                 │
│  ┌─────────────────────────────────────────────────┐    │
│  │ timers.act_as_agent    tickets.act_as_agent     │    │
│  │ time.act_as_agent      billing.act_as_agent     │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
│  Layer 3: Fallback Permissions                         │
│  ┌─────────────────────────────────────────────────┐    │
│  │ Internal users with relevant functional perms   │    │
│  │ timers.write, tickets.manage, time.track, etc.  │    │
│  └─────────────────────────────────────────────────┘    │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

### Agent Permission Matrix

| Feature | Primary Agent | Feature Permission | Fallback Permissions |
|---------|--------------|-------------------|---------------------|
| **Timers** | `user_type = 'agent'` | `timers.act_as_agent` | `timers.write`, `timers.manage` |
| **Tickets** | `user_type = 'agent'` | `tickets.act_as_agent` | `tickets.assign`, `tickets.manage` |
| **Time Entries** | `user_type = 'agent'` | `time.act_as_agent` | `time.track`, `time.manage` |
| **Billing** | `user_type = 'agent'` | `billing.act_as_agent` | `billing.manage`, `billing.admin` |

### Implementation Example

```php
// Agent filtering logic in UserController
public function agents(Request $request): JsonResponse
{
    $agentType = $request->get('agent_type', 'timer');
    $requiredPermission = $this->getRequiredAgentPermission($agentType);
    $fallbackPermissions = $this->getFallbackPermissions($agentType);
    
    $query = User::where('is_active', true)
        ->where(function ($q) use ($requiredPermission, $fallbackPermissions) {
            // Layer 1: Primary agents
            $q->where('user_type', 'agent')
              // Layer 2: Feature-specific permission
              ->orWhereHas('roleTemplate', function ($roleQuery) use ($requiredPermission) {
                  $roleQuery->where('permissions', 'like', "%{$requiredPermission}%");
              })
              // Layer 3: Fallback for internal users
              ->orWhere(function ($subQuery) use ($fallbackPermissions) {
                  $subQuery->whereHas('account', function ($accountQuery) {
                      $accountQuery->where('account_type', 'internal');
                  })->whereHas('roleTemplate', function ($roleQuery) use ($fallbackPermissions) {
                      foreach ($fallbackPermissions as $permission) {
                          $roleQuery->orWhere('permissions', 'like', "%{$permission}%");
                      }
                  });
              });
        });
}
```

### API Integration

The system provides specialized endpoints for each agent type:

- `GET /api/users/agents?agent_type=timer` - Timer agents
- `GET /api/users/agents?agent_type=time` - Time entry agents  
- `GET /api/users/assignable` - Ticket assignment (uses tickets.act_as_agent)
- `GET /api/users/billing-agents` - Billing agents

## Key Benefits

1. **Granular Control**: Fine-grained permissions across all system areas
2. **Agent Specialization**: Feature-specific agent assignment with multi-layer validation
3. **UI Consistency**: Dashboard adapts to user permissions automatically
4. **Context Awareness**: Different permission sets for different user types
5. **Preview System**: Test role configurations before deployment
6. **Security Enhancement**: Multi-layer validation prevents unauthorized agent assignment
7. **Scalable**: Easy to add new permissions, widgets, and agent types
8. **Three-Dimensional Integration**: Unified permission checking across functional, widget, and page dimensions

## Recent Improvements (August 2025)

### Permission System Reconciliation
- **Fixed Three-Dimensional Integration**: `getAllPermissions()` now properly merges functional, widget, and page permissions
- **Resolved Frontend/Backend Mismatches**: Aligned permission checks between UI components and API endpoints
- **Enhanced Super Admin Role**: Added missing `timers.admin` and `admin.write` permissions for complete functionality
- **Improved Agent Assignment**: Fixed timer assignment dialogs to properly load and display available agents