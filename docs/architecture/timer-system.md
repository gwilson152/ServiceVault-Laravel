# Timer System Architecture

Simplified user-global timer system with real-time synchronization across all devices.

## Architecture Overview

### Core Features
- **User-Global Timers**: Timers belong to users, not devices - start on desktop, control from mobile
- **Real-Time Sync**: Database as single source of truth with WebSocket broadcasting
- **Admin Oversight**: Cross-user timer monitoring and control
- **Seamless Cross-Device Experience**: Same timer state visible and controllable from any device
- **Service Ticket Integration**: Timers directly linked to tickets for service delivery

### Technology Stack
- **Backend**: Laravel 12 with Redis state management
- **Database**: PostgreSQL with optimized indexing
- **Caching**: Redis for timer state and sync
- **Frontend**: Vue.js 3.5 with real-time composables
- **Broadcasting**: Laravel Echo + WebSocket

## Database Design

### Timer Model
```sql
CREATE TABLE timers (
    id UUID PRIMARY KEY,
    user_id UUID REFERENCES users(id),
    account_id UUID REFERENCES accounts(id),
    ticket_id UUID REFERENCES tickets(id),
    description TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'stopped',
    started_at TIMESTAMP NULL,
    paused_at TIMESTAMP NULL,
    stopped_at TIMESTAMP NULL,
    total_duration INTEGER DEFAULT 0,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Performance indexes
CREATE INDEX idx_timers_user_status ON timers(user_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_timers_active ON timers(user_id, started_at) WHERE status IN ('running', 'paused');
```

## State Management

### Timer States
- **stopped**: Not running (initial state)
- **running**: Actively counting time
- **paused**: Temporarily stopped, can be resumed

### Redis State Keys
```
user:{user_id}:timer:{timer_id}:state     # Individual timer state
user:{user_id}:active_timers              # Set of active timer IDs
user:{user_id}:last_sync                  # Last sync timestamp
```

### State Synchronization
```php
class TimerSyncService
{
    public function syncUserTimers(User $user, array $localTimers): array
    {
        // Compare local vs server state
        // Resolve conflicts (server wins)
        // Return sync actions for client
    }
    
    public function broadcastTimerUpdate(Timer $timer): void
    {
        broadcast(new TimerUpdated($timer));
    }
}
```

## API Architecture

### Core Timer Operations
```php
class TimerController extends Controller
{
    public function index(Request $request)    // List user timers
    public function store(Request $request)    // Create/start timer
    public function show(Timer $timer)         // Timer details
    public function pause(Timer $timer)        // Pause timer
    public function resume(Timer $timer)       // Resume timer
    public function stop(Timer $timer)         // Stop timer
    public function commit(Timer $timer)       // Stop and create time entry
    public function sync(Request $request)     // Cross-device sync
}
```

### Admin Timer Management
```php
class AdminTimerController extends Controller
{
    public function allActive()                    // View all active timers
    public function adminPauseTimer(Timer $timer) // Admin pause
    public function adminResumeTimer(Timer $timer)// Admin resume  
    public function adminStopTimer(Timer $timer)  // Admin stop
}
```

## Real-Time Integration

### WebSocket Broadcasting
Timer changes broadcast on `user.{userId}` channels:

```javascript
// Frontend integration
Echo.private(`user.${userId}`)
    .listen('TimerStarted', (event) => {
        updateTimerUI(event.timer);
    })
    .listen('TimerStopped', (event) => {
        removeTimerFromUI(event.timer);
        if (event.time_entry) {
            addTimeEntry(event.time_entry);
        }
    });
```

### Vue.js Composable
```javascript
export function useTimerBroadcasting() {
    const activeTimers = ref([]);
    const totalActiveTime = computed(() => 
        activeTimers.value.reduce((sum, timer) => sum + timer.duration, 0)
    );
    
    const startTimer = async (data) => {
        const response = await axios.post('/api/timers', data);
        return response.data;
    };
    
    return { activeTimers, totalActiveTime, startTimer };
}
```

## Cross-Device Synchronization

### Simplified Sync Protocol
1. **Database Authority**: Database serves as single source of truth for all timer state
2. **Real-Time Updates**: WebSocket broadcasting ensures all devices receive updates immediately
3. **Refresh on Demand**: Sync endpoint refreshes local state from database when needed
4. **No Conflict Resolution**: Eliminates complex device-specific synchronization logic

### Sync Request/Response
```json
// Simple sync request (no local state comparison needed)
POST /api/timers/sync
{}

// Response with current authoritative state
{
  "message": "Timer state synchronized",
  "data": {
    "timers": [
      {
        "id": 1,
        "status": "running",
        "duration": 3700,
        "started_at": "2024-12-01T10:00:00Z"
      }
    ],
    "synced_at": "2024-12-01T11:00:00Z"
  }
}
```

## Performance Optimizations

### Caching Strategy
- Active timer states cached in Redis (24 hour TTL)
- Database indexes optimize timer queries by user and status
- Real-time broadcasting eliminates need for frequent polling

### Database Optimization
- Composite indexes on (user_id, status, started_at)
- Soft deletes for audit trail
- Automatic cleanup of old stopped timers

### Broadcasting Optimization
- Broadcast timer updates to user channels across all devices
- Real-time state synchronization without polling
- Efficient WebSocket event distribution

## Admin Oversight Features

### Cross-User Timer Management
Administrators can monitor and control any user's timers:

```php
Gate::define('admin.timers.manage', function (User $user) {
    return $user->hasPermission('admin.read') && 
           $user->hasPermission('timers.manage.team');
});
```

### Admin Dashboard Integration
- Real-time view of all active timers across organization
- Timer statistics and reporting
- Bulk operations for timer management
- Audit trail of admin actions

## Integration Points

### Service Tickets
- Timers can be associated with specific tickets
- Automatic billing rate inheritance from ticket context
- Timer data flows into service delivery reporting

### Time Entries
- Timers convert to time entries when committed
- Approval workflows for billable time
- Integration with invoicing and billing systems

### Billing System
- Real-time calculation of timer values
- Automatic billing rate application
- Cost tracking and project profitability analysis