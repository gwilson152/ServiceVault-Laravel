# Widget API

Widget registry and dashboard APIs for Service Vault's dynamic widget system.

## Overview

### Widget System Features
- **Permission-Based Access**: Widgets filtered by user permissions
- **Auto-Discovery**: Automatic registration of widget components
- **Context Awareness**: Service provider vs account user widget filtering
- **Real-Time Data**: Dynamic widget data provisioning
- **Account Context**: Service providers can filter widgets by customer account

### Widget Categories
- **administration**: System management widgets
- **service_delivery**: Ticket and service-related widgets
- **time_management**: Timer and time tracking widgets
- **financial**: Billing and revenue widgets
- **analytics**: Performance and reporting widgets
- **productivity**: Quick actions and utility widgets

## Authentication

All widget endpoints require authentication:
- **Session Authentication**: For web dashboard access
- **Token Authentication**: For API access with appropriate abilities

### Required Permissions
Widget access is controlled by specific permissions defined in each widget's configuration.

## Endpoints

### Get Available Widgets
```http
GET /api/widgets/available
Authorization: Bearer {token}
```

**Query Parameters:**
- `category` (string): Filter by widget category
- `context` (string): Filter by context (`service_provider`, `account_user`)

**Response:**
```json
{
    "data": [
        {
            "id": "system-health",
            "name": "System Health",
            "description": "Monitor system status, database, Redis, and queue health",
            "component": "SystemHealthWidget",
            "category": "administration",
            "permissions": ["system.manage"],
            "context": "service_provider",
            "default_size": {
                "w": 4,
                "h": 2
            },
            "configurable": true,
            "enabled_by_default": true,
            "account_aware": false
        },
        {
            "id": "ticket-overview",
            "name": "Service Tickets Overview",
            "description": "Overview of service tickets across accounts",
            "component": "TicketOverviewWidget",
            "category": "service_delivery",
            "permissions": ["tickets.view.all", "tickets.view.account"],
            "context": "both",
            "default_size": {
                "w": 6,
                "h": 4
            },
            "configurable": true,
            "enabled_by_default": true,
            "account_aware": true
        }
    ],
    "meta": {
        "total_widgets": 14,
        "static_widgets": 12,
        "discovered_widgets": 2,
        "user_context": "service_provider"
    }
}
```

### Get Widget Categories
```http
GET /api/widgets/categories
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": [
        "administration",
        "service_delivery",
        "time_management",
        "financial",
        "analytics",
        "productivity"
    ]
}
```

### Get Widget by ID
```http
GET /api/widgets/{widget_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": {
        "id": "system-stats",
        "name": "System Statistics",
        "description": "Overview of users, accounts, timers, and system activity",
        "component": "SystemStatsWidget",
        "category": "administration",
        "permissions": ["admin.manage", "system.manage"],
        "context": "service_provider",
        "default_size": {
            "w": 8,
            "h": 2
        },
        "configurable": false,
        "enabled_by_default": true,
        "account_aware": false,
        "discovered": false,
        "file_path": null
    }
}
```

### Get Widget Discovery Statistics
```http
GET /api/widgets/discovery/stats
Authorization: Bearer {admin_token}
```

**Required Permissions**: `admin.read`

**Response:**
```json
{
    "data": {
        "static_widgets": 12,
        "discovered_widgets": 2,
        "total_widgets": 14,
        "discovery_enabled": true,
        "cache_enabled": true,
        "last_discovery": "2024-12-01T10:00:00Z",
        "discovery_directories": [
            "resources/js/Components/Widgets",
            "resources/js/Components/Dashboard/Widgets"
        ]
    }
}
```

### Clear Widget Discovery Cache
```http
POST /api/widgets/discovery/cache/clear
Authorization: Bearer {admin_token}
```

**Required Permissions**: `admin.manage`

**Response:**
```json
{
    "data": {
        "cache_cleared": true,
        "widgets_rediscovered": 2
    }
}
```

## Dashboard API

### Get Dashboard Layout
```http
GET /api/dashboard/layout
Authorization: Bearer {token}
```

**Query Parameters:**
- `account` (integer): Account ID for service provider context

