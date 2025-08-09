# Authentication System Documentation

> **Status**: ✅ Complete (Phase 7)  
> **Integration**: Laravel Breeze + Inertia.js + ABAC Permission System  
> **Database**: PostgreSQL with complete relational schema

## Overview

Service Vault's authentication system provides comprehensive user management with role-based access control through an Attribute-Based Access Control (ABAC) system. The implementation integrates Laravel Breeze for authentication workflows with a custom permission system that supports hierarchical account structures and flexible role templates.

## Architecture Components

### 1. Authentication Foundation (Laravel Breeze)

**Authentication Routes** (`routes/auth.php`):
- Registration: `/register` (GET/POST)
- Login: `/login` (GET/POST)  
- Password Reset: `/forgot-password`, `/reset-password` (GET/POST)
- Email Verification: `/verify-email` (GET/POST)
- Password Confirmation: `/confirm-password` (GET/POST)
- Logout: `/logout` (POST)

**Authentication Pages** (`resources/js/Pages/Auth/`):
- `Login.vue` - User login form
- `Register.vue` - User registration form
- `ForgotPassword.vue` - Password reset request
- `ResetPassword.vue` - Password reset form
- `VerifyEmail.vue` - Email verification prompt
- `ConfirmPassword.vue` - Password confirmation

### 2. Enhanced User Model

**Database Schema** (`users` table):
```sql
id, name, email, email_verified_at, password, remember_token,
current_account_id (foreign key to accounts),
preferences (JSON),
timezone (string, default: 'UTC'),
locale (string, default: 'en'),
last_active_at (timestamp),
is_active (boolean, default: true),
created_at, updated_at
```

**Key Relationships**:
```php
// Current working account
public function currentAccount(): BelongsTo

// All accounts user has access to (many-to-many)
public function accounts(): BelongsToMany

// All roles assigned across accounts
public function roles(): BelongsToMany

// User's timers and time entries
public function timers(): HasMany
public function timeEntries(): HasMany

// Assigned projects
public function projects(): BelongsToMany
```

**ABAC Permission Methods**:
```php
// Check account-specific permissions
public function hasPermissionForAccount(string $permission, Account $account): bool

// Check system-level permissions
public function hasSystemPermission(string $permission): bool

// Get all permissions for current account
public function getAllPermissions(): array

// Switch working account context
public function switchToAccount(Account $account): void

// Get current active timer
public function getCurrentTimer(): Timer|null
```

### 3. Role Template System

**Database Schema** (`role_templates` table):
```sql
id, name (unique), permissions (JSON), is_system_role (boolean),
is_default (boolean), description (text), created_at, updated_at
```

**Default Role Templates** (Created during setup):

#### System Roles
- **Super Administrator**:
  - Permissions: `system.manage`, `accounts.create`, `accounts.manage`, `users.manage`, `role_templates.manage`, `timers.manage`, `billing.manage`, `settings.manage`
  - Full system access + role template management

- **System Administrator**:
  - Permissions: `accounts.create`, `accounts.manage`, `users.manage`, `timers.manage`, `billing.manage`, `settings.manage`
  - System administration without role template management

#### Account Roles
- **Account Manager**:
  - Permissions: `account.manage`, `users.assign`, `projects.manage`, `billing.view`
  - Account-specific management capabilities

- **Team Lead**:
  - Permissions: `team.manage`, `projects.manage`, `time_entries.approve`, `reports.view`
  - Team oversight and approval workflows

- **Employee** (Default):
  - Permissions: `timers.create`, `timers.manage`, `time_entries.create`, `projects.view`
  - Standard time tracking access

- **Customer**:
  - Permissions: `portal.access`, `tickets.view`, `invoices.view`
  - Portal access with limited visibility

### 4. Role Instance System

**Database Schema** (`roles` table):
```sql
id, account_id (FK), role_template_id (FK), name (optional custom name),
custom_permissions (JSON, account-specific overrides), is_active (boolean),
created_at, updated_at

UNIQUE KEY (account_id, role_template_id)
```

**Permission Resolution**:
```php
public function getEffectivePermissions(): array
{
    $basePermissions = $this->roleTemplate->permissions ?? [];
    $customPermissions = $this->custom_permissions ?? [];
    
    return array_unique(array_merge($basePermissions, $customPermissions));
}
```

### 5. User Invitation System

**Database Schema** (`user_invitations` table):
```sql
id, email, token (64 chars, unique), invited_by_user_id (FK),
account_id (FK), role_template_id (FK), invited_name,
message (text), status ('pending'|'accepted'|'expired'|'cancelled'),
expires_at, accepted_at, accepted_by_user_id (FK), created_at, updated_at
```

**Invitation Workflow**:
1. Administrator creates invitation with email, account, and role template
2. System generates unique token and sends email notification
3. Recipient clicks invitation link to register/accept
4. User account created with specified role in target account
5. Invitation marked as accepted with timestamp

**Key Methods**:
```php
public function isExpired(): bool           // Check expiration
public function isPending(): bool           // Check if awaiting response
public function markAsAccepted(User $user): void  // Complete invitation
public static function generateToken(): string     // Generate secure token
```

## Registration Integration

### Enhanced Registration Process

