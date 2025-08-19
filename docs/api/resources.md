# Core Resources API

REST API endpoints for Service Vault's core resources: tickets, timers, time entries, users, and accounts.

## Tickets API

### List Tickets
```http
GET /api/tickets?page=1&per_page=20&account_id={id}&status=open
```

**Query Parameters**:
- `page`, `per_page`: Pagination
- `account_id`: Filter by account
- `status`: Filter by status (open, in_progress, resolved, etc.)
- `priority`: Filter by priority (low, normal, high, urgent)
- `category`: Filter by category
- `assigned_to`: Filter by assigned agent
- `search`: Search titles and descriptions

**Response**:
```json
{
  "data": [
    {
      "id": "ticket-uuid",
      "ticket_number": "TKT-1001",
      "title": "Server Performance Issue",
      "description": "Database queries running slowly",
      "status": "in_progress",
      "priority": "high",
      "category": "support",
      "account": {
        "id": "account-uuid",
        "name": "Company Account"
      },
      "assigned_to": {
        "id": "user-uuid", 
        "name": "John Doe"
      },
      "created_at": "2025-08-19T10:30:00Z",
      "due_date": "2025-08-25T17:00:00Z",
      "timers": [
        {
          "id": "timer-uuid",
          "status": "running",
          "duration": 3600,
          "user": {"id": "user-uuid", "name": "Jane Smith"}
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 20,
    "total": 150
  }
}
```

### Create Ticket
```http
POST /api/tickets
Content-Type: application/json

{
  "title": "New Support Request",
  "description": "Detailed description of the issue",
  "account_id": "account-uuid",
  "customer_id": "customer-uuid",
  "agent_id": "agent-uuid", 
  "priority": "normal",
  "category": "support",
  "due_date": "2025-08-30T17:00:00Z",
  "tags": "urgent,database,performance"
}
```

### Update Ticket
```http
PUT /api/tickets/{ticket_id}
Content-Type: application/json

{
  "title": "Updated Title",
  "status": "in_progress",
  "priority": "high",
  "agent_id": "new-agent-uuid"
}
```

### Ticket Comments
```http
GET /api/tickets/{ticket_id}/comments
POST /api/tickets/{ticket_id}/comments

{
  "comment": "This issue has been resolved",
  "is_internal": false
}
```

## Timers API

### List User Timers
```http
GET /api/timers?page=1&status=running&account_id={id}
```

**Query Parameters**:
- `status`: running, paused, stopped
- `account_id`: Filter by account
- `ticket_id`: Filter by ticket
- `user_id`: Filter by user (if authorized)

### Start Timer
```http
POST /api/timers
Content-Type: application/json

{
  "description": "Working on database optimization",
  "account_id": "account-uuid",
  "ticket_id": "ticket-uuid", 
  "user_id": "user-uuid",
  "billing_rate_id": "rate-uuid",
  "stop_others": false
}
```

**Recent Fix**: `ticket_id` and `account_id` now properly save due to enhanced validation rules.

### Timer Controls
```http
POST /api/timers/{timer_id}/pause      # Pause running timer
POST /api/timers/{timer_id}/resume     # Resume paused timer  
POST /api/timers/{timer_id}/stop       # Stop timer
POST /api/timers/{timer_id}/commit     # Stop and convert to time entry
```

### Update Timer
```http
PUT /api/timers/{timer_id}
Content-Type: application/json

{
  "description": "Updated task description", 
  "account_id": "new-account-uuid",
  "ticket_id": "new-ticket-uuid",
  "billing_rate_id": "new-rate-uuid"
}
```

**Recent Fix**: Timer edit operations now properly update all fields including relationships.

### Active Timers
```http
GET /api/timers/active/current         # All active timers with totals
```

**Response**:
```json
{
  "data": [
    {
      "id": "timer-uuid",
      "description": "Database optimization work",
      "status": "running",
      "duration": 3600,
      "duration_formatted": "1:00:00",
      "calculated_amount": 125.00,
      "billing_rate": {
        "id": "rate-uuid",
        "rate": "125.00"
      },
      "account": {
        "id": "account-uuid", 
        "name": "Client Company"
      },
      "ticket": {
        "id": "ticket-uuid",
        "ticket_number": "TKT-1001",
        "title": "Performance Issues"
      }
    }
  ],
  "totals": {
    "running_timers": 3,
    "total_duration": 10800,
    "total_amount": 450.00
  }
}
```