**Response:**
```json
{
    "data": [
        {
            "i": "system-health",
            "x": 0,
            "y": 0,
            "w": 4,
            "h": 2
        },
        {
            "i": "ticket-overview",
            "x": 4,
            "y": 0,
            "w": 6,
            "h": 4
        },
        {
            "i": "quick-actions",
            "x": 0,
            "y": 2,
            "w": 4,
            "h": 2
        }
    ],
    "meta": {
        "user_id": 123,
        "layout_source": "default",
        "total_widgets": 3
    }
}
```

### Get Dashboard Data
```http
GET /api/dashboard/data
Authorization: Bearer {token}
```

**Query Parameters:**
- `account` (integer): Account ID for service provider context
- `refresh_widget` (string): Specific widget ID to refresh

**Response:**
```json
{
    "data": {
        "system-health": {
            "database": "healthy",
            "redis": "healthy",
            "storage_disk": "healthy",
            "queue_jobs": 5,
            "failed_jobs": 0
        },
        "ticket-overview": {
            "recent_tickets": [
                {
                    "id": 101,
                    "title": "User Authentication Issue",
                    "status": "open",
                    "account": {
                        "id": 456,
                        "name": "ACME Corp"
                    },
                    "assigned_user": {
                        "id": 789,
                        "name": "John Doe"
                    },
                    "created_at": "2024-12-01T09:00:00Z"
                }
            ],
            "stats": {
                "total": 25,
                "open": 5,
                "in_progress": 12,
                "resolved": 8
            }
        },
        "quick-actions": {
            "actions": [
                {
                    "name": "Create Ticket",
                    "route": "tickets.create",
                    "icon": "plus"
                },
                {
                    "name": "Start Timer",
                    "action": "start-timer",
                    "icon": "play"
                }
            ]
        }
    },
    "meta": {
        "account_context": {
            "user_type": "service_provider",
            "selected_account": {
                "id": 456,
                "name": "ACME Corp"
            },
            "can_switch_accounts": true
        },
        "dashboard_title": "Service Dashboard - ACME Corp",
        "last_updated": "2024-12-01T12:00:00Z"
    }
}
```

### Save Dashboard Layout
```http
POST /api/dashboard/layout
Authorization: Bearer {token}
Content-Type: application/json

{
    "layout": [
        {
            "i": "system-health",
            "x": 0,
            "y": 0,
            "w": 4,
            "h": 2
        },
        {
            "i": "ticket-overview",
            "x": 4,
            "y": 0,
            "w": 8,
            "h": 4
        }
    ]
}
```

**Response:**
```json
{
    "data": {
        "layout_saved": true,
        "widget_count": 2
    }
}
```

## Widget Data Endpoints

Individual widgets may have specific data endpoints for real-time updates:

### System Health Widget Data
```http
GET /api/widgets/system-health/data
Authorization: Bearer {token}
```

**Required Permissions**: `system.manage`

**Response:**
```json
{
    "data": {
        "database": "healthy",
        "redis": "healthy",
        "storage_disk": "warning",
        "queue_jobs": 12,
        "failed_jobs": 2,
        "last_check": "2024-12-01T12:00:00Z"
    }
}
```

### System Stats Widget Data
```http
GET /api/widgets/system-stats/data
Authorization: Bearer {token}
```

**Required Permissions**: `admin.manage` or `system.manage`

**Response:**
```json
{
    "data": {
        "total_users": 1250,
        "total_accounts": 45,
        "active_timers": 23,
        "total_time_entries": 12500,
        "users_this_month": 15,
        "time_tracked_today": 28800,
        "pending_approvals": 8,
        "domain_mappings": 12
    }
}
```

### All Timers Widget Data (Admin)
```http
GET /api/widgets/all-timers/data
Authorization: Bearer {admin_token}
```

**Required Permissions**: `admin.read` or `super_admin`

**Response:**
```json
{
    "data": {
        "active_timers": [
            {
                "id": 123,
                "user": {
                    "id": 456,
                    "name": "John Doe",
                    "email": "john@company.com"
                },
                "description": "Working on authentication",
                "status": "running",
                "current_duration": 7200,
                "current_value": 150.00,
                "account": {
                    "id": 789,
                    "name": "ACME Corp"
                }
            }
        ],
        "statistics": {
            "total_timers": 23,
            "total_users": 12,
            "total_duration": 86400,
            "total_value": 1800.00
        }
    }
}
```

