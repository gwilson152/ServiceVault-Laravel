# Time Management System

Service Vault provides a comprehensive time management system with dual interfaces for time entries and active timer monitoring.

## Tabbed Time Interface

Service Vault provides a unified time management interface accessible at `/time-entries` with two main tabs:

### Time Entries Tab

- **Comprehensive Time Entry Management**: View, filter, and manage time entries with approval workflows
- **Advanced Filtering**: Status, billability, date ranges, and account-based filtering
- **Bulk Operations**: Approve/reject multiple entries with manager/admin permissions
- **Statistical Dashboard**: Total hours, entry counts, pending approvals, and average metrics
- **Permission-Based Actions**: Edit, delete, approve based on user roles and entry ownership

### Active Timers Tab  

- **RBAC/ABAC Permission Integration**: Users see timers based on their permission level
  - **Regular Users**: Own active timers via `/api/timers/user/active`
  - **Admins/Managers**: All active timers via `/api/admin/timers/all-active`
- **Real-Time Statistics**: Active timer count, total billing value, cumulative time
- **Timer Controls**: Pause, resume, stop, and commit operations with permission validation
- **Auto-Refresh**: Updates every 30 seconds for live status monitoring
- **Visual Indicators**: Color-coded status badges (running, paused) with formatted durations

## URL-Based Navigation

```bash
/time-entries/time-entries    # Time Entries tab (default)
/time-entries/timers         # Active Timers tab
```

## Permission Matrix

```php
// Timer Visibility Permissions
'timers.view.own'     // See own timers only
'timers.view.all'     // See all active timers (admin view)
'admin.read'          // Administrative timer access
'admin.write'         // Administrative timer control

// Timer Control Permissions  
'timers.manage.own'   // Control own timers (pause/resume/stop/commit)
'timers.manage.all'   // Control any timer (admin functionality)
```

## Timer Integration Features

- **Commit Workflow**: Convert active timers to time entries with automatic tab switching
- **Error Handling**: Comprehensive error messages for failed operations
- **Loading States**: Smooth UX with proper loading indicators
- **Mobile Responsive**: Full functionality across device sizes

## Time Entry Management API

```bash
# Core CRUD Operations
GET    /api/time-entries               # List time entries with filtering and pagination
POST   /api/time-entries               # Create new time entry 
GET    /api/time-entries/{id}          # Show specific time entry
PUT    /api/time-entries/{id}          # Update time entry
DELETE /api/time-entries/{id}          # Delete time entry

# Approval Workflow
POST   /api/time-entries/{id}/approve  # Approve time entry (managers/admins)
POST   /api/time-entries/{id}/reject   # Reject time entry (managers/admins)
POST   /api/time-entries/bulk/approve  # Bulk approve time entries
POST   /api/time-entries/bulk/reject   # Bulk reject time entries

# Statistics & Reporting
GET    /api/time-entries/stats/recent     # Recent statistics for dashboard
GET    /api/time-entries/stats/approvals  # Approval statistics (managers/admins)
```

## Component Architecture

- **`TimeEntries/Index.vue`**: Main tabbed interface component
- **`TimeEntries/TimersTab.vue`**: Active timers display with permission-based controls
- **`TimeEntryResource.php`**: API resource with proper duration calculations and permission flags

## Recent Fixes

- **TimeEntryResource Closure Scoping**: Fixed undefined `$request` variable in closures
- **Duration Field Corrections**: Proper conversion between minutes (storage) and seconds (display)
- **Missing Field Mappings**: Added `started_at` and `ended_at` fields, removed non-existent `date` field

For detailed timer architecture, see [Timer System Architecture](../architecture/timer-system.md).