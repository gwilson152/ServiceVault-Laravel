# Users API

Comprehensive user management API with account assignment and role management capabilities.

## Overview

The Users API provides complete user administration functionality including user creation, editing, account assignments, role template management, and activity tracking. It supports both service provider staff and customer account user management with proper permission-based access control.

## Authentication

All user management endpoints require authentication and appropriate permissions:

- **Basic Access**: `users.view` - View user information
- **User Management**: `users.manage` or `admin.write` - Full user administration
- **Advanced Access**: `admin.read` - Access to comprehensive user details

## User Endpoints

### List Users

```http
GET /api/users
```

Retrieve a paginated list of users with advanced filtering and search capabilities.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `search` | string | Search in name and email fields |
| `status` | string | Filter by status: `active`, `inactive` |
| `role_template_id` | integer | Filter by assigned role template |
| `account_id` | integer | Filter by account assignment |
| `sort` | string | Sort field: `name`, `email`, `created_at`, `last_active_at` |
| `direction` | string | Sort direction: `asc`, `desc` |
| `per_page` | integer | Results per page (default: 15, max: 100) |
| `page` | integer | Page number |

**Example Request:**

```bash
curl -X GET "/api/users?search=john&status=active&sort=name&direction=asc&per_page=20" \
  -H "Authorization: Bearer {token}"
```

