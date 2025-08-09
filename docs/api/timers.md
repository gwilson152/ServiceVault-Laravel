# Timers API

Service Vault supports multiple concurrent timers per user with real-time cross-device synchronization via Redis state management.

## Overview

### Multi-Timer Support
- **Concurrent Timers**: Users can run multiple timers simultaneously
- **Cross-Device Sync**: Redis-based state synchronization across devices
- **Conflict Resolution**: Automatic handling of timer state conflicts
- **Real-Time Updates**: WebSocket broadcasting for live timer updates

### Timer States
- **stopped**: Timer is not running (initial state)
- **running**: Timer is actively counting time
- **paused**: Timer is temporarily stopped, can be resumed

## Authentication

All timer endpoints require authentication. Supported methods:
- **Session Authentication**: For web dashboard access
- **Token Authentication**: For API/mobile access with `timers:*` abilities

### Required Token Abilities
- `timers:read` - View timer data
- `timers:write` - Create and update timers  
- `timers:delete` - Delete timers
- `timers:sync` - Cross-device timer synchronization

## Endpoints

### List User Timers
```http
GET /api/timers
Authorization: Bearer {token}
```

**Query Parameters:**
- `page` (integer): Page number for pagination
- `per_page` (integer): Items per page (max 100)
- `status` (string): Filter by timer status (running, paused, stopped)
- `account_id` (integer): Filter by account ID

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "description": "Working on user authentication",
            "status": "running",
            "started_at": "2024-12-01T09:00:00Z",
            "paused_at": null,
            "stopped_at": null,
            "total_duration": 7200,
            "current_session_duration": 3600,
            "account": {
                "id": 123,
                "name": "Company Inc"
            },
            "project": {
                "id": 456,
                "name": "Authentication System"
            },
            "billing_rate": {
                "id": 789,
                "rate": 75.00,
                "currency": "USD"
            }
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 15,
        "total": 1,
        "active_timers_count": 1,
        "total_active_duration": 7200
    }
}
```

### Get Active Timers
```http
GET /api/timers/active/current
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": [
        {
            "id": 1,
            "description": "Working on user authentication",
            "status": "running",
            "started_at": "2024-12-01T09:00:00Z",
            "current_session_duration": 3600,
            "total_duration": 7200,
            "billing_rate": {
                "id": 789,
                "rate": 75.00,
                "currency": "USD"
            },
            "current_value": 150.00
        },
        {
            "id": 2,
            "description": "Code review",
            "status": "paused",
            "started_at": "2024-12-01T10:30:00Z",
            "paused_at": "2024-12-01T11:00:00Z",
            "current_session_duration": 1800,
            "total_duration": 1800,
            "billing_rate": {
                "id": 789,
                "rate": 75.00,
                "currency": "USD"
            },
            "current_value": 37.50
        }
    ],
    "meta": {
        "active_count": 2,
        "running_count": 1,
        "paused_count": 1,
        "total_active_duration": 9000,
        "total_active_value": 187.50
    }
}
```

### Create Timer
```http
POST /api/timers
Authorization: Bearer {token}
Content-Type: application/json

{
    "description": "Working on user authentication",
    "account_id": 123,
    "project_id": 456,
    "billing_rate_id": 789,
    "stop_others": false
}
```

**Parameters:**
- `description` (string, required): Timer description
- `account_id` (integer, required): Account ID for the timer
- `project_id` (integer, optional): Project ID if applicable
- `billing_rate_id` (integer, optional): Specific billing rate
- `stop_others` (boolean, default: false): Whether to stop other running timers

**Response:**
```json
{
    "data": {
        "id": 1,
        "description": "Working on user authentication",
        "status": "running",
        "started_at": "2024-12-01T09:00:00Z",
        "account": {
            "id": 123,
            "name": "Company Inc"
        },
        "project": {
            "id": 456,
            "name": "Authentication System"
        },
        "billing_rate": {
            "id": 789,
            "rate": 75.00,
            "currency": "USD"
        }
    }
}
```

### Get Timer Details
```http
GET /api/timers/{timer_id}
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "description": "Working on user authentication",
        "status": "running",
        "started_at": "2024-12-01T09:00:00Z",
        "paused_at": null,
        "stopped_at": null,
        "total_duration": 7200,
        "current_session_duration": 3600,
        "sessions": [
            {
                "started_at": "2024-12-01T09:00:00Z",
                "paused_at": "2024-12-01T10:00:00Z",
                "duration": 3600
            },
            {
                "started_at": "2024-12-01T10:30:00Z",
                "paused_at": null,
                "duration": 3600
            }
        ],
        "account": {
            "id": 123,
            "name": "Company Inc"
        },
        "project": {
            "id": 456,
            "name": "Authentication System"
        },
        "billing_rate": {
            "id": 789,
            "rate": 75.00,
            "currency": "USD"
        },
        "current_value": 150.00
    }
}
```

### Update Timer
```http
PUT /api/timers/{timer_id}
Authorization: Bearer {token}
Content-Type: application/json

