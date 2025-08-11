# Token Management API

Laravel Sanctum API token management with granular abilities.

## Features
- **Token CRUD**: Create, list, update, delete API tokens
- **Granular Abilities**: 23+ specific token abilities
- **Predefined Scopes**: employee, manager, mobile-app, admin
- **Token Expiration**: Configurable expiration times

## Authentication
- **Session Auth**: Required for token management

## Endpoints

### Token Management
```http
GET /api/auth/tokens                                   # List user's tokens
POST /api/auth/tokens                                  # Create token
GET /api/auth/tokens/{id}                              # Token details
PUT /api/auth/tokens/{id}                              # Update token
DELETE /api/auth/tokens/{id}                           # Delete token
POST /api/auth/tokens/revoke-all                       # Revoke all tokens
```

### Token Creation Helpers
```http
GET /api/auth/tokens/abilities                         # Available abilities
POST /api/auth/tokens/scope                            # Create scoped token
```

## Token Abilities

### Core Abilities
- `timers:read|write|delete|sync` - Timer management
- `tickets:read|write|account` - Ticket access
- `accounts:read|manage` - Account management
- `widgets:dashboard|configure` - Widget permissions
- `pages:access` - Page access control
- `admin:read|write` - Administrative functions

### Predefined Scopes
```json
{
  "employee": ["timers:read", "timers:write", "tickets:read", "widgets:dashboard"],
  "manager": ["timers:read", "timers:write", "tickets:write", "tickets:account"],
  "mobile-app": ["timers:read", "timers:write", "timers:sync", "tickets:read"],
  "admin": ["*"]
}
```

## Request/Response Format

### Create Token
```http
POST /api/auth/tokens
{
  "name": "Mobile App Token",
  "abilities": ["timers:read", "timers:write", "timers:sync"],
  "expires_at": "2024-12-31T23:59:59Z"
}
```

### Create Scoped Token
```http
POST /api/auth/tokens/scope
{
  "name": "Employee Token",
  "scope": "employee",
  "expires_at": "2024-12-31T23:59:59Z"
}
```

### Token Response
```json
{
  "id": 1,
  "name": "Mobile App Token",
  "abilities": ["timers:read", "timers:write", "timers:sync"],
  "last_used_at": "2024-12-01T09:00:00Z",
  "expires_at": "2024-12-31T23:59:59Z",
  "created_at": "2024-12-01T08:00:00Z",
  "token": "1|abc123..." 
}
```