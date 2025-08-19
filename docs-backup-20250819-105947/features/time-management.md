# Time Management System

Service Vault provides a comprehensive time management system with unified interfaces for time entries and active timer monitoring, built on TanStack Query for optimal performance and real-time updates.

## Tabbed Time Interface

Service Vault provides a unified time management interface accessible at `/time-entries` with two main tabs:

### Time Entries Tab

-   **TanStack Query Integration**: Optimized data fetching with automatic caching, background updates, and optimistic mutations
-   **Comprehensive Time Entry Management**: View, filter, and manage time entries with approval workflows
-   **Advanced Filtering**: Status, billability, date ranges, and account-based filtering with reactive query parameters
-   **Bulk Operations**: Approve/reject multiple entries with manager/admin permissions
-   **Statistical Dashboard**: Total hours, entry counts, pending approvals, and average metrics
-   **Permission-Based Actions**: Edit, delete, approve based on user roles and entry ownership
-   **Real-Time Updates**: Automatic cache invalidation on mutations for consistent data

### Active Timers Tab

-   **ABAC Permission System**: Comprehensive attribute-based access control for timer operations
    -   **View Permissions**: `timers.read`, `timers.admin`, `teams.manage`
    -   **Control Permissions**: `timers.write`, `timers.admin`
    -   **Create Permissions**: Agent-only timer creation with `timers.write`
-   **Permission-Based API Endpoints**:
    -   **Regular Users**: Own active timers via `/api/timers/user/active`
    -   **Admins/Managers**: All active timers via `/api/admin/timers/all-active`
-   **Real-Time Statistics**: Active timer count, total billing value, cumulative time
-   **Timer Controls**: Pause, resume, stop, and commit operations with permission validation
-   **Auto-Refresh**: Updates every 30 seconds for live status monitoring
-   **Visual Indicators**: Color-coded status badges (running, paused) with formatted durations

## URL-Based Navigation

```bash
/time-entries/time-entries    # Time Entries tab (default)
/time-entries/timers         # Active Timers tab
```

## ABAC Permission Matrix

```php
// Timer Visibility Permissions
'timers.read'         // View own timers
'timers.admin'        // View and control all timers (admin)
'teams.manage'        // View all timers (manager level)
'admin.read'          // Administrative timer access
'admin.manage'        // Full administrative control

// Timer Operation Permissions
'timers.write'        // Create, control own timers (pause/resume/stop/commit)
'timers.admin'        // Control any timer across all users
```

## Timer Broadcast Overlay ABAC Implementation

The timer broadcast overlay implements comprehensive permission checks:

```javascript
// Permission computeds in TimerBroadcastOverlay.vue
const canViewMyTimers = computed(() => {
    return (
        user.value?.permissions?.includes("timers.read") ||
        user.value?.permissions?.includes("timers.write")
    );
});

const canViewAllTimers = computed(() => {
    return (
        isAdmin.value ||
        user.value?.permissions?.includes("timers.admin") ||
        user.value?.permissions?.includes("teams.manage")
    );
});

const canControlTimers = computed(() => {
    return (
        user.value?.permissions?.includes("timers.write") ||
        user.value?.permissions?.includes("timers.admin")
    );
});

const canCommitTimers = computed(() => {
    return (
        user.value?.permissions?.includes("timers.write") ||
        user.value?.permissions?.includes("timers.admin")
    );
});

const canCreateTimers = computed(() => {
    return (
        user.value?.permissions?.includes("timers.write") ||
        user.value?.permissions?.includes("timers.admin")
    );
});
```

## Timer Integration Features

-   **Unified Time Entry Dialog**: All timer commit workflows use `UnifiedTimeEntryDialog.vue` component
-   **Multiline Work Descriptions**: Timer descriptions use textarea input with "Work Description" label
-   **Commit Workflow**: Convert active timers to time entries with automatic tab switching and data preloading
-   **Glass Effect Overlay**: Timer broadcast overlay with gradient glass morphism design
-   **Permission-Based Controls**: UI elements show/hide based on ABAC permissions
-   **Error Handling**: Comprehensive error messages for failed operations
-   **Loading States**: Smooth UX with proper loading indicators
-   **Mobile Responsive**: Full functionality across device sizes

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

