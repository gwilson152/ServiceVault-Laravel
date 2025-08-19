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
- **Billing Rate**: Hourly rate for this work (auto-selects appropriate default)

### Timer Controls

**Timer Overlay** (bottom-left of screen):
- **Play/Pause**: Start/pause individual timers
- **Cancel**: Cancel timer without creating time entry (marks as `canceled`)
- **Stop & Commit**: Stop timer and convert to time entry with full dialog (marks as `committed`)
- **Edit**: Modify timer description, billing rate, etc.
- **Settings**: Configure timer preferences
- **Minimize All**: Collapse all expanded timers to compact badge view

### Editing Running Timers

Click the "Edit" (⚙️) button on any timer to modify:
- Description
- Account assignment
- Ticket assignment  
- User/agent assignment
- Billing rate

**Recent Fix**: Timer edit modal now properly displays pre-selected values for all fields.

### Converting Timers to Time Entries

**Timer Commit Process** (Enhanced August 2025):
1. **Commit Option**: Use "Commit to Time Entry" from timer overlay dropdown
2. **Unified Dialog**: Opens the same time entry dialog used for manual entries
3. **Pre-Population**: All timer data (description, account, ticket, user) automatically filled
4. **Agent Assignment**: User who ran the timer is pre-selected as the assignee
5. **Billing Rate Preservation**: Timer's original billing rate is preserved, even if account has overriding rates
6. **Review & Save**: Modify any details before creating the time entry

**Key Behaviors**:
- Timer commit uses the same agent loading logic as manual time entry creation, showing all available time agents regardless of account association
- **Billing Rate Handling**: The exact billing rate assigned to the timer is preserved during commit, ensuring rate consistency across timer lifecycle

## Time Entry Management

Navigate to `/time-entries` for unified time management:

### Time Entries Tab
- View all logged time entries
- Approve/reject time entries (managers/admins)
- Filter by date range, account, user, etc.
- Export time data

### Active Timers Tab  
- **Permission-Based Access**: View your own timers or all timers based on ABAC permissions
- **Enhanced Timer Controls**: Pause, Resume, Cancel, Stop, and Commit actions with granular permission checks
- **Real-Time Updates**: Auto-refresh every 30 seconds with live timer calculations
- **Comprehensive Information**: Shows timer description, status, duration, account, ticket, billing rate, and calculated cost
- **Multi-Level Management**: Own timers vs administrative control with proper endpoint selection

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

## Billing Rate Behavior

### Timer Creation Auto-Selection

**Billing Rate Priority** (when starting new timers):
1. **Account-Specific Default**: If account has a default billing rate
2. **Global Default**: System-wide default billing rate  
3. **First Available**: First account rate, then first global rate

**Rate Availability**:
- **Account Selected**: Shows all account-specific rates + all global rates
- **No Account**: Shows only global rates
- **Rate Display**: Shows rate name, description, and hourly amount

### Timer Commit Rate Preservation

**Enhanced Rate Handling** (August 2025):
- **Original Rate Preserved**: Timer's billing rate is maintained during commit
- **Override Protection**: Even if account has rates with same names, timer's exact rate is used
- **Rate Disambiguation**: UI clearly shows "Rate Name (Original)" vs account rates
- **Consistent Billing**: Ensures timer billing remains consistent from start to time entry

**Technical Implementation**:
- Timer commit API includes `include_rate_id` parameter to ensure rate availability
- UnifiedSelector enhanced with rate amount display and disambiguation
- BillingRateService extended to handle timer-specific rate inclusion

## API Integration

### Key Endpoints

```bash
# Timer Management
GET    /api/timers                     # List user timers (paginated)
POST   /api/timers                     # Start new timer
GET    /api/timers/active/current      # Get all active timers with totals
GET    /api/timers/active-with-controls # Enhanced active timers with ABAC permissions
POST   /api/timers/{timer}/stop        # Stop timer
POST   /api/timers/{timer}/pause       # Pause running timer
POST   /api/timers/{timer}/resume      # Resume paused timer
POST   /api/timers/{timer}/cancel      # Cancel timer (marks as canceled)
POST   /api/timers/{timer}/commit      # Stop and convert to time entry
PUT    /api/timers/{timer}             # Update timer details
DELETE /api/timers/{timer}?force=true  # Force delete timer

# Administrative Timer Management (Requires admin permissions)
GET    /api/admin/timers/all-active    # Get all active timers system-wide
POST   /api/admin/timers/{timer}/pause # Admin pause any timer
POST   /api/admin/timers/{timer}/resume # Admin resume any timer
POST   /api/admin/timers/{timer}/stop  # Admin stop any timer
POST   /api/admin/timers/{timer}/cancel # Admin cancel any timer

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

### Billing Rate Selection Issues

**Timer Creation - Rate Not Auto-Selecting**:
1. Verify account has a default billing rate set
2. Check global default billing rate exists
3. Confirm user has `timers.view` permission
4. Check browser console for billing rate loading errors

**Timer Commit - Rate Not Preserved**:
1. Verify timer has `billing_rate_id` in database
2. Check that BillingRateService includes timer's specific rate
3. Look for "Timer-specific" rates in dropdown with "(Original)" suffix
4. Ensure API call includes `include_rate_id` parameter

**Billing Rates Not Showing Amounts**:
- **Fixed**: All billing rates now display "Description • $XX.XX/hr" format
- Clear browser cache if old format persists
- Verify `rate` field exists in billing rate data

**Account vs Global Rate Conflicts**:
- **Expected Behavior**: Account rates override global rates with same names
- **Timer Preservation**: Original timer rates preserved during commit
- **UI Clarity**: Timer-specific rates show as "Rate Name (Original)"

For technical implementation details, see [Timer Architecture](../technical/architecture.md#timer-system).