{
    "description": "Updated description",
    "project_id": 789,
    "billing_rate_id": 456
}
```

### Stop Timer
```http
POST /api/timers/{timer_id}/stop
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "status": "stopped",
        "stopped_at": "2024-12-01T12:00:00Z",
        "total_duration": 10800,
        "final_value": 225.00
    }
}
```

### Pause Timer
```http
POST /api/timers/{timer_id}/pause
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "status": "paused",
        "paused_at": "2024-12-01T11:00:00Z",
        "current_session_duration": 3600,
        "total_duration": 7200
    }
}
```

### Resume Timer
```http
POST /api/timers/{timer_id}/resume
Authorization: Bearer {token}
```

**Response:**
```json
{
    "data": {
        "id": 1,
        "status": "running",
        "resumed_at": "2024-12-01T11:30:00Z",
        "total_duration": 7200
    }
}
```

### Commit Timer (Stop and Create Time Entry)
```http
POST /api/timers/{timer_id}/commit
Authorization: Bearer {token}
Content-Type: application/json

{
    "description": "Final description for time entry",
    "billable": true,
    "notes": "Completed user authentication feature"
}
```

**Response:**
```json
{
    "data": {
        "timer": {
            "id": 1,
            "status": "stopped",
            "total_duration": 10800
        },
        "time_entry": {
            "id": 101,
            "description": "Final description for time entry",
            "duration": 10800,
            "billable": true,
            "notes": "Completed user authentication feature",
            "account_id": 123,
            "project_id": 456,
            "billing_rate": 75.00,
            "total_value": 225.00,
            "created_at": "2024-12-01T12:00:00Z"
        }
    }
}
```

### Delete Timer
```http
DELETE /api/timers/{timer_id}
Authorization: Bearer {token}
```

**Query Parameters:**
- `force` (boolean): Force delete even if timer is running

### Cross-Device Timer Synchronization
```http
POST /api/timers/sync
Authorization: Bearer {token}
Content-Type: application/json

{
    "device_id": "mobile_app_v1.0",
    "last_sync": "2024-12-01T10:00:00Z",
    "local_timers": [
        {
            "id": 1,
            "status": "running",
            "local_duration": 3600,
            "last_updated": "2024-12-01T11:00:00Z"
        }
    ]
}
```

**Response:**
```json
{
    "data": {
        "sync_timestamp": "2024-12-01T11:00:00Z",
        "conflicts_resolved": 0,
        "timers": [
            {
                "id": 1,
                "status": "running",
                "server_duration": 3700,
                "sync_action": "update_local"
            }
        ],
        "deleted_timers": [],
        "new_timers": []
    }
}
```

### Bulk Timer Operations
```http
POST /api/timers/bulk
Authorization: Bearer {token}
Content-Type: application/json

{
    "action": "stop",
    "timer_ids": [1, 2, 3],
    "options": {
        "create_time_entries": true
    }
}
```

**Supported Actions:**
- `stop` - Stop multiple timers
- `pause` - Pause multiple timers
- `resume` - Resume multiple timers  
- `delete` - Delete multiple timers

**Response:**
```json
{
    "data": {
        "successful": [1, 2],
        "failed": [
            {
                "timer_id": 3,
                "error": "Timer not found"
            }
        ],
        "time_entries_created": 2
    }
}
```

## Timer State Management

### Redis Keys Structure
```
user:{user_id}:timer:{timer_id}:state
user:{user_id}:active_timers        # Set of active timer IDs
user:{user_id}:last_sync           # Last synchronization timestamp
```

### State Synchronization
- **Automatic Sync**: Every 5 seconds for active timers
- **Manual Sync**: Via `/api/timers/sync` endpoint
- **Conflict Resolution**: Server timestamp wins, with user notification
- **Offline Support**: Local timer state cached, synced when online

## Real-Time Broadcasting

### WebSocket Events
Timer changes are broadcast in real-time using Laravel Echo:

#### Timer Started
```javascript
Echo.private(`user.${userId}`)
    .listen('TimerStarted', (event) => {
        // event.timer contains timer data
    });
```

#### Timer Updated
```javascript
Echo.private(`user.${userId}`)
    .listen('TimerUpdated', (event) => {
        // event.timer contains updated timer data
        // event.changes contains list of changed fields
    });
```

#### Timer Stopped
```javascript
Echo.private(`user.${userId}`)
    .listen('TimerStopped', (event) => {
        // event.timer contains final timer state
        // event.time_entry contains created time entry (if applicable)
    });
```

### Vue.js Integration
```javascript
// useTimerBroadcasting.js composable
import { useTimerBroadcasting } from '@/Composables/useTimerBroadcasting'

const {
    activeTimers,
    totalActiveTime,
    totalActiveValue,
    startTimer,
    stopTimer,
    pauseTimer,
    resumeTimer
} = useTimerBroadcasting()
```

## Error Responses

### Common Error Codes
- `400` - Bad Request (invalid parameters)
- `401` - Unauthorized (missing or invalid token)
- `403` - Forbidden (insufficient permissions)
- `404` - Timer not found
- `422` - Validation error
- `429` - Rate limit exceeded

### Example Error Response
```json
{
    "error": {
        "code": 422,
        "message": "The given data was invalid.",
        "details": {
            "description": ["The description field is required."],
            "account_id": ["The selected account id is invalid."]
        }
    }
}
```

## Performance Considerations

### Caching
- Timer states cached in Redis for 24 hours
- Active timer queries optimized with database indexes
- Cross-device sync limited to once per 5 seconds per user

### Rate Limiting
- Standard API limits: 60 requests per minute
- Timer sync endpoint: 120 requests per minute
- Bulk operations: 20 requests per minute

### Database Optimization
- Composite indexes on (user_id, status, started_at)
- Soft deletes for audit trail
- Automatic cleanup of old stopped timers (configurable retention)