**Example Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "John Doe",
      "email": "john@company.com",
      "is_active": true,
      "is_super_admin": false,
      "timezone": "America/New_York",
      "locale": "en",
      "last_active_at": "2025-08-11T15:30:00Z",
      "created_at": "2025-08-01T10:00:00Z",
      "account_id": 1,
      "account": {
        "id": 1,
        "name": "ACME Corp",
        "display_name": "ACME Corporation",
        "account_type": "customer",
        "is_active": true
      },
      "role_template_id": 2,
      "role_template": {
        "id": 2,
        "name": "Account Manager",
        "context": "service_provider",
        "is_system_role": false,
        "is_super_admin": false
      },
      "statistics": {
        "total_assigned_tickets": 15,
        "total_time_entries": 45,
        "active_timers_count": 1
      }
    }
  ],
  "meta": {
    "total": 25,
    "current_page": 1,
    "last_page": 2,
    "per_page": 20
  }
}
```

### Create User

```http
POST /api/users
```

Create a new user with account assignments and role templates.

**Request Body:**

```json
{
  "name": "Jane Smith",
  "email": "jane@company.com",
  "password": "secure_password",
  "password_confirmation": "secure_password",
  "timezone": "America/Los_Angeles",
  "locale": "en",
  "is_active": true,
  "is_visible": true,
  "account_id": 1,
  "role_template_id": 2,
  "send_invitation": false,
  "preferences": {
    "notification_email": true,
    "dashboard_layout": "compact"
  }
}
```

**Invitation Workflow Example:**

```json
{
  "name": "New Employee",
  "email": "new.employee@company.com",
  "timezone": "America/New_York",
  "locale": "en",
  "is_active": true,
  "is_visible": true,
  "account_id": 1,
  "role_template_id": 3,
  "send_invitation": true
}
```

**Inactive Placeholder User Example:**

```json
{
  "name": "Future Employee",
  "timezone": "America/Chicago",
  "locale": "en",
  "is_active": false,
  "is_visible": true,
  "account_id": 1,
  "role_template_id": 2
}
```

**Dynamic Validation Rules:**

```php
// Email requirement depends on user state and workflow
$emailRequired = $request->boolean('is_active', true) && !$request->boolean('send_invitation', false);
$passwordRequired = !$request->boolean('send_invitation') && $request->boolean('is_active', true);
```

**Field Requirements:**

- `name`: **required**, string, max 255 chars
- `email`: **conditional** - required for active users unless using invitation workflow
- `password`: **conditional** - required for direct creation of active users
- `password_confirmation`: required when password provided
- `timezone`: optional, valid timezone string (default: 'UTC')
- `locale`: optional, valid locale code (default: 'en')
- `is_active`: optional, boolean (default: true)
- `is_visible`: optional, boolean (default: true)
- `send_invitation`: optional, boolean (default: false)
- `account_id`: optional, existing account ID
- `role_template_id`: optional, existing role template ID
- `preferences`: optional, object

**Validation Matrix:**

| User Type | Active | Send Invitation | Email Required | Password Required |
|-----------|--------|-----------------|----------------|-----------------|
| **Standard User** | ✅ | ❌ | ✅ Required | ✅ Required |
| **Invitation User** | ✅ | ✅ | ✅ Required | ❌ Optional |
| **Inactive User** | ❌ | N/A | ❌ Optional | ❌ Optional |

**Example Response:**

```json
{
  "message": "User created successfully",
  "data": {
    "id": 26,
    "name": "Jane Smith",
    "email": "jane@company.com",
    "is_active": true,
    "account": {
      "id": 1,
      "name": "ACME Corp",
      "display_name": "ACME Corporation",
      "account_type": "customer",
      "is_active": true
    },
    "role_template": {
      "id": 2,
      "name": "Account Manager",
      "context": "service_provider",
      "is_system_role": false,
      "is_super_admin": false
    }
  }
}
```

### Get User Details

```http
GET /api/users/{user}
```

Retrieve comprehensive user information including relationships, statistics, and recent activity.

**Example Response:**

```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@company.com",
    "is_active": true,
    "is_super_admin": false,
    "timezone": "America/New_York",
    "locale": "en",
    "account_id": 1,
    "account": {
      "id": 1,
      "name": "ACME Corp",
      "display_name": "ACME Corporation",
      "company_name": "ACME Corporation",
      "account_type": "customer",
      "is_active": true,
      "hierarchy_level": 0
    },
    "role_template_id": 2,
    "role_template": {
      "id": 2,
      "name": "Account Manager",
      "description": "Manages customer account relationships",
      "context": "service_provider",
      "is_system_role": false,
      "is_super_admin": false,
      "is_modifiable": true
    },
    "statistics": {
      "total_assigned_tickets": 15,
      "total_created_tickets": 3,
      "total_time_entries": 45,
      "total_time_logged": 144000,
      "active_timers_count": 1
    },
    "recent_timers": [
      {
        "id": 5,
        "description": "Website maintenance",
        "status": "running",
        "duration": 3600
      }
    ]
  }
}
```

### Update User

```http
PUT /api/users/{user}
```

Update user information, account assignments, and role templates.

**Request Body:**

```json
{
  "name": "John Smith",
  "email": "john.smith@company.com",
  "password": "new_password",
  "password_confirmation": "new_password",
  "timezone": "America/Chicago",
  "is_active": true,
  "is_visible": true,
  "account_id": 1,
  "role_template_id": 2
}
```

**Update Validation Rules:**

```php
// For updates, email is always optional but unique
$rules = [
    'name' => 'required|string|max:255',
    'email' => ['nullable', 'email', Rule::unique('users')->ignore($user->id)],
    'password' => 'nullable|string|min:8|confirmed', // Always optional for updates
    // ... other fields
];
```

**Notes:**
- **Email field** is optional for updates but must be unique when provided
- **Password fields** are always optional when updating (leave empty to keep current password)
- **Account and role assignments** will be updated with new values
- **Omit fields** to leave current values unchanged
- **Email removal**: Set email to null for inactive users (requires `is_active: false`)

### Deactivate User

```http
DELETE /api/users/{user}
```

Deactivate a user account (soft delete). The user will be set to inactive status and cannot log in.

**Response:**

```json
{
  "message": "User deactivated successfully"
}
```

## User Detail Endpoints

### User Tickets

```http
GET /api/users/{user}/tickets
```

Get service tickets assigned to the user, with pagination and relationships.

**Example Response:**

```json
{
  "data": [
    {
      "id": 123,
      "title": "Website Performance Issues",
      "status": "in_progress",
      "priority": "high",
      "account": {
        "id": 1,
        "name": "ACME Corp"
      },
      "created_at": "2025-08-10T09:00:00Z"
    }
  ],
  "meta": {
    "total": 15,
    "current_page": 1,
    "last_page": 1
  }
}
```

### User Time Entries

```http
GET /api/users/{user}/time-entries
```

Get time entries created by the user, with pagination and billing information.

**Example Response:**

```json
{
  "data": [
    {
      "id": 456,
      "description": "Bug fixing",
      "duration": 3600,
      "duration_formatted": "1:00:00",
      "billable": true,
      "status": "approved",
      "calculated_cost": 125.00,
      "started_at": "2025-08-10T14:00:00Z"
    }
  ],
  "meta": {
    "total": 45,
    "current_page": 1,
    "last_page": 3
  }
}
```

### User Activity

```http
GET /api/users/{user}/activity
```

Get comprehensive user activity analytics and recent activity summary.

**Example Response:**

```json
{
  "data": {
    "recent_logins": {
      "last_login_at": "2025-08-11T08:30:00Z",
      "last_active_at": "2025-08-11T15:45:00Z"
    },
    "statistics": {
      "total_tickets_assigned": 15,
      "total_time_entries": 45,
      "total_time_logged": 144000,
      "active_timers": 1
    },
    "recent_activity": {
      "recent_tickets": [
        {
          "id": 123,
          "title": "Website Performance Issues",
          "status": "in_progress"
        }
      ],
      "recent_time_entries": [
        {
          "id": 456,
          "description": "Bug fixing",
          "duration": 3600,
          "status": "approved"
        }
      ],
      "recent_timers": [
        {
          "id": 5,
          "description": "Website maintenance",
          "status": "running",
          "duration": 3600
        }
      ]
    }
  }
}
```

### User Account Assignments

```http
GET /api/users/{user}/accounts
```

Get accounts assigned to the user with role context information.

**Example Response:**

```json
{
  "data": [
    {
      "id": 1,
      "name": "ACME Corp",
      "display_name": "ACME Corporation",
      "account_type": "customer",
      "hierarchy_level": 0,
      "users_count": 5,
      "role_templates": [
        {
          "id": 2,
          "name": "Account Manager",
          "context": "service_provider"
        }
      ]
    }
  ]
}
```

## Agent Assignment Endpoints

Service Vault includes specialized endpoints for agent assignment with feature-specific permission filtering.

### Get Available Agents

```http
GET /api/users/agents
```

Retrieve users who can act as agents for specific features, with multi-layer permission filtering.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `agent_type` | string | Feature type: `timer`, `ticket`, `time`, `billing` (default: `timer`) |
| `account_id` | integer | Filter by account assignment (optional) |
| `search` | string | Search in name and email fields |
| `per_page` | integer | Results per page (default: 100) |

**Agent Types & Permissions:**

| Agent Type | Required Permission | Fallback Permissions |
|------------|-------------------|---------------------|
| `timer` | `timers.act_as_agent` | `timers.write`, `timers.manage` |
| `ticket` | `tickets.act_as_agent` | `tickets.assign`, `tickets.manage` |
| `time` | `time.act_as_agent` | `time.track`, `time.manage` |
| `billing` | `billing.act_as_agent` | `billing.manage`, `billing.admin` |

**Agent Determination Logic:**

1. **Primary**: Users with `user_type = 'agent'`
2. **Secondary**: Users with feature-specific `*.act_as_agent` permission
3. **Tertiary**: Internal account users with relevant fallback permissions

**Example Request:**

```bash
curl -X GET "/api/users/agents?agent_type=timer&account_id=1&search=john" \
  -H "Authorization: Bearer {token}"
