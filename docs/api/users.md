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
      "accounts_summary": {
        "total_accounts": 3,
        "account_types": {
          "customer": 2,
          "internal": 1
        }
      },
      "roles_summary": {
        "total_roles": 2,
        "contexts": ["service_provider", "account_user"]
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
  "account_ids": [1, 3, 5],
  "role_template_ids": [2, 4],
  "preferences": {
    "notification_email": true,
    "dashboard_layout": "compact"
  }
}
```

**Validation Rules:**

- `name`: required, string, max 255 chars
- `email`: required, email, unique
- `password`: required, min 8 chars, confirmed
- `timezone`: optional, valid timezone string
- `locale`: optional, valid locale code
- `is_active`: optional, boolean (default: true)
- `account_ids`: optional, array of existing account IDs
- `role_template_ids`: optional, array of existing role template IDs
- `preferences`: optional, object

**Example Response:**

```json
{
  "message": "User created successfully",
  "data": {
    "id": 26,
    "name": "Jane Smith",
    "email": "jane@company.com",
    "is_active": true,
    "accounts": [
      {
        "id": 1,
        "name": "ACME Corp",
        "account_type": "customer"
      }
    ],
    "role_templates": [
      {
        "id": 2,
        "name": "Account Manager",
        "context": "service_provider"
      }
    ]
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
    "current_account": {
      "id": 1,
      "name": "ACME Corp",
      "account_type": "customer"
    },
    "accounts": [
      {
        "id": 1,
        "name": "ACME Corp",
        "display_name": "ACME Corporation",
        "account_type": "customer",
        "hierarchy_level": 0,
        "assigned_at": "2025-08-01T10:00:00Z"
      }
    ],
    "role_templates": [
      {
        "id": 2,
        "name": "Account Manager",
        "context": "service_provider",
        "account_id": 1,
        "assigned_at": "2025-08-01T10:00:00Z"
      }
    ],
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
  "account_ids": [1, 2, 4],
  "role_template_ids": [2, 3]
}
```

**Notes:**
- Password fields are optional when updating
- Account and role assignments will be synced (replaced, not merged)
- Omit arrays to leave assignments unchanged

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
    "account_ids": [1, 2, 3],
    "role_template_ids": [2],
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
    "account_ids": [5],
    "role_template_ids": [5],
    "timezone": "America/Los_Angeles"
  }'
```

### Search Active Users

```bash
curl -X GET "/api/users?search=manager&status=active&role_template_id=2" \
  -H "Authorization: Bearer {token}"
```

## Best Practices

### User Creation

1. **Always assign appropriate accounts** - Users need account access to be functional
2. **Choose correct role templates** - Match role templates to user responsibilities
3. **Set proper timezone** - Important for time tracking and scheduling
4. **Use strong passwords** - Enforce password policies in your client application

### User Management

1. **Use soft delete** - Deactivate users instead of hard deleting to preserve data integrity
2. **Monitor user activity** - Regular activity checks help identify inactive accounts
3. **Review permissions regularly** - Audit user permissions and account assignments
4. **Batch operations** - Use filtering to manage multiple users efficiently

### Security Considerations

1. **Validate permissions** - Always check user permissions before allowing access
2. **Audit sensitive operations** - Log user creation, permission changes, and deletions
3. **Monitor failed attempts** - Track authentication failures and suspicious activity
4. **Regular access reviews** - Periodic review of user accounts and permissions

---

**Last Updated**: August 11, 2025  
**Status**: ✅ Complete - Full user management API with dual context support