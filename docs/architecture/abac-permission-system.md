# ABAC Permission System Architecture

Service Vault implements a comprehensive **Attribute-Based Access Control (ABAC)** system with role templates and hierarchical permission inheritance.

## Overview

The ABAC system provides fine-grained authorization without hard-coded roles, using:

-   **Role Templates**: JSON-based permission definitions
-   **Hierarchical Inheritance**: Account-based permission propagation
-   **Laravel Integration**: Gates, Policies, and Middleware
-   **Caching Strategy**: Redis-based permission caching (5-minute TTL)

## Core Components

### 1. PermissionService (`app/Services/PermissionService.php`)

The central service for all permission operations:

```php
class PermissionService
{
    // Check permission for specific account with inheritance
    public function hasPermissionForAccount(User $user, string $permission, Account $account): bool

    // Get all accessible accounts for user
    public function getAccessibleAccounts(User $user, string $permission = null): Collection

    // System-level permission checking
    public function hasSystemPermission(User $user, string $permission): bool

    // Clear permission cache for user
    public function clearUserPermissionCache(User $user): void
}
```

### 2. Laravel Gates (`app/Providers/AppServiceProvider.php`)

System-wide permission gates:

```php
// Account management gates
Gate::define('manage-account', function (User $user, Account $account) {
    return app(PermissionService::class)->hasPermissionForAccount($user, 'accounts.manage', $account);
});

// System-level gates
Gate::define('manage-users', function (User $user) {
    return app(PermissionService::class)->hasSystemPermission($user, 'system.manage_users');
});
```

### 3. CheckPermission Middleware (`app/Http/Middleware/CheckPermission.php`)

Route-level authorization:

```php
// Usage in routes
Route::middleware(['auth', 'permission:accounts.manage,account'])->group(function () {
    Route::apiResource('accounts', AccountController::class);
});
```

### 4. Model Policies

Laravel policies for model-specific authorization:

-   `AccountPolicy`: Hierarchical account permissions
-   `TimerPolicy`: Cross-device timer access
-   `TimeEntryPolicy`: Approval workflow permissions

## Permission Structure

### Role Templates

JSON-based permission definitions stored in `role_templates` table:

```json
{
    "name": "Account Manager",
    "permissions": [
        "accounts.manage",
        "users.assign",
        "rates.customize",
        "reports.view_account"
    ],
    "is_system_role": false,
    "is_default": false
}
```

### Default Role Templates

| Role Template         | Permissions                               | Scope   |
| --------------------- | ----------------------------------------- | ------- |
| Super Administrator   | `system.full_access`, `*`                 | System  |
| System Administrator  | `system.manage_users`, `accounts.create`  | System  |
| Account Manager       | `accounts.manage`, `users.assign`         | Account |
| Team Lead/Manager     | `team.oversight`, `timeentries.approve`   | Account |
| Employee              | `timers.manage_own`, `timeentries.create` | Account |
| Customer/Client       | `portal.access`, `tickets.view_own`       | Account |
| Billing Administrator | `invoices.generate`, `payments.track`     | Account |

### Permission Naming Convention

```
<domain>.<action>[_scope]

Examples:
- accounts.manage
- timers.manage_own
- system.manage_users
- reports.view_team
- invoices.generate
```

## Hierarchical Inheritance

### Account Hierarchy

Permissions inherit through the account hierarchy:

```
Root Account (Company)
├── Department A
│   ├── Team 1
│   └── Team 2
└── Department B
    └── Team 3
```

### Inheritance Rules

1. **Direct Permission**: User has role template on specific account
2. **Inherited Permission**: User has role template on parent account
3. **System Permission**: User has system-level role template

```php
public function hasPermissionForAccount(User $user, string $permission, Account $account): bool
{
    // Check direct permissions
    if ($this->hasDirectPermission($user, $permission, $account)) {
        return true;
    }

    // Check inherited permissions from parent accounts
    return $account->ancestors()->get()->some(function ($parent) use ($user, $permission) {
        return $this->hasDirectPermission($user, $permission, $parent);
    });
}
```

## Caching Strategy

### Cache Keys

```php
// User permissions for specific account
"user_permissions:{user_id}:account:{account_id}"

// All user permissions for account
"user_all_permissions:{user_id}:account:{account_id}"

// User's accessible accounts
"user_accessible_accounts:{user_id}:{permission}"

// System-level permissions
"user_system_permissions:{user_id}"
```