```

**Example Response:**

```json
{
  "data": [
    {
      "id": 5,
      "name": "John Smith", 
      "email": "john@serviceprovider.com",
      "user_type": "agent",
      "is_active": true,
      "account": {
        "id": 1,
        "name": "Service Provider Inc",
        "account_type": "internal"
      },
      "role_template": {
        "id": 3,
        "name": "Service Agent",
        "permissions": ["timers.act_as_agent", "timers.write"]
      },
      "agent_priority": 1
    }
  ],
  "message": "Available agents retrieved successfully"
}
```

**Required Permissions:** `timers.admin`, `time.admin`, `admin.write`

### Get Ticket Assignable Users

```http
GET /api/users/assignable
```

Retrieve users who can be assigned to tickets, using `tickets.act_as_agent` permission.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `account_id` | integer | Filter by account assignment (optional) |
| `search` | string | Search in name and email fields |

**Example Response:**

```json
{
  "data": [
    {
      "id": 7,
      "name": "Sarah Johnson",
      "email": "sarah@serviceprovider.com", 
      "user_type": "agent",
      "account": {
        "account_type": "internal"
      },
      "role_template": {
        "permissions": ["tickets.act_as_agent", "tickets.manage"]
      }
    }
  ],
  "message": "Assignable users retrieved successfully"
}
```

**Required Permissions:** `tickets.assign`, `admin.write`

### Get Billing Agents

```http
GET /api/users/billing-agents
```

Retrieve users who can be responsible for billing operations, using `billing.act_as_agent` permission.

**Query Parameters:**

| Parameter | Type | Description |
|-----------|------|-------------|
| `account_id` | integer | Filter by account assignment (optional) |
| `search` | string | Search in name and email fields |

**Example Response:**

```json
{
  "data": [
    {
      "id": 9,
      "name": "Mike Wilson",
      "email": "mike@serviceprovider.com",
      "user_type": "agent", 
      "account": {
        "account_type": "internal"
      },
      "role_template": {
        "permissions": ["billing.act_as_agent", "billing.manage"]
      }
    }
  ],
  "message": "Available billing agents retrieved successfully"
}
```

**Required Permissions:** `billing.admin`, `billing.manage`, `admin.write`

## Error Responses

The API returns standard HTTP status codes and error messages:

### 403 Forbidden

```json
{
  "message": "Insufficient permissions to view users"
}
```

### 404 Not Found

```json
{
  "message": "User not found"
}
```

### 422 Unprocessable Entity

```json
{
  "message": "Validation failed",
  "errors": {
    "email": ["The email has already been taken."],
    "password": ["The password confirmation does not match."]
  }
}
```

## User Context & Permissions

### Service Provider Context

Service provider staff can:
- View all users in the system
- Create and manage customer account users
- Assign users to multiple accounts
- View cross-account user activity

### Account User Context

Account users have limited access:
- View users within their account hierarchy
- Limited user creation capabilities
- Account-scoped user management only

### Permission Requirements

| Action | Required Permissions |
|--------|---------------------|
| List users | `users.view`, `admin.read` |
| Create users | `users.create`, `users.manage`, `admin.write` |
| Update users | `users.edit`, `users.manage`, `admin.write` |
| Delete users | `users.delete`, `users.manage`, `admin.write` |
| View user details | `users.view`, `admin.read` |
| View user activity | `users.view`, `admin.read` |

## Integration Examples

### Create Service Provider User

```bash
curl -X POST "/api/users" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Service Manager",
    "email": "manager@serviceprovider.com",
    "password": "secure_password",
    "password_confirmation": "secure_password",
    "account_id": 1,
    "role_template_id": 2,
    "timezone": "America/New_York"
  }'