**RegisteredUserController** handles automatic role assignment:

```php
public function store(Request $request): RedirectResponse
{
    // Create user with Service Vault fields
    $user = User::create([
        'name' => $request->name,
        'email' => $request->email,
        'password' => Hash::make($request->password),
        'timezone' => 'UTC',
        'locale' => 'en',
        'is_active' => true,
    ]);

    // Assign to default account and role
    $this->assignDefaultAccountAndRole($user);

    event(new Registered($user));
    Auth::login($user);
    
    return redirect(route('dashboard'));
}
```

**Default Assignment Logic**:
1. Assigns user to primary account (first account from setup)
2. Creates role instance using "Employee" template (default role)
3. Establishes account-user and role-user relationships
4. Sets primary account as user's current working account

## Permission Service Integration

The authentication system integrates with `App\Services\PermissionService` for:

- **Permission Caching**: 5-minute TTL for user permissions
- **Hierarchical Checking**: Account inheritance through parent relationships  
- **Context Switching**: Permission validation based on current account
- **System vs Account Permissions**: Distinction between global and account-scoped permissions

## Security Features

### 1. Password Security
- Laravel's default password requirements
- Secure password hashing with bcrypt
- Password confirmation for sensitive operations

### 2. Email Verification
- Built-in Laravel email verification
- Signed URL generation for verification links
- Rate limiting on verification attempts

### 3. Session Management
- Secure session handling via Laravel Sanctum
- Remember token functionality
- Device tracking capabilities (ready for implementation)

### 4. Permission Validation
- Middleware-based route protection
- Model policy authorization
- Real-time permission checking

## Database Relationships

### Core Relationship Map
```
User
├── belongsTo currentAccount (Account)
├── belongsToMany accounts (Account via account_user)
├── belongsToMany roles (Role via role_user)
├── hasMany timers (Timer)
├── hasMany timeEntries (TimeEntry)
└── belongsToMany projects (Project via project_user)

Role
├── belongsTo account (Account)
├── belongsTo roleTemplate (RoleTemplate)
└── belongsToMany users (User via role_user)

RoleTemplate
└── hasMany roles (Role)

Account
├── hasMany roles (Role)
├── belongsToMany users (User via account_user)
└── belongsTo parent (Account) // Hierarchical structure

UserInvitation
├── belongsTo invitedBy (User)
├── belongsTo acceptedBy (User)
├── belongsTo account (Account)
└── belongsTo roleTemplate (RoleTemplate)
```

## Integration Points

### 1. Setup Wizard Integration
- Setup creates default role templates
- First admin user gets "Super Administrator" role
- Primary account establishment

### 2. Timer System Integration
- User relationship to timers
- Permission checking for timer operations
- Current account context for timer scoping

### 3. API Authentication
- Sanctum token authentication for API routes
- Permission-based endpoint access control
- User context in API controllers

## Future Enhancements

### 1. Domain-Based Account Assignment
- Automatic account assignment based on email domain
- Domain mapping configuration in settings
- CSV import for bulk domain rules

### 2. Advanced User Management
- Two-factor authentication (2FA)
- Social login integration (OAuth)
- Advanced password policies
- Account lockout policies

### 3. Invitation Workflow Completion
- Email templates for invitations
- Invitation management interface
- Bulk invitation capabilities
- Invitation expiration handling

### 4. Session Management
- Device tracking and management
- Concurrent session limits  
- Activity monitoring and logging
- Suspicious activity detection

## API Endpoints

### Authentication Endpoints
```
POST /login              # User authentication
POST /register           # New user registration  
POST /logout             # User logout
POST /forgot-password    # Password reset request
POST /reset-password     # Password reset
POST /email/verification-notification  # Resend verification
GET  /verify-email/{id}/{hash}  # Email verification
```

### User Management Endpoints (Planned)
```
GET    /api/users                    # List users (with permissions)
POST   /api/users/{user}/invite      # Send user invitation
GET    /api/invitations              # List pending invitations
POST   /api/invitations/{token}/accept  # Accept invitation
DELETE /api/invitations/{invitation}  # Cancel invitation
PUT    /api/users/{user}/account     # Switch user account context
```

## Testing Strategy

### Unit Tests
- User model methods and relationships
- Role template permission resolution
- Invitation token generation and validation
- Permission service integration

### Feature Tests
- Complete registration workflow
- Login/logout functionality
- Password reset process
- Email verification flow
- Role assignment during registration

### Integration Tests
- ABAC permission checking
- Account switching functionality
- Timer system integration with user context
- Setup wizard integration

## Performance Considerations

### 1. Permission Caching
- 5-minute cache TTL for user permissions
- Cache invalidation on role changes
- Efficient cache key generation

### 2. Database Optimization
- Foreign key indexes on all relationships
- Unique constraints on critical fields
- Query optimization for permission checking

### 3. Session Efficiency
- Minimal session data storage
- Lazy loading of user relationships
- Efficient current account resolution

## Related Documentation

- [ABAC Permission System](../architecture/abac-permission-system.md)
- [Setup Wizard](../system/setup-wizard.md)
- [User Management](../features/user-management.md)
- [Role Templates](../features/role-templates.md)
- [Account Management](../features/accounts.md)