# Timer System Architecture

Service Vault implements a sophisticated multi-timer system supporting concurrent timers per user with real-time cross-device synchronization.

## Overview

### Multi-Timer Architecture
- **Concurrent Timers**: Users can run multiple timers simultaneously
- **Real-Time Sync**: Redis-based state management across devices
- **Conflict Resolution**: Automatic handling of timer state conflicts
- **Broadcasting**: Laravel Echo WebSocket integration for live updates

### Technology Stack
- **Backend**: Laravel 12 with Redis state management
- **Frontend**: Vue.js 3.5 with real-time composables
- **Database**: PostgreSQL with optimized indexing
- **Caching**: Redis for timer state and cross-device sync
- **Broadcasting**: Laravel Echo with Pusher/WebSocket support

## Database Design

### Timer Model Structure
```sql
CREATE TABLE timers (
    id BIGSERIAL PRIMARY KEY,
    user_id BIGINT NOT NULL REFERENCES users(id),
    account_id BIGINT NOT NULL REFERENCES accounts(id),
    project_id BIGINT NULL REFERENCES projects(id),
    billing_rate_id BIGINT NULL REFERENCES billing_rates(id),
    description TEXT NOT NULL,
    status VARCHAR(20) DEFAULT 'stopped',
    started_at TIMESTAMP NULL,
    paused_at TIMESTAMP NULL,
    stopped_at TIMESTAMP NULL,
    total_duration INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL
);

-- Optimized indexes for performance
CREATE INDEX idx_timers_user_status ON timers(user_id, status) WHERE deleted_at IS NULL;
CREATE INDEX idx_timers_user_started ON timers(user_id, started_at) WHERE deleted_at IS NULL;
CREATE INDEX idx_timers_account ON timers(account_id) WHERE deleted_at IS NULL;
```

### Timer Sessions Tracking
```sql
CREATE TABLE timer_sessions (
    id BIGSERIAL PRIMARY KEY,
    timer_id BIGINT NOT NULL REFERENCES timers(id),
    started_at TIMESTAMP NOT NULL,
    ended_at TIMESTAMP NULL,
    duration INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## Redis State Management

### State Storage Strategy
Service Vault uses Redis to maintain real-time timer state across devices:

#### Redis Key Structure
```
user:{user_id}:timer:{timer_id}:state          # Individual timer state
user:{user_id}:active_timers                   # Set of active timer IDs
user:{user_id}:last_sync                       # Last synchronization timestamp
timer:{timer_id}:sessions                      # Timer session history
global:active_users                            # Set of users with active timers
```

#### Timer State Object
```json
{
    "id": 123,
    "user_id": 456,
    "status": "running",
    "started_at": "2024-12-01T09:00:00Z",
    "paused_at": null,
    "total_duration": 7200,
    "current_session_start": "2024-12-01T11:00:00Z",
    "current_session_duration": 3600,
    "last_updated": "2024-12-01T12:00:00Z",
    "device_id": "web_browser_v1.0",
    "sync_version": 15
}
```

### State Synchronization Logic

#### TimerService Implementation
```php
class TimerService
{
    public function updateRedisState(Timer $timer): void
    {
        $key = "user:{$timer->user_id}:timer:{$timer->id}:state";
        $state = [
            'id' => $timer->id,
            'user_id' => $timer->user_id,
            'status' => $timer->status,
            'started_at' => $timer->started_at?->toISOString(),
            'paused_at' => $timer->paused_at?->toISOString(),
            'total_duration' => $timer->total_duration,
            'current_session_duration' => $timer->getCurrentSessionDuration(),
            'last_updated' => now()->toISOString(),
            'sync_version' => $this->incrementSyncVersion($timer)
        ];
        
        Redis::setex($key, 86400, json_encode($state)); // 24 hour TTL
        
        // Update user's active timers set
        if ($timer->isActive()) {
            Redis::sadd("user:{$timer->user_id}:active_timers", $timer->id);
        } else {
            Redis::srem("user:{$timer->user_id}:active_timers", $timer->id);
        }
    }
    
