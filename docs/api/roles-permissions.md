# Roles & Permissions API

REST API endpoints for managing role templates, permissions, and dashboard configurations in Service Vault's three-dimensional permission system.

## Authentication

All role and permission management endpoints require authentication and administrative privileges:

```bash
# Session-based (web interface)
Authorization: Session cookie

# Token-based (API access)
Authorization: Bearer {token}
Required abilities: admin.read, admin.write
```

## Role Template Management

### List Role Templates

```http
GET /api/role-templates
```

**Query Parameters:**
- `context` (optional) - Filter by context: `service_provider`, `account_user`
- `modifiable` (optional) - Filter by modifiable status: `true`, `false`
- `search` (optional) - Search by name

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Super Admin", 
      "description": "Complete system administration access",
      "context": "service_provider",
      "is_system_role": true,
      "is_default": false,
      "is_modifiable": false,
      "users_count": 1,
      "permission_counts": {
        "functional": 45,
        "widget": 15,
        "page": 12
      },
      "created_at": "2025-08-11T10:00:00.000000Z",
      "updated_at": "2025-08-11T10:00:00.000000Z"
    }
  ]
}
```

### Get Role Template

```http
GET /api/role-templates/{roleTemplate}
```

**Response:**
```json
{
  "data": {
    "id": 1,
    "name": "Admin",
    "description": "Administrative access for service provider staff",
    "context": "service_provider",
    "is_system_role": false,
    "is_default": true,
    "is_modifiable": true,
    "permissions": [
      "admin.manage",
      "users.manage",
      "accounts.manage"
    ],
    "widget_permissions": [
      "widgets.dashboard.system-health",
      "widgets.dashboard.user-management"
    ],
    "page_permissions": [
      "pages.admin.dashboard",
      "pages.admin.users"
    ],
    "users_count": 3,
    "roles": [
      {
        "id": 1,
        "name": "Admin",
        "users_count": 3
      }
    ],
    "widget_configurations": [
      {
        "widget_id": "system-health",
        "enabled": true,
        "display_order": 1,
        "config": {}
      }
    ],
    "created_at": "2025-08-11T10:00:00.000000Z",
    "updated_at": "2025-08-11T10:00:00.000000Z"
  }
}
```

### Create Role Template

```http
POST /api/role-templates
```

**Request Body:**
```json
{
  "name": "Senior Technician",
  "description": "Experienced technician with team oversight",
  "context": "service_provider",
  "permissions": [
    "tickets.view.all",
    "tickets.create",
    "tickets.assign",
    "time.track",
    "time.approve"
  ],
  "widget_permissions": [
    "widgets.dashboard.ticket-overview",
    "widgets.dashboard.time-tracking"
  ],
  "page_permissions": [
    "pages.tickets.index",
    "pages.tickets.create"
  ],
  "is_default": false
}
```

**Response:**
```json
{
  "message": "Role template created successfully",
  "data": {
    "id": 7,
    "name": "Senior Technician",
    "description": "Experienced technician with team oversight",
    "context": "service_provider",
    "is_system_role": false,
    "is_default": false,
    "is_modifiable": true,
    "permissions": [
      "tickets.view.all",
      "tickets.create",
      "tickets.assign",
      "time.track",
      "time.approve"
    ],
    "widget_permissions": [
      "widgets.dashboard.ticket-overview",
      "widgets.dashboard.time-tracking"
    ],
    "page_permissions": [
      "pages.tickets.index",
      "pages.tickets.create"
    ],
    "created_at": "2025-08-11T12:00:00.000000Z",
    "updated_at": "2025-08-11T12:00:00.000000Z"
  }
}
```

### Update Role Template

```http
PUT /api/role-templates/{roleTemplate}
```

**Request Body:** Same as create, with optional fields.

**Response:**
```json
{
  "message": "Role template updated successfully", 
  "data": {
    "id": 7,
    "name": "Senior Technician",
    // ... updated role template data
  }
}
```

### Delete Role Template

```http
DELETE /api/role-templates/{roleTemplate}
```

**Conditions:**
- Role template must be modifiable (`is_modifiable: true`)
- Role template must not be in use (no assigned users)

**Response:**
```json
{
  "message": "Role template deleted successfully"
}
```

**Error Responses:**
```json
// Non-modifiable role template
{
  "message": "This role template cannot be deleted"
}

