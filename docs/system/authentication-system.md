# Authentication System

Comprehensive user authentication and authorization system for Service Vault.

> **Integration**: Laravel Breeze + Inertia.js + Three-Dimensional Permission System  
> **Database**: PostgreSQL with UUID primary keys

## Architecture Overview

### Core Components
1. **Laravel Breeze**: Authentication foundation with Inertia.js integration
2. **Three-Dimensional Permissions**: Functional + Widget + Page access control
3. **Role Templates**: Permission blueprints for different user types
4. **Account Hierarchy**: Multi-level business account relationships
5. **User Invitations**: Email-based user onboarding system

### Authentication Methods
- **Session Authentication**: Web dashboard with cookie-based sessions
- **API Token Authentication**: Laravel Sanctum with granular abilities
- **Hybrid Support**: Single codebase supporting both methods

## User Management System

### Enhanced User Model
```php
class User extends Authenticatable
{
    use HasApiTokens, HasUuid;
    
    protected $fillable = [
        'name', 'email', 'password', 'account_id', 'role_template_id',
        'preferences', 'timezone', 'locale', 'is_active'
    ];
    
    // Core relationships
    public function account(): BelongsTo;           // Primary account
    public function roleTemplate(): BelongsTo;     // Assigned role template
    public function timers(): HasMany;             // User's timers
    public function assignedTickets(): HasMany;    // Assigned service tickets
    
    // Permission methods
    public function hasPermission(string $permission): bool;
    public function isSuperAdmin(): bool;
    public function hasAnyPermission(array $permissions): bool;
}
```

