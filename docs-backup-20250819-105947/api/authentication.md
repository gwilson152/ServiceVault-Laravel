# Authentication API

API authentication endpoints and token management for Service Vault.

> **Architecture**: [System Authentication](../system/authentication-system.md)  
> **Implementation**: Laravel Sanctum hybrid authentication

## Overview

Service Vault provides secure API access through Laravel Sanctum bearer tokens with granular ability-based permissions. All API endpoints require authentication via bearer tokens.

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

## Usage Examples

### Creating API Token
```bash
# Get CSRF cookie first (for web-based token creation)
curl -X GET http://localhost:8000/sanctum/csrf-cookie

# Create scoped token
curl -X POST http://localhost:8000/api/auth/tokens/scope \
  -H "Content-Type: application/json" \
  -d '{"name":"Mobile Token","scope":"employee","password":"password"}'
```

### Using Token in Requests
```bash
curl -H "Authorization: Bearer sv_1|token..." \
     -H "Accept: application/json" \
     http://localhost:8000/api/timers
```

### JavaScript Client Example
```javascript
const token = 'sv_1|your_token_here';

const response = await fetch('/api/timers', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});
```

For domain mapping and advanced authentication features, see [System Authentication](../system/authentication-system.md).