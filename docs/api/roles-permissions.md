# Roles & Permissions API

Three-dimensional permission system with role templates and dashboard preview.

## Three-Dimensional Permissions
1. **Functional**: What users can DO (actions/operations)
2. **Widget**: What users can SEE on dashboards
3. **Page**: What pages users can ACCESS

## Authentication
- **Token Abilities**: `admin:read|write`, `roles:manage`

## Role Template Management

### Core Endpoints
```http
GET /api/role-templates?context=service_provider        # List role templates
POST /api/role-templates                                # Create role template
GET /api/role-templates/{id}                           # Role template details
PUT /api/role-templates/{id}                           # Update role template
DELETE /api/role-templates/{id}                        # Delete role template
POST /api/role-templates/{id}/clone                    # Clone role template
```

### Role Template Creation
```http
GET /api/role-templates/create                         # Get creation form data
GET /api/role-templates/create/preview/widgets         # Preview widgets for creation
```

### Permission Management
```http
GET /api/role-templates/permissions/available          # Available permissions
GET /api/role-templates/{id}/widgets                   # Role widget assignments
POST /api/role-templates/{id}/widgets                  # Update widget assignments
```

## Dashboard Preview System

### Preview Endpoints
```http
GET /api/role-templates/{id}/preview/dashboard         # Preview dashboard
GET /api/role-templates/{id}/preview/layout            # Preview layout
GET /api/role-templates/{id}/preview/navigation        # Preview navigation
GET /api/role-templates/{id}/preview/widgets           # Preview widgets
```

## Widget Permissions

### Widget Permission Management
```http
GET /api/widget-permissions                            # List widget permissions
POST /api/widget-permissions                           # Create widget permission
GET /api/widget-permissions/{id}                       # Widget permission details
PUT /api/widget-permissions/{id}                       # Update widget permission
DELETE /api/widget-permissions/{id}                    # Delete widget permission
```

### Widget-Role Assignment
```http
POST /api/widget-permissions/{id}/assign-to-role       # Assign widget to role
POST /api/widget-permissions/{id}/remove-from-role     # Remove widget from role
POST /api/widget-permissions/sync                      # Sync widget permissions
GET /api/widget-permissions/categories/list            # Widget categories
```

## Response Formats

### Role Template Response
```json
{
  "id": 1,
  "name": "Account Manager",
  "description": "Manage customer accounts",
  "context": "service_provider",
  "is_system_role": false,
  "is_modifiable": true,
  "users_count": 5,
  "permission_counts": {
    "functional": 15,
    "widget": 8,
    "page": 5
  },
  "permissions": {
    "functional": ["accounts.manage", "tickets.view"],
    "widget": ["dashboard.ticket-overview", "dashboard.account-activity"],
    "page": ["pages.tickets.manage", "pages.accounts.view"]
  }
}
```

### Dashboard Preview Response
```json
{
  "layout": {
    "columns": 12,
    "widgets": [
      {
        "id": "ticket-overview",
        "component": "TicketOverviewWidget",
        "position": { "x": 0, "y": 0, "w": 6, "h": 4 },
        "permissions": ["widgets.dashboard.ticket-overview"]
      }
    ]
  },
  "context": "service_provider",
  "preview_data": {
    "tickets_count": 45,
    "active_timers": 8
  }
}
```