    public function getRedisState(int $userId): array
    {
        $activeTimerIds = Redis::smembers("user:{$userId}:active_timers");
        $timers = [];
        
        foreach ($activeTimerIds as $timerId) {
            $key = "user:{$userId}:timer:{$timerId}:state";
            $state = Redis::get($key);
            if ($state) {
                $timers[] = json_decode($state, true);
            }
        }
        
        return $timers;
    }
}
```

## Cross-Device Synchronization

### Sync Protocol
1. **Client Request**: Device sends current timer states with timestamps
2. **Conflict Detection**: Server compares timestamps and sync versions
3. **Resolution Strategy**: Server timestamp wins, with user notification
4. **State Update**: Merge resolved states and broadcast updates

### Conflict Resolution Algorithm
```php
public function reconcileTimerConflicts(array $localTimers, int $userId): array
{
    $serverTimers = $this->getRedisState($userId);
    $conflicts = [];
    $resolved = [];
    
    foreach ($localTimers as $localTimer) {
        $serverTimer = collect($serverTimers)->firstWhere('id', $localTimer['id']);
        
        if (!$serverTimer) {
            // Timer doesn't exist on server - client created offline
            $resolved[] = ['action' => 'create_on_server', 'timer' => $localTimer];
            continue;
        }
        
        $localTime = Carbon::parse($localTimer['last_updated']);
        $serverTime = Carbon::parse($serverTimer['last_updated']);
        
        if ($localTime->gt($serverTime)) {
            // Local timer is newer - update server
            $resolved[] = ['action' => 'update_server', 'timer' => $localTimer];
        } elseif ($serverTime->gt($localTime)) {
            // Server timer is newer - update client
            $resolved[] = ['action' => 'update_client', 'timer' => $serverTimer];
        }
        
        // Check for status conflicts
        if ($localTimer['status'] !== $serverTimer['status']) {
            $conflicts[] = [
                'timer_id' => $localTimer['id'],
                'conflict_type' => 'status_mismatch',
                'local_status' => $localTimer['status'],
                'server_status' => $serverTimer['status'],
                'resolution' => 'server_wins'
            ];
        }
    }
    
    return [
        'resolved' => $resolved,
        'conflicts' => $conflicts
    ];
}
```

## Real-Time Broadcasting

### Laravel Echo Integration
Service Vault uses Laravel Echo for real-time timer updates across devices:

#### Event Broadcasting
```php
// Timer events
class TimerStarted implements ShouldBroadcast
{
    public $timer;
    
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->timer->user_id);
    }
    
    public function broadcastAs()
    {
        return 'timer.started';
    }
}
```

#### Frontend Integration
```javascript
// Vue.js composable for timer broadcasting
export function useTimerBroadcasting() {
    const activeTimers = ref([])
    const totalActiveTime = ref(0)
    const totalActiveValue = ref(0)
    
    const initializeBroadcasting = () => {
        const userId = getCurrentUserId()
        
        Echo.private(`user.${userId}`)
            .listen('timer.started', (event) => {
                addTimer(event.timer)
                updateTotals()
            })
            .listen('timer.updated', (event) => {
                updateTimer(event.timer)
                updateTotals()
            })
            .listen('timer.stopped', (event) => {
                removeTimer(event.timer.id)
                updateTotals()
            })
    }
    
    return {
        activeTimers: readonly(activeTimers),
        totalActiveTime: readonly(totalActiveTime),
        totalActiveValue: readonly(totalActiveValue),
        initializeBroadcasting
    }
}
```

## Performance Optimization

### Caching Strategy
1. **Redis State Cache**: 24-hour TTL for timer states
2. **Database Query Optimization**: Composite indexes on frequently queried fields
3. **Event Debouncing**: Limit sync requests to once per 5 seconds
4. **Lazy Loading**: Load timer details only when needed

### Database Optimization
```sql
-- High-performance indexes
CREATE INDEX CONCURRENTLY idx_timers_user_active 
ON timers(user_id, status, started_at) 
WHERE status IN ('running', 'paused') AND deleted_at IS NULL;

CREATE INDEX CONCURRENTLY idx_timers_account_billing 
ON timers(account_id, billing_rate_id, created_at) 
WHERE deleted_at IS NULL;

-- Partial indexes for active timers only
CREATE INDEX CONCURRENTLY idx_active_timers_sync 
ON timers(user_id, updated_at) 
WHERE status IN ('running', 'paused') AND deleted_at IS NULL;
```

### Memory Management
- **Redis Memory Optimization**: Use appropriate data types (sets vs. lists)
- **State Cleanup**: Automatic cleanup of expired timer states
- **Connection Pooling**: Efficient Redis connection management

## API Architecture

### RESTful Endpoints
```
GET    /api/timers                     # List user timers (paginated)
POST   /api/timers                     # Create new timer
GET    /api/timers/active/current      # Get all active timers
POST   /api/timers/{id}/stop           # Stop timer
POST   /api/timers/{id}/pause          # Pause timer
POST   /api/timers/{id}/resume         # Resume timer
POST   /api/timers/sync               # Cross-device synchronization
POST   /api/timers/bulk               # Bulk operations
DELETE /api/timers/{id}               # Delete timer
```

### Response Standardization
```json
{
    "data": { /* timer data */ },
    "meta": {
        "active_count": 2,
        "running_count": 1,
        "paused_count": 1,
        "total_active_duration": 9000,
        "total_active_value": 187.50,
        "sync_timestamp": "2024-12-01T12:00:00Z"
    }
}
```

## Security Considerations

### Authorization
- **Token Abilities**: `timers:read`, `timers:write`, `timers:delete`, `timers:sync`
- **Account Scoping**: Users can only access timers for accounts they belong to
- **Rate Limiting**: Sync endpoint limited to 120 requests/minute per user

### Data Protection
- **State Encryption**: Sensitive timer data encrypted in Redis
- **Audit Trail**: All timer operations logged for compliance
- **Data Retention**: Configurable retention period for stopped timers

## Monitoring & Analytics

### Performance Metrics
- **Active Timer Count**: Real-time monitoring of concurrent timers
- **Sync Frequency**: Cross-device synchronization patterns
- **Conflict Resolution**: Rate and types of sync conflicts
- **Response Times**: API endpoint performance monitoring

### Health Checks
```php
class TimerSystemHealthCheck
{
    public function check(): array
    {
        return [
            'redis_connectivity' => $this->checkRedisConnection(),
            'active_timers_count' => $this->getActiveTimersCount(),
            'sync_queue_size' => $this->getSyncQueueSize(),
            'broadcast_channel_health' => $this->checkBroadcastChannels(),
            'average_response_time' => $this->getAverageResponseTime()
        ];
    }
}
```

## Scalability Considerations

### Horizontal Scaling
- **Redis Clustering**: Distribute timer state across multiple Redis instances
- **Database Sharding**: Partition timer data by user ID or account ID
- **Load Balancing**: Distribute API requests across multiple Laravel instances

### Vertical Scaling
- **Connection Pooling**: Optimize database connections
- **Memory Management**: Efficient Redis memory usage
- **Query Optimization**: Regular analysis and optimization of slow queries