### Ticket Overview Widget Data
```http
GET /api/widgets/ticket-overview/data
Authorization: Bearer {token}
```

**Query Parameters:**
- `account_id` (integer): Filter by specific account

**Required Permissions**: `tickets.view.all`, `tickets.view.account`, or `tickets.view.assigned`

**Response:**
```json
{
    "data": {
        "recent_tickets": [
            {
                "id": 101,
                "title": "User Authentication Issue",
                "status": "open",
                "priority": "high",
                "account": {
                    "id": 456,
                    "name": "ACME Corp"
                },
                "assigned_user": {
                    "id": 789,
                    "name": "John Doe"
                },
                "created_at": "2024-12-01T09:00:00Z",
                "updated_at": "2024-12-01T10:30:00Z"
            }
        ],
        "stats": {
            "total": 125,
            "open": 15,
            "in_progress": 45,
            "resolved": 65,
            "average_resolution_time": 7200
        }
    }
}
```

## Widget Configuration

### Get Widget Configuration Options
```http
GET /api/widgets/{widget_id}/config
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": {
        "widget_id": "ticket-overview",
        "configurable_options": [
            {
                "key": "max_tickets",
                "type": "number",
                "label": "Maximum Tickets to Display",
                "default": 10,
                "min": 5,
                "max": 50
            },
            {
                "key": "show_stats",
                "type": "boolean",
                "label": "Show Statistics",
                "default": true
            },
            {
                "key": "status_filter",
                "type": "select",
                "label": "Status Filter",
                "options": ["all", "open", "in_progress", "resolved"],
                "default": "all"
            }
        ]
    }
}
```

### Update Widget Configuration
```http
PUT /api/widgets/{widget_id}/config
Authorization: Bearer {token}
Content-Type: application/json

{
    "max_tickets": 15,
    "show_stats": true,
    "status_filter": "open"
}
```

**Response:**
```json
{
    "data": {
        "widget_id": "ticket-overview",
        "configuration_updated": true,
        "new_config": {
            "max_tickets": 15,
            "show_stats": true,
            "status_filter": "open"
        }
    }
}
```

## Error Responses

### Common Error Codes
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (insufficient permissions for widget)
- `404` - Widget not found
- `422` - Validation error

### Widget Permission Error
```json
{
    "error": {
        "code": 403,
        "message": "Insufficient permissions to access this widget",
        "details": {
            "widget_id": "system-health",
            "required_permissions": ["system.manage"],
            "user_permissions": ["time.track", "tickets.view.own"]
        }
    }
}
```

### Widget Not Found Error
```json
{
    "error": {
        "code": 404,
        "message": "Widget not found",
        "details": {
            "widget_id": "unknown-widget",
            "available_widgets": ["system-health", "system-stats", "ticket-overview"]
        }
    }
}
```

## Performance Considerations

### Caching
- Widget registry cached in production for 1 hour
- Widget data cached per user for 5 minutes
- Account context cached for user session duration

### Rate Limiting
- Widget API endpoints: 120 requests per minute
- Dashboard data endpoint: 60 requests per minute
- Widget configuration endpoints: 30 requests per minute

### Optimization
- Widgets load data only when visible
- Dashboard data can be refreshed for individual widgets
- Account context switching preserves widget state

## Real-Time Updates

Widgets support real-time updates via Laravel Echo broadcasting:

```javascript
// Listen for widget data updates
Echo.private(`user.${userId}`)
    .listen('WidgetDataUpdated', (event) => {
        // event.widget_id contains the updated widget
        // event.data contains new widget data
        updateWidget(event.widget_id, event.data);
    });

// Listen for widget configuration changes
Echo.private(`user.${userId}`)
    .listen('WidgetConfigUpdated', (event) => {
        // event.widget_id contains the configured widget
        // event.config contains new configuration
        reconfigureWidget(event.widget_id, event.config);
    });
```

## Security

### Permission Enforcement
- All widget endpoints validate user permissions
- Widget data respects account-level access controls
- Admin widgets require elevated permissions

### Data Isolation
- Account-aware widgets filter data by user's account access
- Service provider users can switch account context
- Account users only see their own account data

### Audit Trail
- Widget configuration changes are logged
- Admin widget access is audited
- Failed permission checks are logged for security monitoring