```

### Create Customer Account User

```bash
curl -X POST "/api/users" \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Customer Admin",
    "email": "admin@customercorp.com",
    "password": "secure_password",
    "password_confirmation": "secure_password",
    "account_id": 5,
    "role_template_id": 5,
    "timezone": "America/Los_Angeles"
  }'
```

### Search Active Users

```bash
curl -X GET "/api/users?search=manager&status=active&role_template_id=2" \
  -H "Authorization: Bearer {token}"
```

### Get Timer Agents for Account

```bash
curl -X GET "/api/users/agents?agent_type=timer&account_id=5" \
  -H "Authorization: Bearer {token}"
```

### Get Available Billing Agents

```bash
curl -X GET "/api/users/billing-agents?search=manager" \
  -H "Authorization: Bearer {token}"
```

## Best Practices

### User Creation

1. **Always assign an appropriate account** - Users need account access to be functional
2. **Choose correct role template** - Match role template to user responsibilities
3. **Set proper timezone** - Important for time tracking and scheduling
4. **Use strong passwords** - Enforce password policies in your client application

### User Management

1. **Use soft delete** - Deactivate users instead of hard deleting to preserve data integrity
2. **Monitor user activity** - Regular activity checks help identify inactive accounts
3. **Review permissions regularly** - Audit user permissions and account assignment
4. **Batch operations** - Use filtering to manage multiple users efficiently

### Security Considerations

1. **Validate permissions** - Always check user permissions before allowing access
2. **Audit sensitive operations** - Log user creation, permission changes, and deletions
3. **Monitor failed attempts** - Track authentication failures and suspicious activity
4. **Regular access reviews** - Periodic review of user accounts and permissions

---

**Last Updated**: August 14, 2025  
**Status**: ✅ Complete - Full user management API with nullable email support, conditional validation, and invitation workflow integration