### Database Schema
```sql
-- Users table (enhanced Laravel default)
CREATE TABLE users (
    id UUID PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    account_id UUID REFERENCES accounts(id),
    role_template_id UUID REFERENCES role_templates(id),
    preferences JSONB,
    timezone VARCHAR(100) DEFAULT 'UTC',
    locale VARCHAR(10) DEFAULT 'en',
    last_active_at TIMESTAMP,
    last_login_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    remember_token VARCHAR(100),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Three-Dimensional Permission System

### Permission Dimensions
1. **Functional Permissions**: What users can DO
   - `accounts.manage` - Account management operations
   - `tickets.view.account` - View account tickets
   - `timers.admin` - Timer administrative control
   - `admin.read` - Administrative data access

2. **Widget Permissions**: What users can SEE
   - `widgets.dashboard.system-health` - System Health widget
   - `widgets.dashboard.ticket-overview` - Ticket Overview widget
   - `widgets.dashboard.all-timers` - All Active Timers (admin)

3. **Page Permissions**: What pages users can ACCESS
   - `pages.admin.system` - System Administration page
   - `pages.tickets.manage` - Ticket Management page
   - `pages.billing.overview` - Billing Overview page

### Role Template System
```php
class RoleTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'context', 
        'permissions',              // Functional permissions
        'widget_permissions',       // Widget permissions
        'page_permissions',        // Page permissions
        'dashboard_layout',        // Default widget layout
        'is_system_role', 'is_modifiable'
    ];
    
    protected $casts = [
        'permissions' => 'array',
        'widget_permissions' => 'array', 
        'page_permissions' => 'array',
        'dashboard_layout' => 'array'
    ];
}
```

### Default Role Templates

**System Roles:**
- **Super Administrator**: Complete system access + role management
  - Permissions: All system, account, widget, and page permissions
- **System Administrator**: System admin without role template management
  - Permissions: System management excluding role template modification

**Account Roles:**
- **Account Manager**: Account-specific management capabilities
- **Team Lead**: Team oversight and approval workflows
- **Employee** (default): Standard time tracking and basic access
- **Customer**: Portal access with limited visibility

## Authentication Routes

### Laravel Breeze Routes
```php
// Authentication routes (routes/auth.php)
Route::get('/register', [RegisteredUserController::class, 'create']);
Route::post('/register', [RegisteredUserController::class, 'store']);
Route::get('/login', [AuthenticatedSessionController::class, 'create']);
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy']);
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create']);
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store']);
Route::get('/reset-password/{token}', [NewPasswordController::class, 'create']);
Route::post('/reset-password', [NewPasswordController::class, 'store']);
```

### API Authentication Routes
```php
// Token management (routes/api.php)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/tokens', [TokenController::class, 'index']);
    Route::post('/auth/tokens', [TokenController::class, 'store']);
    Route::delete('/auth/tokens/{token}', [TokenController::class, 'destroy']);
    Route::post('/auth/tokens/revoke-all', [TokenController::class, 'revokeAll']);
    Route::get('/auth/tokens/abilities', [TokenController::class, 'abilities']);
    Route::post('/auth/tokens/scope', [TokenController::class, 'createScoped']);
});
```

## Laravel Sanctum Integration

### Token Abilities System
23 granular abilities across system features:
```php
class TokenAbilityService {
    public const ABILITIES = [
        // Timer Management
        'timers:read' => 'View timer data',
        'timers:write' => 'Create and update timers',
        'timers:sync' => 'Cross-device synchronization',
        
        // Service Tickets  
        'tickets:read' => 'View ticket information',
        'tickets:write' => 'Create and modify tickets',
        'tickets:account' => 'Access account hierarchy tickets',
        
        // Widget & UI Control
        'widgets:dashboard' => 'Access dashboard widgets',
        'widgets:configure' => 'Configure widget settings', 
        'pages:access' => 'Access specific pages',
        
        // Administrative
        'admin:read' => 'View administrative data',
        'admin:write' => 'Administrative operations',
    ];
}
```

### Predefined Token Scopes
```php
public const SCOPES = [
    'employee' => ['timers:read', 'timers:write', 'tickets:read', 'widgets:dashboard'],
    'manager' => ['timers:read', 'timers:write', 'tickets:write', 'tickets:account'],
    'mobile-app' => ['timers:read', 'timers:write', 'timers:sync', 'tickets:read'],
    'admin' => ['*'] // All abilities including widgets and pages
];
```

## User Registration & Invitations

### Enhanced Registration Process
```php
public function store(Request $request): RedirectResponse
{
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'timezone' => 'UTC',
        'locale' => 'en',
        'is_active' => true,
    ]);

    // Auto-assign to account and role via domain mapping or default
    $this->assignAccountAndRole($user);

    event(new Registered($user));
    Auth::login($user);
    
    return redirect(route('dashboard'));
}
```

### User Invitation System
```sql
CREATE TABLE user_invitations (
    id UUID PRIMARY KEY,
    email VARCHAR(255) NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    invited_by_user_id UUID REFERENCES users(id),
    account_id UUID REFERENCES accounts(id),
    role_template_id UUID REFERENCES role_templates(id),
    invited_name VARCHAR(255),
    message TEXT,
    status VARCHAR(20) DEFAULT 'pending',
    expires_at TIMESTAMP,
    accepted_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## Permission Resolution

### Context-Aware Permission Checking
```php
public function hasPermission(string $permission): bool
{
    if (!$this->roleTemplate) return false;
    
    // Super Admin bypass
    if ($this->roleTemplate->isSuperAdmin()) return true;
    
    // Check all three permission dimensions
    return in_array($permission, array_merge(
        $this->roleTemplate->permissions ?? [],
        $this->roleTemplate->widget_permissions ?? [],
        $this->roleTemplate->page_permissions ?? []
    ));
}
```

### API Token Permission Checking
```php
// In policies and middleware
if ($user->currentAccessToken()) {
    return $user->tokenCan('timers:read');
}
// Fall back to role-based permissions
return $user->hasPermission('timers.view');
```

## Security Features

### Password Security
- Laravel bcrypt hashing with automatic salting
- Minimum 8 character requirements
- Password confirmation for sensitive operations
- Password reset via secure email links

### Session Security
- Secure cookie configuration
- CSRF protection on all forms
- Session fixation protection
- Remember token functionality

### API Security  
- Token-based authentication with expiration
- Granular ability-based access control
- Rate limiting per endpoint
- Request throttling and abuse prevention

## Integration Points

### Setup Wizard Integration
- Setup creates default role templates
- First admin gets Super Administrator role
- Primary account establishment and assignment

### Domain Mapping Integration
- Automatic user-to-account assignment based on email domain
- Domain pattern matching with wildcard support
- Priority-based domain mapping rules

### Timer System Integration
- User relationship to timers and time entries
- Permission-based timer operation access
- Account context for timer scoping and billing

## Performance Optimizations

### Permission Caching
```php
// Cache user permissions for 5 minutes
Cache::remember("user_permissions_{$user->id}", 300, function() use ($user) {
    return $user->roleTemplate->getAllPermissions();
});
```

### Database Optimization
- UUID primary keys with proper indexing
- Foreign key indexes on all relationships
- Unique constraints on critical fields
- Query optimization for permission checking

### Session Efficiency
- Minimal session data storage
- Lazy loading of user relationships
- Efficient permission resolution algorithms

## Monitoring & Logging

### Authentication Events
- Login/logout tracking with IP addresses
- Failed login attempt monitoring
- Password reset request logging
- Token creation and usage tracking

### Security Monitoring
- Permission violation attempts
- Suspicious activity detection
- Rate limit violations
- API abuse monitoring

## Future Enhancements

1. **Two-Factor Authentication**: TOTP and SMS-based 2FA
2. **Social Login**: OAuth integration with major providers  
3. **Advanced Password Policies**: Complexity requirements and rotation
4. **Device Management**: Device registration and trusted device tracking
5. **Advanced Audit Logging**: Comprehensive activity tracking and reporting

Service Vault's authentication system provides enterprise-grade security with flexible permission management, supporting both web and API access patterns while maintaining scalability and performance.