### Bulk Timer Operations
```http
POST /api/timers/bulk
Content-Type: application/json

{
  "action": "stop",
  "timer_ids": ["uuid1", "uuid2", "uuid3"],
  "convert_to_entries": true
}
```

## Time Entries API

### List Time Entries
```http
GET /api/time-entries?start_date=2025-08-01&end_date=2025-08-31&status=pending
```

**Query Parameters**:
- `start_date`, `end_date`: Date range filter
- `status`: pending, approved, rejected
- `account_id`: Filter by account
- `user_id`: Filter by user
- `billable`: true/false filter

### Create Time Entry
```http
POST /api/time-entries
Content-Type: application/json

{
  "account_id": "account-uuid",
  "ticket_id": "ticket-uuid",
  "user_id": "user-uuid", 
  "billing_rate_id": "rate-uuid",
  "description": "Manual time entry",
  "started_at": "2025-08-19T09:00:00Z",
  "ended_at": "2025-08-19T10:30:00Z",
  "duration": 5400,
  "billable": true
}
```

### Approval Workflow
```http
POST /api/time-entries/{entry_id}/approve
POST /api/time-entries/{entry_id}/reject

{
  "notes": "Approved for billing"
}
```

### Bulk Approvals
```http
POST /api/time-entries/bulk/approve
Content-Type: application/json

{
  "entry_ids": ["uuid1", "uuid2", "uuid3"],
  "notes": "Batch approval for weekly billing"
}
```

## Users API

### List Users
```http
GET /api/users?role=agent&account_id={id}&active=true
```

**Query Parameters**:
- `role`: Filter by user type (agent, manager, account_user, etc.)
- `account_id`: Filter by account
- `active`: true/false for active users only
- `search`: Search by name or email

### Agent Lists (Feature-Specific)
```http
GET /api/users/agents                           # All available agents
GET /api/users/agents?agent_type=timer          # Timer-specific agents  
GET /api/users/agents?agent_type=ticket         # Ticket-specific agents
GET /api/users/agents?agent_type=time           # Time entry agents
GET /api/users/agents?agent_type=billing        # Billing agents
```

**Recent Enhancement**: Feature-specific agent permissions allow granular control over who can act as agents for different system features.

### Create User
```http
POST /api/users
Content-Type: application/json

{
  "name": "New User",
  "email": "user@company.com",
  "user_type": "agent",
  "account_id": "account-uuid",
  "role_template_id": "role-uuid",
  "is_active": true,
  "send_invitation": true
}
```

**Recent Enhancement**: Email is now optional - users can be created without email for placeholder/service accounts.

## Accounts API

### List Accounts
```http
GET /api/accounts?search=company&active=true
```

### Account Users
```http
GET /api/accounts/{account_id}/users?role_context=account_user
```

### Create Account
```http
POST /api/accounts
Content-Type: application/json

{
  "name": "New Company Account",
  "description": "B2B client account", 
  "parent_id": "parent-account-uuid",
  "settings": {
    "billing_terms": 30,
    "default_billing_rate_id": "rate-uuid"
  },
  "is_active": true
}
```

## Error Handling

### Validation Errors (422)
```json
{
  "message": "The given data was invalid",
  "errors": {
    "title": ["The title field is required"],
    "account_id": ["The selected account does not exist"]
  }
}
```

### Authorization Errors (403)
```json
{
  "message": "This action is unauthorized",
  "required_permission": "tickets.create"
}
```

### Not Found Errors (404)
```json
{
  "message": "Resource not found",
  "resource_type": "Ticket",
  "resource_id": "invalid-uuid"
}
```

## Response Format

All API responses follow a consistent structure:

### Success Response
```json
{
  "data": { /* resource data */ },
  "message": "Operation completed successfully"
}
```

### Collection Response  
```json
{
  "data": [ /* array of resources */ ],
  "meta": {
    "current_page": 1,
    "per_page": 20, 
    "total": 100,
    "last_page": 5
  }
}
```

### Error Response
```json
{
  "message": "Error description",
  "errors": { /* validation errors if applicable */ },
  "code": "ERROR_CODE"
}
```

## Rate Limiting

- **General API**: 60 requests per minute per user
- **Search endpoints**: 30 requests per minute per user
- **Bulk operations**: 10 requests per minute per user
- **File uploads**: 5 requests per minute per user

Rate limit headers included in all responses:
```http
X-RateLimit-Limit: 60
X-RateLimit-Remaining: 45
X-RateLimit-Reset: 1629876543
```