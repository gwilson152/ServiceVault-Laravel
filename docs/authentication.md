# Service Vault Authentication Guide

## Overview

Service Vault uses a hybrid authentication system built on Laravel Breeze (session auth) and Laravel Sanctum (API token auth), providing secure access for both web users and external applications.

## Authentication Methods

### 1. Session Authentication (Web Interface)
- **Usage**: Web dashboard, admin panels, user-facing interfaces
- **Technology**: Laravel Breeze with Inertia.js
- **Flow**: Traditional login → session cookie → CSRF protection
- **Endpoints**: `/login`, `/register`, `/forgot-password`, etc.

### 2. Token Authentication (API Access)
- **Usage**: Mobile apps, external integrations, API clients
- **Technology**: Laravel Sanctum with bearer tokens
- **Flow**: Generate token → Include in `Authorization: Bearer {token}` header
- **Security**: Token abilities, expiration, revocation

## API Token Management

### Creating Tokens

#### Option 1: Custom Abilities
```bash
POST /api/auth/tokens
Authorization: Bearer existing_token (or session auth)
Content-Type: application/json

{
    "name": "Mobile App Token",
    "abilities": ["timers:read", "timers:write", "timers:sync"],
    "expires_at": "2024-12-31T23:59:59Z",
    "password": "user_password"
}
```

#### Option 2: Predefined Scopes
```bash
POST /api/auth/tokens/scope
Authorization: Bearer existing_token (or session auth)
Content-Type: application/json

{
    "name": "Employee Mobile Access",
    "scope": "employee",
    "expires_at": "2024-12-31T23:59:59Z", 
    "password": "user_password"
}
```

### Available Scopes

| Scope | Description | Abilities |
|-------|-------------|-----------|
| `employee` | Basic user access | Timer management, time entries, project viewing |
| `manager` | Team oversight | Employee abilities + team management, approvals |
| `mobile-app` | Mobile application | Full mobile functionality with sync |
| `admin` | Administrative | Complete system access |

### Token Abilities

#### Timer System
- `timers:read` - View timer data
- `timers:write` - Create and update timers
- `timers:delete` - Delete timers
- `timers:sync` - Cross-device synchronization

#### Projects & Tasks
- `projects:read` - View projects
- `projects:write` - Manage projects
- `tasks:read` - View tasks
- `tasks:write` - Manage tasks

#### Time Entries
- `time-entries:read` - View time entries
- `time-entries:write` - Create and update time entries
- `time-entries:delete` - Delete time entries

#### Administrative
- `admin:read` - Administrative read access
- `admin:write` - Administrative write access
- `users:read` - View user data
- `users:write` - Manage users

## Using API Tokens

### Authentication Header
```bash
curl -H "Authorization: Bearer sv_1|abc123def456..." \
     -H "Accept: application/json" \
     https://servicevault.com/api/timers
```

### JavaScript Example
```javascript
const token = 'sv_1|abc123def456...';

const response = await fetch('/api/timers', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

const timers = await response.json();
```

### Mobile App Integration
```javascript
// Create app-specific token
const tokenResponse = await fetch('/api/auth/tokens/scope', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        // Use session auth for token creation
    },
    body: JSON.stringify({
        name: 'iPhone App - John Doe',
        scope: 'mobile-app',
        password: userPassword
    })
});

const { token } = await tokenResponse.json();

// Store token securely in app
localStorage.setItem('api_token', token);

// Use for all subsequent API calls
const apiClient = {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
};
```

## Token Management Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `GET` | `/api/auth/tokens` | List user's tokens |
| `POST` | `/api/auth/tokens` | Create custom token |
| `POST` | `/api/auth/tokens/scope` | Create scoped token |
| `GET` | `/api/auth/tokens/abilities` | Get available abilities |
| `GET` | `/api/auth/tokens/{id}` | View specific token |
| `PUT` | `/api/auth/tokens/{id}` | Update token |
| `DELETE` | `/api/auth/tokens/{id}` | Delete token |
| `DELETE` | `/api/auth/tokens/revoke-all` | Revoke all tokens |

## Security Best Practices

### Token Security
- **Store Securely**: Never store tokens in browser localStorage for web apps
- **Use HTTPS**: Always transmit tokens over encrypted connections
- **Set Expiration**: Configure appropriate token lifetimes
- **Rotate Regularly**: Refresh tokens periodically
- **Revoke Compromised**: Immediately revoke suspected compromised tokens

### Ability Scoping
- **Principle of Least Privilege**: Grant only necessary abilities
- **Regular Audits**: Review token usage and abilities
- **Scope Validation**: Use predefined scopes when possible
- **Monitor Usage**: Track token activity and unusual patterns

### Password Protection
- **Token Creation**: Always require password confirmation
- **Sensitive Operations**: Re-authenticate for critical actions
- **Account Security**: Use strong passwords and 2FA when available

## Testing & Development

### Local Development
```bash
# Create test token via Tinker
php artisan tinker

# In Tinker:
$user = App\Models\User::find(1);
$token = $user->createToken('Development Token', ['timers:read', 'timers:write']);
echo $token->plainTextToken;
```

### Testing API Endpoints
```bash
# Test token creation
curl -X POST http://localhost:8000/api/auth/tokens/scope \
  -H "Content-Type: application/json" \
  -d '{"name":"Test Token","scope":"employee","password":"password"}' \
  --cookie-jar cookies.txt \
  --cookie cookies.txt

# Test authenticated request
curl -H "Authorization: Bearer $TOKEN" \
  http://localhost:8000/api/timers
```

## Future Features

- **Client Monitoring API**: External monitoring applications
- **Webhook Integration**: Event-driven integrations
- **IP Whitelisting**: Token-based IP restrictions
- **Rate Limiting**: Token-specific rate limits
- **Advanced Scoping**: Custom ability combinations

For implementation details, see the [Laravel Sanctum documentation](https://laravel.com/docs/sanctum).