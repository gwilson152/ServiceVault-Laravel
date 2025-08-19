# Timer System Guide

Complete guide to Service Vault's multi-timer system with real-time synchronization.

## Overview

Service Vault supports **concurrent timers** - run multiple timers simultaneously and track time across different tickets/accounts. Timers sync in real-time across all devices and browsers.

### Key Features

- **✅ Concurrent Timers**: Run multiple timers for different tickets/accounts
- **✅ Cross-Device Sync**: Timer state synced via Redis across all devices
- **✅ Real-Time Updates**: WebSocket broadcasting for live timer updates
- **✅ Persistent Overlay**: Timer overlay persists across page navigation
- **✅ Billing Integration**: Automatic billing rate application and calculation

## Using Timers

### Starting a Timer

1. **From Ticket Page**: Click "Start Timer" button on any ticket
2. **From Timer Overlay**: Click "+" to start new timer
3. **From Time Management**: Navigate to `/time-entries` and use timers tab

### Timer Configuration

When starting a timer, configure:

- **Description**: What work you're performing
- **Account**: Which client account (auto-populated from ticket)
- **Ticket**: Which specific ticket (if applicable)
- **User/Agent**: Who is performing the work
- **Billing Rate**: Hourly rate for this work

### Timer Controls

**Timer Overlay** (bottom-left of screen):
- **Play/Pause**: Start/pause individual timers
- **Stop**: Stop timer and optionally convert to time entry
- **Commit**: Stop timer and convert to time entry with full dialog
- **Edit**: Modify timer description, billing rate, etc.
- **Settings**: Configure timer preferences

### Editing Running Timers

Click the "Edit" (⚙️) button on any timer to modify:
- Description
- Account assignment
- Ticket assignment  
- User/agent assignment
- Billing rate

**Recent Fix**: Timer edit modal now properly displays pre-selected values for all fields.

### Converting Timers to Time Entries

**Timer Commit Process** (Recent Enhancement):
1. **Commit Option**: Use "Commit to Time Entry" from timer overlay dropdown
2. **Unified Dialog**: Opens the same time entry dialog used for manual entries
3. **Pre-Population**: All timer data (description, account, ticket, user) automatically filled
4. **Agent Assignment**: User who ran the timer is pre-selected as the assignee
5. **Review & Save**: Modify any details before creating the time entry

**Key Behavior**: Timer commit uses the same agent loading logic as manual time entry creation, showing all available time agents regardless of account association.

## Time Entry Management

Navigate to `/time-entries` for unified time management:

### Time Entries Tab
- View all logged time entries
- Approve/reject time entries (managers/admins)
- Filter by date range, account, user, etc.
- Export time data

### Active Timers Tab  
- View all active timers (based on your permissions)
- Control timers you have access to
- Monitor team timer activity (managers/admins)

## Permissions & Access

### Timer Permissions

- **`timers.act_as_agent`**: Can create and be assigned timers
- **`timers.view`**: Can view own timers
- **`timers.manage`**: Can view and control all timers (managers/admins)

### Agent Assignment

**Timer Agent Eligibility** - Users can act as timer agents if they have:
1. `user_type = 'agent'` (primary agents)
2. `timers.act_as_agent` permission (extended agents)
3. Admin permissions (`admin.write`, super admin)

**Time Entry Agent Eligibility** - Users can be assigned time entries if they have:
1. `time.act_as_agent` permission (feature-specific)
2. `time.assign`, `time.manage` permissions (management)
3. Admin permissions (`admin.write`, super admin)

**Agent Loading Behavior**:
- **Timer Creation**: Shows all available timer agents (no account filtering)
- **Time Entry Creation**: Shows all available time agents (no account filtering)
- **Timer Commit**: Uses unified time entry dialog with same agent loading logic

**Permission Service**: All agent filtering uses the centralized `PermissionService` to ensure consistent behavior and proper Super Admin inheritance.

## API Integration

### Key Endpoints

```bash
# Timer Management
GET    /api/timers                     # List user timers (paginated)
POST   /api/timers                     # Start new timer
GET    /api/timers/active/current      # Get all active timers with totals
POST   /api/timers/{timer}/stop        # Stop timer
POST   /api/timers/{timer}/pause       # Pause running timer
POST   /api/timers/{timer}/resume      # Resume paused timer
POST   /api/timers/{timer}/commit      # Stop and convert to time entry
PUT    /api/timers/{timer}             # Update timer details
DELETE /api/timers/{timer}?force=true  # Force delete timer

# Bulk Operations
POST   /api/timers/sync                # Cross-device timer synchronization
POST   /api/timers/bulk                # Bulk operations (stop, pause, resume, delete)
```

### Timer State Management

Timers use hybrid storage:
- **Database**: Persistent timer records
- **Redis**: Real-time state sync (`user:{user_id}:timer:{timer_id}:state`)
- **WebSocket**: Live updates via Laravel Reverb

## Technical Details

### Performance Optimization

**Recent Enhancement**: Eliminated N+1 query problem by embedding timer data in ticket API responses:
- Reduced 10+ API calls to 1
- Timer data included automatically in ticket lists
- Maintains real-time compatibility

### Navigation Compatibility

**⚠️ Important**: Always use Inertia.js navigation to maintain timer overlay:

```vue
<!-- ✅ CORRECT - Maintains timer state -->
<Link :href="`/tickets/${ticket.id}`">View Ticket</Link>

<!-- ❌ WRONG - Breaks timer overlay -->
<a :href="`/tickets/${ticket.id}`">View Ticket</a>
```

### Real-Time Features

Timer updates broadcast to:
- **User Channel**: `user.{user_id}` (own timers)
- **Account Channel**: `account.{account_id}` (team timers)
- **Ticket Channel**: `ticket.{ticket_id}` (ticket-specific timers)

## Troubleshooting

### Timer Overlay Missing
- Check for non-Inertia navigation (HTML `<a>` tags)
- Verify WebSocket connection in browser console
- Ensure Redis is running and connected

### Timer Not Saving Data
- Verify validation rules in `StoreTimerRequest` and `UpdateTimerRequest`
- Check that ticket_id and account_id fields are properly populated
- **Recent Fix**: Request validation now includes all required fields

### Cross-Device Sync Issues
- Check Redis connection and configuration
- Verify WebSocket server (Laravel Reverb) is running
- Clear browser cache and localStorage

For technical implementation details, see [Timer Architecture](../technical/architecture.md#timer-system).