# Timers API

User-global timer system with real-time synchronization and admin oversight.

## Features
- **User-Global Timers**: Timers belong to users, visible and controllable from any device
- **Real-Time Sync**: Database as single source of truth with WebSocket broadcasting
- **Admin Controls**: Pause, resume, stop any user's timer
- **Cross-Device Experience**: Same timer state across all devices
- **States**: `stopped`, `running`, `paused`

## Authentication
- **Session Auth**: Web dashboard access
- **Token Auth**: API access with abilities: `timers:read|write|delete|sync`
- **Admin Auth**: Additional `admin:read`, `timers:admin` abilities

## Core Endpoints

### User Timer Management
```http
GET /api/timers?page=1&status=running&account_id=123     # List user timers
GET /api/timers/active/current                           # Get active timers
GET /api/timers/{id}                                     # Timer details
POST /api/timers                                         # Create timer
PUT /api/timers/{id}                                     # Update timer
```

### Timer Controls
```http
POST /api/timers/{id}/pause        # Pause timer
POST /api/timers/{id}/resume       # Resume timer  
POST /api/timers/{id}/stop         # Stop timer
POST /api/timers/{id}/commit       # Stop and create time entry
DELETE /api/timers/{id}?force=true # Delete timer
```

### Bulk Operations
```http
POST /api/timers/bulk
{
  "action": "stop|pause|resume|delete",
  "timer_ids": [1, 2, 3],
  "options": { "create_time_entries": true }
}
```

### State Synchronization
```http
POST /api/timers/sync                                        # Refresh timer state
GET /api/timers/statistics?include_service_tickets=true     # Timer statistics
```

## Admin Endpoints (Requires `admin:read`)

### Admin Timer Management
```http
GET /api/admin/timers/all-active             # View all active timers
POST /api/admin/timers/{id}/pause            # Admin pause timer
POST /api/admin/timers/{id}/resume           # Admin resume timer
POST /api/admin/timers/{id}/stop             # Admin stop timer
GET /api/admin/timers/statistics             # Timer statistics
```

## Ticket Integration

### Ticket Timer Endpoints  
```http
GET /api/tickets/{id}/timers                 # Get timers for ticket
GET /api/tickets/{id}/timers/active          # Get active timers for ticket
POST /api/tickets/{id}/timers                # Start timer for ticket
```

## Real-Time Events
Timer changes broadcast via WebSocket on `user.{userId}` channel:
- `TimerStarted` - Timer created and started (visible on all user devices)
- `TimerUpdated` - Timer state changed (synchronized across devices)
- `TimerStopped` - Timer stopped/completed (updated on all devices)

## Response Format
All timer responses include:
```json
{
  "id": 1,
  "description": "Task description",
  "status": "running|paused|stopped",
  "started_at": "2024-12-01T09:00:00Z",
  "total_duration": 7200,
  "current_session_duration": 3600,
  "account": { "id": 123, "name": "Company" },
  "ticket": { "id": 456, "title": "Ticket Title" },
  "billing_rate": { "rate": 75.00, "currency": "USD" },
  "current_value": 150.00
}
```