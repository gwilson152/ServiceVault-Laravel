# Permission System Architecture

Service Vault implements a sophisticated three-dimensional permission system with centralized permission management through the PermissionService.

## Overview

The permission system consists of three dimensions:
- **Functional Permissions**: What users can DO (e.g., `time.manage`, `tickets.assign`)
- **Widget Permissions**: What they SEE on dashboard (e.g., `widgets.dashboard.tickets`)
- **Page Permissions**: What pages they can ACCESS (e.g., `pages.admin.users`)

## PermissionService

The `App\Services\PermissionService` provides centralized permission logic for consistent application behavior.

### Key Methods

#### Agent Management
```php
// Get users who can act as agents for specific types
PermissionService::getAgentsForType('time', ['search' => 'john']);
PermissionService::getAgentsForType('ticket', ['account_id' => 123]);

// Check if user can view agent dropdowns
PermissionService::canViewAgents($user, 'time');

// Check if user can assign work to others
PermissionService::canAssignToOthers($user, 'billing');
```

#### Permission Filtering
```php
// Filter users by permissions (handles Super Admin logic)
PermissionService::filterUsersByPermissions($users, ['time.manage', 'admin.write']);

// Get required permissions for agent types
PermissionService::getAgentPermissions('time'); // Returns array of permissions
```

### Supported Agent Types

- `timer` - Timer creation and assignment
- `ticket` - Ticket assignment and management
- `time` - Time entry creation and assignment
- `billing` - Billing responsibility assignment

### Permission Hierarchies

Each agent type has a permission hierarchy:

**Time Entry Agents:**
- `time.act_as_agent` (feature-specific)
- `time.assign` (management)
- `time.manage` (administrative)
- `admin.write` (super admin)

**Timer Agents:**
- `timers.act_as_agent`
- `timers.assign`
- `timers.manage`
- `admin.write`

## Super Admin Logic

Super Admin users automatically inherit ALL permissions through the `User::hasPermission()` method. The PermissionService properly handles this by using application-level filtering instead of database queries.

## Usage Guidelines

### Controllers
Always use PermissionService methods in controllers instead of direct database queries:

```php
// ✅ Good - Uses centralized service
$agents = PermissionService::getAgentsForType($agentType, $filters);

// ❌ Bad - Direct database query (bypasses Super Admin logic)
$agents = User::whereHas('roleTemplate', function($q) use ($permissions) {
    $q->where('permissions', 'like', "%{$permission}%");
})->get();
```

### Frontend Components
Use the centralized permission checking for UI logic:

```php
// In controllers or resources
'canAssignToOthers' => PermissionService::canAssignToOthers($user, 'time'),
'availableAgents' => PermissionService::getAgentsForType('time')
```

#### Vue Component Integration
For Vue components that need permission checking, ensure proper user object structure:

```javascript
// Recommended approach for canAssignToOthers checking
const canAssignToOthers = computed(() => {
    // Super Admin can always assign to others
    if (user.value?.is_super_admin || user.value?.roleTemplate?.name === "Super Admin") {
        return true;
    }
    
    // Check specific permissions
    return (
        user.value?.permissions?.includes("time.admin") ||
        user.value?.permissions?.includes("time.assign") ||
        user.value?.permissions?.includes("time.manage") ||
        user.value?.permissions?.includes("admin.manage") ||
        user.value?.permissions?.includes("admin.write")
    );
});
```

## Migration Considerations

When updating permission logic:

1. Update `PermissionService::getAgentPermissions()` for new agent types
2. Update `PermissionService::canViewAgents()` for viewing permissions  
3. Update role template seeders with new permissions
4. Run migrations to add permissions to existing role templates

## Performance

The PermissionService uses application-level filtering to properly handle Super Admin logic and complex permission scenarios. For large datasets, consider:

- Using search/account filters to reduce the dataset size
- Caching agent lists for frequently accessed data
- Implementing pagination for large result sets

## Agent Loading Behavior

### Account Filtering Strategy

**Time Entry Agents**: No account filtering applied
- **Rationale**: Time entries represent completed work and should be assignable to any qualified agent
- **Implementation**: `loadAgentsForAccount()` ignores the account parameter for time entries
- **Consistency**: Matches timer creation dialog behavior

**Other Agent Types**: Account filtering may be applied based on business requirements
- Ticket assignment may filter by account for organizational purposes
- Billing agents may be scoped to relevant accounts

### Recent Fixes

**Timer Commit Integration** (Fixed):
- Timer overlay now uses `UnifiedTimeEntryDialog` instead of direct API calls
- Proper pre-population of agent field from timer's `user_id`
- Consistent agent loading behavior across all entry points

**Super Admin Permission Handling** (Fixed):  
- Frontend components now properly detect Super Admin status
- Centralized permission logic handles Super Admin automatic inheritance
- Database queries replaced with application-level filtering

## Related Documentation

- [Three-Dimensional Permissions](three-dimensional-permissions.md)
- [Feature-Specific Agent Permissions](../features/feature-specific-agent-permissions.md)
- [Authentication System](../system/authentication-system.md)