# Timer System Architecture

Simplified user-global timer system with real-time synchronization across all devices.

## Architecture Overview

### Core Features
- **User-Global Timers**: Timers belong to users, not devices - start on desktop, control from mobile
- **Real-Time Sync**: Database as single source of truth with WebSocket broadcasting
- **Enhanced Timer Overlays**: Floating timer widgets with real-time counting and professional UX
- **Settings Management**: In-timer configuration for description, billing rates, and metadata
- **Advanced Commit Workflow**: Pause-then-commit with time rounding and note capabilities
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

## Enhanced Frontend Features

### Timer Overlay System
The floating timer overlay provides a professional, non-intrusive timer management interface:

#### Visual Modes
- **Mini Badge Mode**: Compact horizontal badges showing status, duration, and value
- **Expanded Panel Mode**: Full control interface with settings, actions, and detailed information
- **Real-Time Updates**: Live counting with 1-second precision updates

#### Enhanced UX Components
```javascript
// TimerBroadcastOverlay.vue - Enhanced Features
const features = {
  // Real-time counting with Vue reactivity
  realTimeUpdates: setInterval(() => currentTime.value = new Date(), 1000),
  
  // Settings management modal
  timerSettings: {
    description: 'Edit timer descriptions',
    billingRate: 'Change billing rates on-the-fly',
    preview: 'Live duration and value preview'
  },
  
  // Advanced commit workflow
  commitWorkflow: {
    pauseFirst: 'Pause timer before committing',
    roundingOptions: [5, 10, 15], // minutes
    roundUpBehavior: 'Always round up for accurate billing',
    notes: 'Add context notes to time entries'
  }
}
```

#### Layout and Design
- **Horizontal Right-to-Left**: Timers stack from right to left for natural flow
- **Minimum Width**: 192px minimum ensures readability across all content
- **Professional Styling**: Clean shadows, borders, and hover effects
- **Header Totals**: Summary information positioned above timer controls
- **Icon-Based Actions**: Intuitive SVG icons for all timer operations

### Settings Management System
Advanced in-timer configuration without interrupting workflow:

```vue
<template>
  <!-- Settings Modal with Live Preview -->
  <div class="timer-settings-modal">
    <input v-model="description" placeholder="Timer description..." />
    <select v-model="billing_rate_id">
      <option value="">No billing rate</option>
      <option value="uuid">Standard Rate - $75/hr</option>
    </select>
    
    <!-- Live Preview -->
    <div class="preview">
      <div>Duration: {{ formatDuration(currentDuration) }}</div>
      <div>Value: ${{ calculateValue().toFixed(2) }}</div>
    </div>
  </div>
</template>
```

### Advanced Commit Workflow
Professional time entry creation with business-friendly features:

#### Commit Process
1. **Pause Timer**: Stop timing without losing state
2. **Populate Dialog**: Auto-fill with timer information
3. **Add Context**: Notes field for additional details
4. **Round Up**: 5/10/15 minute rounding options (always round up)
5. **Create Entry**: Generate billable time entry
6. **Remove Timer**: Clean up completed timer

#### Rounding Logic (Backend)
```php
// TimerService.php - Round-up behavior
if ($roundTo > 0) {
    $minutes = ceil($duration / 60);           // Round up to next minute
    $roundedMinutes = ceil($minutes / $roundTo) * $roundTo; // Round up to interval
    $duration = $roundedMinutes * 60;          // Convert back to seconds
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