// Role template in use
{
  "message": "Cannot delete role template that is currently in use"
}
```

### Clone Role Template

```http
POST /api/role-templates/{roleTemplate}/clone
```

**Request Body:**
```json
{
  "name": "Custom Admin Role",
  "description": "Customized version of Admin role"
}
```

**Response:**
```json
{
  "message": "Role template cloned successfully",
  "data": {
    "id": 8,
    "name": "Custom Admin Role", 
    "description": "Customized version of Admin role",
    "context": "service_provider",
    "permissions": ["..."],  // Copied from source
    "widget_permissions": ["..."],  // Copied from source
    "page_permissions": ["..."],  // Copied from source
    "is_system_role": false,
    "is_default": false,
    "is_modifiable": true,
    "created_at": "2025-08-11T12:30:00.000000Z",
    "updated_at": "2025-08-11T12:30:00.000000Z"
  }
}
```

## Permission Management

### Get Available Permissions

```http
GET /api/role-templates/permissions/available
```

**Response:**
```json
{
  "data": {
    "functional_permissions": {
      "admin": [
        {
          "key": "admin.manage",
          "name": "Admin Manage",
          "description": "Full administrative access to system management",
          "category": "admin",
          "action": "manage",
          "scope": null
        }
      ],
      "tickets": [
        {
          "key": "tickets.view.all",
          "name": "Tickets View All",
          "description": "View all service tickets across all accounts", 
          "category": "tickets",
          "action": "view",
          "scope": "all"
        }
      ]
    },
    "widget_permissions": [
      {
        "key": "widgets.dashboard.system-health",
        "name": "System Health",
        "description": "Access to System Health widget",
        "category": "administration",
        "context": "service_provider"
      },
      {
        "key": "widgets.configure",
        "name": "Configure Widgets",
        "description": "Ability to configure and customize widgets",
        "category": "administration", 
        "context": "both"
      }
    ],
    "page_permissions": {
      "admin": [
        {
          "key": "pages.admin.dashboard",
          "name": "Pages Admin Dashboard",
          "description": "Admin dashboard access",
          "category": "admin",
          "page": "dashboard"
        }
      ]
    }
  }
}
```

### Get Widget Permissions

```http
GET /api/widget-permissions
```

**Response:**
```json
{
  "data": [
    {
      "widget_id": "system-health",
      "name": "System Health",
      "description": "Monitor system status, database, Redis, and queue health",
      "category": "administration",
      "context": "service_provider",
      "permission_key": "widgets.dashboard.system-health",
      "enabled_by_default": true,
      "configurable": true,
      "default_size": {
        "w": 4,
        "h": 2
      }
    }
  ]
}
```

### Sync Widget Permissions

```http
POST /api/widget-permissions/sync
```

Synchronizes widget permissions with the widget registry. Updates available widget permissions based on discovered widgets.

**Response:**
```json
{
  "message": "Widget permissions synchronized successfully",
  "data": {
    "synced_widgets": 15,
    "new_permissions": 3,
    "updated_permissions": 2,
    "removed_permissions": 1
  }
}
```

## Dashboard Preview System

### Preview Complete Dashboard

```http
GET /api/role-templates/{roleTemplate}/preview/dashboard?context=service_provider
```

**Query Parameters:**
- `context` (optional) - Preview context: `service_provider`, `account_user`

**Response:**
```json
{
  "data": {
    "role_template": {
      "id": 2,
      "name": "Admin",
      "context": "service_provider"
    },
    "mock_user": {
      "name": "Alex Johnson (Admin)",
      "context": "service_provider", 
      "permissions": ["admin.manage", "users.manage"]
    },
    "dashboard": {
      "available_widgets": [
        {
          "id": "system-health",
          "name": "System Health",
          "description": "Monitor system status",
          "category": "administration",
          "enabled_by_default": true
        }
      ],
      "layout": [
        {
          "i": "system-health",
          "x": 0,
          "y": 0,
          "w": 4,
          "h": 2,
          "widget_config": {}
        }
      ],
      "widget_data": {
        "system-health": {
          "database": "healthy",
          "redis": "healthy",
          "storage_disk": "healthy",
          "_preview_mode": true
        }
      },
      "navigation": [
        {
          "name": "Dashboard",
          "route": "dashboard",
          "icon": "home"
        }
      ],
      "title": "Dashboard Preview - Admin (Service Provider)"
    },
    "stats": {
      "total_widgets": 8,
      "functional_permissions": 25,
      "widget_permissions": 8,
      "page_permissions": 12,
      "navigation_items": 6
    }
  }
}
```

### Preview Widgets Only

```http
GET /api/role-templates/{roleTemplate}/preview/widgets?context=service_provider
```

**Response:**
```json
{
  "data": {
    "widgets": [
      {
        "id": "system-health",
        "name": "System Health", 
        "description": "Monitor system status, database, Redis, and queue health",
        "category": "administration",
        "enabled_by_default": true,
        "configurable": true,
        "default_size": {
          "w": 4,
          "h": 2
        }
      }
    ],
    "widget_data": {
      "system-health": {
        "database": "healthy",
        "redis": "healthy",
        "_preview_mode": true
      }
    },
    "categories": ["administration", "service_delivery"],
    "context": "service_provider"
  }
}
```

### Preview Navigation

```http  
GET /api/role-templates/{roleTemplate}/preview/navigation?context=service_provider
```

**Response:**
```json
{
  "data": {
    "navigation": [
      {
        "name": "Dashboard",
        "route": "dashboard", 
        "icon": "home",
        "badge": null
      },
      {
        "name": "Service Tickets",
        "route": "tickets.index",
        "icon": "ticket",
        "badge": "5"
      }
    ],
    "grouped_navigation": {
      "main": [
        {
          "name": "Dashboard",
          "route": "dashboard",
          "icon": "home"
        }
      ],
      "service": [
        {
          "name": "Service Tickets", 
          "route": "tickets.index",
          "icon": "ticket"
        }
      ]
    },
    "group_labels": {
      "main": "Main",
      "service": "Service Delivery"
    },
    "context": "service_provider",
    "stats": {
      "total_items": 6,
      "groups": 3
    }
  }
}
```

### Preview Layout

```http
GET /api/role-templates/{roleTemplate}/preview/layout?context=service_provider
```

**Response:**
```json
{
  "data": {
    "layout": [
      {
        "i": "system-health",
        "x": 0,
        "y": 0,
        "w": 4,
        "h": 2,
        "widget_config": {}
      },
      {
        "i": "user-management", 
        "x": 4,
        "y": 0,
        "w": 4,
        "h": 3,
        "widget_config": {}
      }
    ],
    "widgets_count": 8,
    "grid_settings": {
      "max_cols": 12,
      "row_height": 100,
      "margin": [10, 10]
    },
    "context": "service_provider"
  }
}
```

## Widget Assignment Interface

### Get Widget Assignments

```http
GET /api/role-templates/{roleTemplate}/widgets
```

**Response:**
```json
{
  "data": {
    "widgets": [
      {
        "id": "system-health",
        "widget_id": "system-health",
        "enabled": true,
        "enabled_by_default": true,
        "configurable": true,
        "display_order": 1,
        "widget_config": {}
      }
    ],
    "layout": [
      {
        "i": "system-health",
        "x": 0,
        "y": 0,
        "w": 4,
        "h": 2,
        "widget_config": {}
      }
    ]
  }
}
```

### Update Widget Assignments

```http
PUT /api/role-templates/{roleTemplate}/widgets
```

**Request Body:**
```json
{
  "widgets": [
    {
      "widget_id": "system-health",
      "enabled": true,
      "enabled_by_default": true,
      "configurable": true,
      "widget_config": {
        "refresh_interval": 30
      }
    },
    {
      "widget_id": "user-management", 
      "enabled": true,
      "enabled_by_default": false,
      "configurable": true,
      "widget_config": {}
    }
  ],
  "layout": [
    {
      "i": "system-health",
      "x": 0,
      "y": 0,
      "w": 4,
      "h": 2,
      "widget_config": {}
    },
    {
      "i": "user-management",
      "x": 4,
      "y": 0, 
      "w": 4,
      "h": 3,
      "widget_config": {}
    }
  ]
}
```

**Response:**
```json
{
  "message": "Widget assignments updated successfully",
  "data": {
    "widgets_count": 2,
    "layout_items": 2
  }
}
```

## Error Responses

### Common Error Codes

**401 Unauthorized**
```json
{
  "message": "Unauthenticated"
}
```

**403 Forbidden**
```json
{
  "message": "This action is unauthorized"
}
```

**404 Not Found**
```json
{
  "message": "Role template not found"
}
```

**422 Validation Error**
```json
{
  "message": "The given data was invalid",
  "errors": {
    "name": [
      "The name field is required"
    ],
    "permissions": [
      "Invalid permission: invalid.permission"
    ]
  }
}
```

**500 Server Error**
```json
{
  "message": "Failed to update widget assignments",
  "error": "Database transaction failed"
}
```

## Permission Requirements

### Endpoint Authorization

| Endpoint | Required Permissions |
|----------|---------------------|
| `GET /api/role-templates` | `admin.read` |
| `POST /api/role-templates` | `admin.write` |
| `PUT /api/role-templates/{id}` | `admin.write` |
| `DELETE /api/role-templates/{id}` | `admin.write` |
| `GET /api/role-templates/permissions/available` | `admin.read` |
| `GET /api/role-templates/{id}/preview/*` | `admin.read` |
| `GET /api/role-templates/{id}/widgets` | `admin.read` |
| `PUT /api/role-templates/{id}/widgets` | `admin.write` |
| `GET /api/widget-permissions` | `admin.read` |
| `POST /api/widget-permissions/sync` | `admin.write` |

### Context Validation

- Role templates with `context: "service_provider"` can only be assigned to service provider users
- Role templates with `context: "account_user"` can only be assigned to customer account users  
- Role templates with `context: "both"` can be assigned to any user type

### Modification Restrictions

- System role templates (`is_system_role: true`) cannot be modified or deleted
- The Super Admin role template always has `is_modifiable: false`
- Role templates in active use cannot be deleted (must have `users_count: 0`)

## Rate Limits

All role and permission management endpoints are subject to rate limiting:

- **Authenticated Requests**: 100 requests per minute per user
- **Preview Requests**: 60 requests per minute per user  
- **Widget Assignment Updates**: 30 requests per minute per user

## Examples

### Create Custom Customer Service Role

```bash
curl -X POST /api/role-templates \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Customer Service Representative",
    "description": "Handle customer inquiries and basic ticket management",
    "context": "service_provider",
    "permissions": [
      "tickets.view.assigned",
      "tickets.create",
      "time.track"
    ],
    "widget_permissions": [
      "widgets.dashboard.my-tickets",
      "widgets.dashboard.time-tracking", 
      "widgets.dashboard.quick-actions"
    ],
    "page_permissions": [
      "pages.tickets.index",
      "pages.tickets.create",
      "pages.time.entries"
    ]
  }'
```

### Preview Dashboard for Account Manager Role

```bash
curl -X GET "/api/role-templates/5/preview/dashboard?context=account_user" \
  -H "Authorization: Bearer {token}"
```

### Assign Widgets with Custom Layout

```bash
curl -X PUT /api/role-templates/7/widgets \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "widgets": [
      {
        "widget_id": "ticket-overview",
        "enabled": true,
        "enabled_by_default": true,
        "configurable": true
      },
      {
        "widget_id": "time-tracking",
        "enabled": true, 
        "enabled_by_default": true,
        "configurable": false
      }
    ],
    "layout": [
      {
        "i": "ticket-overview",
        "x": 0,
        "y": 0,
        "w": 8,
        "h": 4
      },
      {
        "i": "time-tracking", 
        "x": 8,
        "y": 0,
        "w": 4,
        "h": 4
      }
    ]
  }'
```

---

**Service Vault Roles & Permissions API** - Complete REST API for three-dimensional permission management with dashboard preview and widget assignment capabilities.