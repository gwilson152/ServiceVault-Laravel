# Authentication API

Service Vault uses **Laravel Sanctum** for hybrid authentication supporting both session-based web authentication and token-based API access.

## Authentication Methods

### 1. Session Authentication (Web Interface)

**Login Endpoint**:
```http
POST /login
Content-Type: application/json

{
  "email": "user@company.com",
  "password": "password",
  "remember": true
}
```

**Response**:
```json
{
  "user": {
    "id": "uuid",
    "name": "John Doe",
    "email": "user@company.com",
    "user_type": "agent",
    "account_id": "account-uuid",
    "permissions": ["tickets.view", "timers.create", "..."]
  },
  "redirect": "/dashboard"
}
```

**Session Management**:
- Sessions stored in Redis with 120-minute default lifetime
- Automatic CSRF token refresh every 10 minutes
- Proactive token refresh on 419 errors with automatic retry

### 2. API Token Authentication

**Create API Token**:
```http
POST /api/auth/tokens
Authorization: Bearer {existing-token}
Content-Type: application/json

{
  "name": "Mobile App Token",
  "abilities": ["tickets:read", "timers:write", "time-entries:read"],
  "expires_at": "2024-12-31T23:59:59Z"
}
```

**Response**:
```json
{
  "token": "1|abc123...",
  "name": "Mobile App Token", 
  "abilities": ["tickets:read", "timers:write", "time-entries:read"],
  "expires_at": "2024-12-31T23:59:59Z"
}
```

**Token Usage**:
```http
GET /api/tickets
Authorization: Bearer 1|abc123...
```

## Token Abilities

Service Vault uses **23 granular token abilities** for fine-grained API access control:

### Core Resource Abilities
```
tickets:read, tickets:write, tickets:delete
timers:read, timers:write, timers:delete  
time-entries:read, time-entries:write, time-entries:delete
users:read, users:write, users:delete
accounts:read, accounts:write, accounts:delete
```

### Billing & Financial Abilities
```
billing-rates:read, billing-rates:write, billing-rates:delete
invoices:read, invoices:write, invoices:delete
payments:read, payments:write
```

### Administrative Abilities
```
admin:read, admin:write
system:configure
```

## Token Management

### List User Tokens
```http
GET /api/auth/tokens
Authorization: Bearer {token}
```

### Revoke Token
```http
DELETE /api/auth/tokens/{token_id}
Authorization: Bearer {token}
```

### Revoke All Tokens
```http
POST /api/auth/tokens/revoke-all
Authorization: Bearer {token}
```

### Create Scoped Token
```http
POST /api/auth/tokens/scope
Authorization: Bearer {token}
Content-Type: application/json

{
  "name": "Read-Only Access",
  "scope": "employee",
  "expires_in": 86400
}
```

**Available Scopes**:
- `employee`: Basic read access to own resources
- `manager`: Team management and approval abilities  
- `admin`: Administrative access to all resources

### Get Available Token Abilities
```http
GET /api/auth/tokens/abilities
Authorization: Bearer {token}
```

**Response**:
```json
{
  "abilities": [
    "tickets:read", "tickets:write", "tickets:delete",
    "timers:read", "timers:write", "timers:delete",
    "time-entries:read", "time-entries:write", "time-entries:delete",
    "users:read", "users:write", "users:delete",
    "accounts:read", "accounts:write", "accounts:delete",
    "billing-rates:read", "billing-rates:write", "billing-rates:delete",
    "invoices:read", "invoices:write", "invoices:delete",
    "payments:read", "payments:write",
    "admin:read", "admin:write",
    "system:configure"
  ]
}
```

## Domain-Based Assignment

**Automatic User-to-Account Mapping**:

Service Vault can automatically assign users to accounts based on email domain:

### Configuration
```http
GET /api/domain-mappings
POST /api/domain-mappings
Content-Type: application/json

{
  "domain_pattern": "*.company.com",
  "account_id": "account-uuid",
  "priority": 1,
  "is_active": true
}
```

### Preview Assignment
```http
POST /api/domain-mappings/preview
Content-Type: application/json

{
  "email": "user@company.com"
}
```

**Response**:
```json
{
  "matched_mapping": {
    "id": "mapping-uuid",
    "domain_pattern": "*.company.com", 
    "account": {
      "id": "account-uuid",
      "name": "Company Account"
    }
  }
}
```

### Domain Validation
```http
GET /api/domain-mappings/validate/requirements # System validation
```

## Real-Time Broadcasting

### WebSocket Channel Authorization
```http
GET /broadcasting/auth              # WebSocket channel authorization (session-based)
```

**Private Channels**:
- `user.{id}`: Personal notifications and updates
- `account.{id}`: Account-wide notifications  
- `ticket.{id}`: Ticket-specific real-time updates

**Events**:
- `comment.created`: New ticket comments
- `timer.started`, `timer.stopped`: Timer state changes
- `ticket.updated`: Status/assignment changes

## CSRF Protection

**Web Interface CSRF**:
- Automatic CSRF token inclusion in Axios requests
- Proactive refresh every 10 minutes  
- Automatic retry on 419 errors
- No manual page refresh required

**CSRF Token Refresh**:
```javascript
// Available globally
window.refreshCSRFToken();
```

## Error Handling

### Authentication Errors

**401 Unauthorized**:
```json
{
  "message": "Unauthenticated"
}
```

**419 CSRF Token Mismatch** (automatically handled):
```json
{
  "message": "CSRF token mismatch"
}
```

**403 Forbidden**:
```json
{
  "message": "This action is unauthorized",
  "required_permission": "tickets.create"
}
```

### Token Errors

**Invalid Token**:
```json
{
  "message": "Invalid token",
  "code": "TOKEN_INVALID"
}
```

**Expired Token**:
```json
{
  "message": "Token has expired", 
  "code": "TOKEN_EXPIRED"
}
```

**Insufficient Abilities**:
```json
{
  "message": "Token lacks required ability",
  "required_ability": "tickets:write",
  "code": "INSUFFICIENT_ABILITIES"
}
```

## Security Best Practices

### Token Security
- **Short-lived tokens**: Use reasonable expiration times
- **Minimal abilities**: Grant only required abilities
- **Regular rotation**: Rotate tokens periodically
- **Secure storage**: Never log or expose tokens in frontend

### Session Security
- **HTTPS only**: Always use HTTPS in production
- **Secure cookies**: Session cookies marked secure/httponly
- **Session timeout**: Automatic logout after inactivity
- **Device tracking**: Monitor unusual login patterns

### API Security
- **Rate limiting**: API endpoints have rate limits
- **Input validation**: All inputs validated and sanitized
- **Permission checks**: Every endpoint checks permissions
- **Audit logging**: All authentication events logged

## Integration Examples

### Frontend (Vue.js/Axios)
```javascript
// Automatic session authentication
import axios from 'axios'

// API calls automatically include CSRF token and session
const response = await axios.get('/api/tickets')
```

### Mobile App (Token-based)
```javascript
// Token-based authentication
const token = await getStoredToken()

const response = await fetch('/api/tickets', {
  headers: {
    'Authorization': `Bearer ${token}`,
    'Accept': 'application/json'
  }
})
```

### External Integration
```bash
# Create dedicated API token
curl -X POST https://servicevault.com/api/auth/tokens \
  -H "Authorization: Bearer ${ADMIN_TOKEN}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "External Integration",
    "abilities": ["tickets:read", "tickets:write"],
    "expires_at": "2024-12-31T23:59:59Z"
  }'
```