## TanStack Query Integration

```javascript
// useTimeEntriesQuery.js - Comprehensive query management
export function useTimeEntriesQuery() {
    // Paginated time entries with reactive filters
    const useTimeEntriesListQuery = (optionsRef) => {
        return useQuery({
            queryKey: computed(() =>
                queryKeys.timeEntries.list({
                    status: optionsRef.status,
                    billable: optionsRef.billable,
                    date_from: optionsRef.date_from,
                    date_to: optionsRef.date_to,
                    page: optionsRef.page || 1,
                })
            ),
            queryFn: async () => {
                /* API call */
            },
            staleTime: 1000 * 60 * 2, // 2 minutes
            keepPreviousData: true,
        });
    };

    // Optimistic mutations with automatic cache invalidation
    const approveTimeEntryMutation = useMutation({
        mutationFn: async (timeEntryId) => {
            /* API call */
        },
        onMutate: async (timeEntryId) => {
            // Optimistic update
            queryClient.setQueryData(queryKey, {
                ...previousData,
                status: "approved",
            });
        },
        onSuccess: () => {
            // Invalidate related queries
            queryClient.invalidateQueries({
                queryKey: queryKeys.timeEntries.all,
            });
        },
    });
}
```

## Component Architecture

-   **`TimeEntries/Index.vue`**: Main tabbed interface with TanStack Query integration
-   **`TimeEntries/TimersTab.vue`**: Active timers display with ABAC permission-based controls
-   **`TimeEntries/UnifiedTimeEntryDialog.vue`**: Unified dialog for all time entry operations
-   **`Timer/TimerConfigurationForm.vue`**: Form component with multiline work descriptions
-   **`Timer/TimerBroadcastOverlay.vue`**: Real-time timer overlay with glass effect and ABAC permissions
-   **`Composables/queries/useTimeEntriesQuery.js`**: TanStack Query composable for time entries
-   **`TimeEntryResource.php`**: API resource with proper duration calculations and permission flags

## Recent Major Updates (Phase 15A+)

### API Fixes & Optimizations

-   **TimeEntryResource Closure Scoping**: Fixed undefined `$request` variable in closures
-   **Duration Field Corrections**: Proper conversion between minutes (storage) and seconds (display)
-   **Missing Field Mappings**: Added `started_at` and `ended_at` fields, removed non-existent `date` field
-   **500 Error Resolution**: Fixed database column mapping issues in time entries API

### TanStack Query Migration

-   **Complete Migration**: Replaced axios with TanStack Query for all time entry operations
-   **Reactive Query Parameters**: Filters automatically trigger re-fetching
-   **Optimistic Updates**: Instant UI feedback with automatic rollback on errors
-   **Cache Management**: Intelligent cache invalidation and background refetching

### ABAC Permission System

-   **Comprehensive Permission Checks**: All timer operations validate user permissions
-   **UI Permission Integration**: Show/hide controls based on user abilities
-   **Timer Owner Validation**: Users can only control their own timers unless admin
-   **Agent vs Customer Filtering**: Timer creation restricted to agents only

### Component Unification

-   **Unified Time Entry Dialog**: Consolidated all time entry workflows into single component
-   **Fixed Missing Imports**: Resolved CommitTimeEntryDialog references to use UnifiedTimeEntryDialog
-   **Multiline Work Descriptions**: Enhanced timer description input with textarea
-   **Left-Aligned Timer Interface**: Timer overlay and individual timers now align left for improved visual hierarchy
-   **Enhanced Timer Dialog**: Auto-selection of current agent and default billing rates with merged single-tab interface

For detailed timer architecture, see [Timer System Architecture](../architecture/timer-system.md).