### Cache TTL

-   **5 minutes** for all permission caches
-   **Auto-invalidation** on role template changes
-   **Manual clearing** via `clearUserPermissionCache()`

## Integration Points

### Controller Authorization

```php
class AccountController extends Controller
{
    public function show(Account $account): JsonResponse
    {
        $this->authorize('view-account', $account);
        // ... controller logic
    }

    public function selector(Request $request): JsonResponse
    {
        $user = $request->user();
        $accounts = $this->permissionService->getAccessibleAccounts($user, 'accounts.manage');
        // ... return hierarchical accounts for AccountSelector
    }
}
```

### Frontend Integration

The permission system supports the **AccountSelector Component** for domain mapping:

```php
// AccountController::selector() provides hierarchical accounts
// Filtered by user's 'accounts.manage' permission
// Used in Settings → Email → Domain Mapping
```

### Middleware Usage

```php
// Route-level permission checking
Route::middleware(['permission:accounts.create'])->post('/accounts', [AccountController::class, 'store']);

// Account-specific permission checking
Route::middleware(['permission:accounts.manage,account'])->put('/accounts/{account}', [AccountController::class, 'update']);
```

## Security Considerations

### Permission Validation

1. **Input Validation**: All permissions validated against role templates
2. **Context Checking**: Account context required for account-scoped permissions
3. **Inheritance Validation**: Parent account permissions verified
4. **Cache Security**: User-specific cache keys prevent cross-user access

### Audit Trail

-   All permission checks logged in Laravel logs
-   Role template changes tracked in database
-   User permission assignments audited
-   Cache invalidation events logged

## Performance Optimization

### Database Queries

-   **Eager Loading**: Load role templates with accounts
-   **Query Optimization**: Indexed foreign keys
-   **Batch Operations**: Bulk permission checks

### Cache Efficiency

-   **Selective Caching**: Only cache frequently accessed permissions
-   **Cache Warming**: Pre-populate cache for active users
-   **Memory Management**: TTL prevents cache bloat

## Testing Strategy

### Unit Tests

```php
public function test_user_can_manage_account_with_direct_permission()
{
    $user = User::factory()->create();
    $account = Account::factory()->create();

    // Assign role template with accounts.manage permission
    $roleTemplate = RoleTemplate::factory()->create([
        'permissions' => ['accounts.manage']
    ]);

    $user->accounts()->attach($account, ['role_template_id' => $roleTemplate->id]);

    $this->assertTrue(
        app(PermissionService::class)->hasPermissionForAccount($user, 'accounts.manage', $account)
    );
}
```

### Feature Tests

```php
public function test_account_controller_enforces_permissions()
{
    $user = User::factory()->create();
    $account = Account::factory()->create();

    // User without permission
    $response = $this->actingAs($user)->get("/api/accounts/{$account->id}");
    $response->assertStatus(403);

    // User with permission
    $this->giveUserPermission($user, 'accounts.view', $account);
    $response = $this->actingAs($user)->get("/api/accounts/{$account->id}");
    $response->assertStatus(200);
}
```

## Migration from Hard-Coded Roles

Service Vault's ABAC system replaces traditional hard-coded roles:

### Before (Hard-Coded)

```php
if ($user->role === 'admin') {
    // Allow access
}
```

### After (ABAC)

```php
if (Gate::allows('manage-account', $account)) {
    // Allow access
}
```

This provides:

-   **Flexibility**: New permissions without code changes
-   **Granularity**: Fine-grained access control
-   **Scalability**: Supports complex organizational structures
-   **Maintainability**: Single source of truth for permissions

## Future Enhancements

### Planned Features

1. **Conditional Permissions**: Time-based or context-aware permissions
2. **Permission Templates**: Reusable permission sets
3. **Advanced Inheritance**: Custom inheritance rules
4. **Permission Analytics**: Usage tracking and optimization
5. **API Permissions**: External API access control

### Integration Roadmap

1. **Phase 4**: Complete API controller authorization
2. **Phase 6**: Frontend permission-aware components
3. **Phase 7**: Dashboard role-based navigation
4. **Phase 8**: Domain mapping with account selection

The ABAC system provides the foundation for Service Vault's multi-role dashboard system and enterprise-level permission management.
