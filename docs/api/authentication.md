# Authentication API

Service Vault implements a hybrid authentication system supporting both session-based web access and token-based API access using Laravel Sanctum.

## Authentication Methods

### Session Authentication (Web Dashboard)
- **Login/Register Pages**: Standard web authentication with session cookies
- **CSRF Protection**: Inertia.js integration with CSRF tokens
- **User Management**: Role assignment and ABAC permission integration

### Token Authentication (API Access)
- **Bearer Token**: API access for mobile and external clients
- **Granular Permissions**: 23 different token abilities with scoped access
- **Token Management**: Full CRUD operations for API tokens

## Token Abilities

Service Vault provides 23 granular token abilities organized by feature area:

### Timer Management
- `timers:read` - View timer data
- `timers:write` - Create and update timers
- `timers:delete` - Delete timers
- `timers:sync` - Cross-device timer synchronization

### Time Entry Management
- `time-entries:read` - View time entry data
- `time-entries:write` - Create and modify time entries
- `time-entries:approve` - Approve time entries (manager level)
- `time-entries:delete` - Delete time entries

### Project Management
- `projects:read` - View project information
- `projects:write` - Create and modify projects
- `projects:assign` - Assign users to projects

### Account Management
- `accounts:read` - View account data
- `accounts:write` - Modify account settings
- `accounts:billing` - Access billing information

### User Management
- `users:read` - View user information
- `users:write` - Modify user data
- `users:invite` - Send user invitations

### Reports & Analytics
- `reports:read` - View reports and analytics
- `reports:export` - Export report data

### Billing Management
- `billing:read` - View billing data
- `billing:write` - Modify billing settings
- `billing:rates` - Manage billing rates

### Administrative
- `admin:read` - View administrative data
- `admin:write` - Administrative operations

## Predefined Token Scopes

Service Vault provides predefined token scopes for common use cases:

### Employee Scope
Basic functionality for employees:
```php
'employee' => [
    'timers:read', 'timers:write', 'timers:sync',
    'time-entries:read', 'time-entries:write',
    'projects:read'
]
```

### Manager Scope  
Team oversight and approval capabilities:
```php
'manager' => [
    'timers:read', 'timers:write', 'timers:sync',
    'time-entries:read', 'time-entries:write', 'time-entries:approve',
    'projects:read', 'projects:write', 'projects:assign',
    'reports:read', 'users:read'
]
```

### Mobile App Scope
Full mobile application functionality:
```php
'mobile-app' => [
    'timers:read', 'timers:write', 'timers:sync',
    'time-entries:read', 'time-entries:write',
    'projects:read', 'accounts:read', 'users:read'
]
```

### Admin Scope
Complete administrative access:
```php
'admin' => ['*'] // All abilities
```

## API Endpoints

### Authentication Endpoints

#### Get CSRF Cookie
```http
GET /sanctum/csrf-cookie
```
Initialize CSRF cookie for API requests.

#### Login (Session)
```http
POST /login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

#### Register (Session)
```http
POST /register
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@company.com",
    "password": "password",
    "password_confirmation": "password"
}
```

**Note**: Users are automatically assigned to accounts based on email domain patterns.

#### Logout (Session)
```http
POST /logout
```

### Token Management Endpoints

#### List User Tokens
```http
GET /api/auth/tokens
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "name": "Mobile App Token",
            "abilities": ["timers:read", "timers:write", "timers:sync"],
            "expires_at": "2025-01-01T00:00:00Z",
            "last_used_at": "2024-12-01T10:30:00Z",
            "created_at": "2024-11-01T09:00:00Z"
        }
    ]
}
```

#### Create API Token
```http
POST /api/auth/tokens
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Mobile App Token",
    "abilities": ["timers:read", "timers:write", "timers:sync"],
    "expires_at": "2025-01-01T00:00:00Z",
    "password": "current_password"
}
```

**Response:**
```json
{
    "token": "sv_1|AbCdEf...",
    "plain_text_token": "sv_1|AbCdEf...",
    "expires_at": "2025-01-01T00:00:00Z"
}
```

#### Create Scoped Token
```http
POST /api/auth/tokens/scope
Authorization: Bearer {token}
Content-Type: application/json

{
    "name": "Employee Mobile Access",
    "scope": "employee",
    "expires_at": "2025-01-01T00:00:00Z",
    "password": "current_password"
}
```

#### Get Available Abilities
```http
GET /api/auth/tokens/abilities
Authorization: Bearer {token}
```

**Response:**
```json
{
    "abilities": {
        "timers:read": "View timer data",
        "timers:write": "Create and update timers",
        "timers:delete": "Delete timers",
        "timers:sync": "Cross-device timer synchronization"
    },
    "scopes": {
        "employee": ["timers:read", "timers:write", "projects:read"],
        "manager": ["timers:read", "timers:write", "projects:write", "reports:read"],
        "mobile-app": ["timers:read", "timers:write", "timers:sync", "projects:read"],
        "admin": ["*"]
    }
}
```

#### Revoke Token
```http
DELETE /api/auth/tokens/{token_id}
Authorization: Bearer {token}
```

#### Revoke All Tokens
```http
DELETE /api/auth/tokens/revoke-all
Authorization: Bearer {token}
Content-Type: application/json

{
    "password": "current_password"
}
```

## Policy Integration

All Laravel policies support both authentication methods:

```php
// Check token abilities if API authenticated
if ($user->currentAccessToken()) {
    return $user->tokenCan('timers:read');
}
// Default ABAC permissions for web users
return $user->hasPermission('timers.view');
```

## Security Considerations

### Token Security
- Tokens are prefixed with `sv_` for identification
- Default expiration: 1 year (525,600 minutes)
- Password verification required for sensitive operations
- Automatic token cleanup for expired tokens

### CSRF Protection
- CSRF cookies required for session-based requests
- Sanctum stateful domains properly configured
- Cross-origin requests handled securely

### Rate Limiting
- API endpoints protected with rate limiting
- Different limits for authenticated vs. unauthenticated requests
- Token-based requests can have higher limits

## Domain-Based User Assignment

New users are automatically assigned to accounts based on their email domain:

### Domain Mapping Configuration
Administrators can configure domain patterns that automatically assign users to specific accounts:

```http
POST /api/domain-mappings
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "domain_pattern": "*.company.com",
    "account_id": 123,
    "priority": 1
}
```

### Pattern Matching
- **Wildcard Support**: `*.company.com` matches any subdomain
- **Exact Matching**: `company.com` matches only the exact domain
- **Priority System**: Higher priority mappings take precedence

### Preview Assignment
```http
POST /api/domain-mappings/preview
Authorization: Bearer {admin_token}
Content-Type: application/json

{
    "email": "john@dev.company.com"
}
```

**Response:**
```json
{
    "matched_mapping": {
        "id": 1,
        "domain_pattern": "*.company.com",
        "account_id": 123,
        "account_name": "Company Inc"
    },
    "would_assign_to": {
        "id": 123,
        "name": "Company Inc"
    }
}
```