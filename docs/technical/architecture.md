# System Architecture

Technical overview of Service Vault's architecture, database design, and key systems.

## Technology Stack

### Backend
- **Laravel 12**: PHP framework with modern features
- **PostgreSQL**: Primary database
- **Redis**: Session storage, caching, and timer state management
- **Laravel Sanctum**: Hybrid authentication (session + API tokens)
- **Laravel Reverb**: WebSocket server for real-time features

### Frontend
- **Vue.js 3.5**: Component framework with Composition API
- **Inertia.js**: SPA navigation without API complexity
- **TanStack Query**: Data fetching, caching, and synchronization
- **Tailwind CSS**: Utility-first styling
- **Headless UI**: Accessible component primitives

### Infrastructure
- **Nginx**: Web server and reverse proxy
- **Laravel Horizon**: Queue worker management
- **Supervisor**: Process monitoring
- **Docker**: Containerized development environment

## Core Systems

### Universal Import System (Production-Ready)

**Architecture Overview**:
- **Database-Agnostic Design**: Supports any PostgreSQL database with intelligent schema introspection
- **Template-Based Configuration**: Pre-built platform patterns (FreeScout, Custom) with JSON storage
- **Visual Query Builder**: Fullscreen interface with centralized state management and persistent configurations
- **Reactive Loop Prevention**: Eliminated infinite update cycles through controlled mutations and async safety guards
- **Session Persistence**: Saved query configurations automatically restore after page reload
- **Real-Time Monitoring**: WebSocket-based job tracking with floating progress monitor
- **Enterprise Audit Trails**: UUID-based record tracking with complete lineage and rollback capabilities
- **Automated Sync Scheduling**: Configurable frequencies with Laravel scheduler integration
- **Profile Duplication**: Complete configuration cloning with settings preservation
- **Enhanced SQL Generation**: Proper table prefixing in JOIN conditions for frontend and backend consistency

**Key Components**:
```php
// Unified SQL generation for validation and sample data
public function generateSQL(array $config): string
{
    return $this->buildSelectClause($config)
         . $this->buildFromClause($config)
         . $this->buildJoinClauses($config)
         . $this->buildWhereClause($config);
}

// Consistent query preview and validation
$query = $this->generateSQL($request->all());
```

**Import Modes**:
- **Create Only**: New records only, skip existing ones
- **Update Only**: Existing records only, skip new ones
- **Upsert**: Smart handling of both new and existing records

**Duplicate Detection Strategies**:
- **Exact Match**: Perfect field-by-field matching
- **Case-Insensitive**: Handles case variations
- **Fuzzy Match**: Levenshtein distance with configurable thresholds

**Database Schema**:
```sql
import_profiles (id, name, database_config, ssl_mode, notes, 
                 sync_enabled, sync_frequency, sync_time, sync_timezone,
                 last_sync_at, next_sync_at, sync_options, sync_stats, ...)
import_jobs (id, profile_id, status, progress, records_imported, ...)
import_records (id, job_id, source_table, target_type, import_action, ...)
import_templates (id, name, platform, configuration, ...)
import_queries (id, profile_id, name, base_table, joins, fields, filters, ...)
```

**Sync Scheduling Fields**:
- `sync_enabled`: Boolean flag for automated execution
- `sync_frequency`: hourly, daily, weekly, monthly, custom
- `sync_time`: Time of day for execution (HH:MM format)
- `sync_timezone`: Timezone for scheduling calculations
- `sync_cron_expression`: Custom cron pattern for complex schedules
- `sync_options`: JSON configuration for batch size, timeouts, etc.
- `sync_stats`: Historical sync performance and statistics

**Real-Time Broadcasting**:
- `import.job.{job_id}`: Individual job progress updates
- `import.profile.{profile_id}`: Profile-related events
- `user.{user_id}`: Personal import notifications

### Authentication System

**Hybrid Authentication Architecture**:
- **Web Interface**: Laravel Breeze session-based authentication
- **API Access**: Laravel Sanctum token-based authentication
- **Cross-Platform**: Unified policy system supports both methods

**Key Components**:
```php
// Unified permission checking
if ($user->currentAccessToken()) {
    return $user->tokenCan('tickets:read');
}
return $user->hasPermission('tickets.view');
```

**CSRF Protection**:
- Automatic token refresh every 10 minutes
- Proactive 419 error handling with retry
- Global `refreshCSRFToken()` function

### Three-Dimensional Permission System

Service Vault implements a unique three-layer permission architecture:

**1. Functional Permissions** (what users can DO):
```php
$user->hasPermission('tickets.create');
$user->hasPermission('timers.manage');
```

**2. Widget Permissions** (what users SEE on dashboard):
```php
$user->hasPermission('widgets.dashboard.tickets');
$user->hasPermission('widgets.dashboard.billing');
```

**3. Page Permissions** (what pages users can ACCESS):
```php
$user->hasPermission('pages.users.manage');
$user->hasPermission('pages.billing.rates');
```

**Storage**: Role templates store three separate arrays with unified checking via `hasPermission()` method.

### Multi-Timer System

**Architecture Overview**:
- **Concurrent Timers**: Multiple active timers per user
- **Hybrid Storage**: Database + Redis for performance
- **Real-Time Sync**: WebSocket broadcasting across devices

**State Management**:
```
Redis Keys: user:{user_id}:timer:{timer_id}:state
Database: timers table for persistence
WebSocket: Live updates via Laravel Reverb
```

**Performance Optimization**:
- Eliminated N+1 queries by embedding timer data in ticket responses
- Reduced 10+ API calls to single request
- Redis-backed state synchronization

### Unified Selector System

Service Vault uses a **self-managing** `UnifiedSelector` component for consistent entity selection across the entire application. Selectors automatically handle their own data loading, search, caching, and permissions.

**Supported Types**:
- `ticket` - Ticket selection with creation support
- `account` - Account selection with creation support  
- `user` - User selection (customer users)
- `agent` - Agent selection with feature-specific types (timer, ticket, time, billing)
- `billing-rate` - Billing rate selection with hierarchy display
- `role-template` - Role template selection for user assignment

**Key Features**:
- **Self-Managing Data**: Automatic data loading, search, and caching via TanStack Query
- **Permission-Aware**: Built-in permission filtering based on user context
- **Case-Insensitive Search**: Debounced search with PostgreSQL ILIKE for smooth UX
- **Recent Items Tracking**: localStorage-based recent selections with API fallback
- **Custom Sorting**: Configurable sort field and direction per selector
- **Focus Preservation**: Input maintains focus during search operations
- **Proper Dropdown Dismissal**: Fixed issue where dropdowns wouldn't close after selecting searched items
- **Creation Support**: Built-in "Create New" options with proper modal stacking
- **Type-Specific Configurations**: Icons, colors, badges, and behaviors per type
- **Modal Stacking**: `nested` prop for proper z-index management

**Usage Example (New Self-Managing Architecture)**:
```vue
<UnifiedSelector
  v-model="selectedId"
  type="account"
  label="Account"
  placeholder="Select account..."
  sort-field="name"
  sort-direction="asc"
  :can-create="true"
  :nested="true"
  :clearable="true"
  @item-selected="handleSelection"
  @item-created="handleCreation"
/>
```

**Available Props**:
- `type` - Entity type (required)
- `sort-field` - Custom sort field (e.g., 'name', 'created_at')
- `sort-direction` - Sort direction ('asc' or 'desc')
- `agent-type` - For agent selectors: 'timer', 'ticket', 'time', 'billing'
- `filter-set` - Applied filters for context-aware results
- `custom-items` - Override with custom dataset for special cases
- `clearable` - Allow clearing selection (default: true)
- `recent-items-limit` - Number of recent items to show (default: 10)
- `search-min-length` - Minimum characters before API search (default: 2)

**Migration from Old Pattern**:
```vue
<!-- OLD: Manual data management -->
<UnifiedSelector :items="availableAccounts" />

<!-- NEW: Self-managing (no items prop needed) -->
<UnifiedSelector type="account" />
```

**Technical Implementation**:
- **Query Composables**: `/resources/js/Composables/queries/useSelectorQueries.js`
- **Search API Controller**: `/app/Http/Controllers/Api/SearchController.php`
- **Permission Integration**: Automatic filtering based on user context and abilities
- **TanStack Query**: Optimized caching with reactive query keys
- **Recent Items**: localStorage persistence with `selector_recent_{type}` keys

### StackedDialog Architecture

Service Vault uses a native `<dialog>`-based modal system for proper stacking and accessibility:

**Key Features**:
- **Native Dialog Elements**: Uses HTML5 `<dialog>` for proper modal behavior
- **Automatic Stacking**: Manages z-index automatically for nested modals
- **Consistent Header**: Unified header with title and close button
- **Vertical Expansion**: Dialogs expand to fit content without artificial height limits
- **Teleport Support**: Proper rendering outside component tree

**Usage Example**:
```vue
<StackedDialog
  :show="isOpen"
  title="Dialog Title"
  max-width="2xl"
  @close="closeDialog"
>
  <!-- Dialog content -->
</StackedDialog>
```

**Modal Conversion**: All core modals have been converted from the old `Modal` component to `StackedDialog` for consistency and proper stacking behavior.

**Enhanced Approval Wizard Integration** (August 2025):
The ApprovalWizardModal has been converted to use StackedDialog architecture:
- **Proper Z-Index Management**: Eliminates dropdown clipping issues in account selection
- **Consistent UI**: Unified header with title and subtitle for account context

### StandardPageLayout System

Service Vault implements a unified layout architecture for consistent page structure across the entire application:

**Core Components**:
- **`StandardPageLayout.vue`**: Main layout wrapper with configurable slots
- **`FilterSection.vue`**: Standardized filter container with collapsible mobile behavior
- **`PageHeader.vue`**: Consistent page headers with title and action areas
- **`PageSidebar.vue`**: Sidebar wrapper with automatic spacing

**Layout Structure**:
```vue
<StandardPageLayout
  :title="pageTitle"
  subtitle="Page description"
  :show-sidebar="true"
  :show-filters="true"
>
  <template #header-actions>
    <!-- Action buttons -->
  </template>
  <template #filters>
    <!-- Filter components -->
  </template>
  <template #main-content>
    <!-- Primary content -->
  </template>
  <template #sidebar>
    <!-- Stats, widgets, etc. -->
  </template>
</StandardPageLayout>
```

**Key Features**:
- **Responsive Design**: Mobile-first with automatic sidebar/filter collapsing
- **Full-Width Support**: No artificial width constraints on desktop
- **Configurable Slots**: Flexible content areas for different page types
- **Mobile Toggle Controls**: Automatic mobile sidebar and filter toggles
- **Consistent Spacing**: Standardized padding and margins across all pages

**Implementation Benefits**:
- **Code Consistency**: All pages follow same layout patterns
- **Maintainability**: Centralized layout logic reduces duplication
- **Responsive UX**: Unified mobile behavior across all pages
- **Developer Experience**: Easy to implement new pages with standard structure

**Current Implementation**: Initially deployed on tickets page (`/resources/js/Pages/Tickets/Index.vue`) with plans to extend to other pages once validated.

### Enhanced Filtering System

Service Vault features an intelligent multi-select filtering system with user-specific persistence:

**MultiSelect Component** (`/resources/js/Components/UI/MultiSelect.vue`):
- **Dropdown Interface**: Visual multi-select with checkboxes and "Select All/Clear All" options
- **Smart Display**: Shows selected count or individual items based on selection size
- **Keyboard Navigation**: Full accessibility support with proper focus management
- **Click Outside**: Automatic dropdown closing with event management

**Filter Persistence Architecture**:
```javascript
// User-specific localStorage keys
const getStorageKey = (key) => `tickets-filters-${user.id}-${key}`

// Default filter logic
const getDefaultStatuses = () => {
  return ticketStatuses.filter(status => status.key !== 'closed')
}
```

**Key Features**:
- **Smart Defaults**: Excludes closed tickets on initial load for better user experience
- **Multi-Select Arrays**: Status and priority filters support multiple selection values
- **Per-User Persistence**: Filter preferences saved uniquely per user in localStorage
- **Automatic Restoration**: Filters restore on page reload with fallback to smart defaults
- **Array Parameter Handling**: Backend API supports both single values and arrays (`status[]`, `priority[]`)

**API Integration**:
- **Query Parameters**: `useTicketsQuery.js` handles both string and array filter values
- **Backward Compatibility**: Supports legacy single-value filters while enabling multi-select
- **TanStack Query**: Reactive query keys ensure proper cache invalidation on filter changes

**Implementation Benefits**:
- **Better UX**: Users see relevant tickets (non-closed) by default
- **Flexible Filtering**: Multi-select enables complex filtering scenarios
- **Stateful Experience**: Remembers user preferences across sessions
- **Performance**: Efficient localStorage with user-scoped keys prevents conflicts

### Real-Time Broadcasting

**WebSocket Channels**:
- `user.{id}`: Personal notifications and timer updates
- `account.{id}`: Team-wide updates and announcements  
- `ticket.{id}`: Ticket-specific comments and status changes

**Event Types**:
- `comment.created`: New ticket comments
- `timer.started/stopped`: Timer state changes
- `ticket.updated`: Status/assignment changes

## Database Design

### Core Tables

**Users & Authentication**:
```sql
users (id, name, email, user_type, account_id, ...)
role_templates (id, name, permissions[], widget_permissions[], page_permissions[])
personal_access_tokens (Laravel Sanctum tokens)
```

**Account Management**:
```sql
accounts (id, name, description, parent_id, settings, ...)
domain_mappings (id, domain_pattern, account_id, priority, ...)
```

**Ticketing System**:
```sql
tickets (id, ticket_number, title, description, status, priority, ...)
ticket_categories (id, name, color, sla_hours, ...)
ticket_statuses (id, name, color, workflow_order, ...)
ticket_priorities (id, name, level, escalation_hours, ...)
ticket_comments (id, ticket_id, user_id, comment, is_internal, ...)
```

**Time & Billing**:
```sql
timers (id, user_id, account_id, ticket_id, billing_rate_id, status, ...)
time_entries (id, user_id, account_id, ticket_id, duration, billing_rate_id, ...)
billing_rates (id, name, rate, account_id, is_default, ...)
invoices (id, account_id, total_amount, status, due_date, ...)
```

### Key Relationships

**Account Hierarchy**:
- Recently simplified - removed complex nested hierarchy
- Parent-child relationships for organizational structure
- Inheritance for billing rates and settings

**Permission Inheritance**:
- Role templates define permission sets
- Users inherit permissions from assigned roles
- Feature-specific agent permissions for granular control

**Billing Rate Priority**:
1. Account-specific rates (highest priority)
2. Global default rates (fallback)

## Frontend Architecture

### Component Structure

**Core Components**:
- `UnifiedSelector`: Self-managing selector for all entity types with automatic data loading
- `StackedDialog`: Native dialog-based modal system with proper z-index management
- `TimerBroadcastOverlay`: Persistent timer interface with real-time sync

### State Management

**TanStack Query Integration**:
- Optimized data fetching and caching
- Automatic background updates
- Error handling and retry logic

**Real-Time Updates**:
```javascript
// Vue composables for WebSocket integration
const { timers, isConnected } = useTimerSync()
const { comments } = useTicketComments(ticketId)
```

### Centralized State Management (Latest Architecture)

**Composable-Based Architecture**:
Service Vault implements advanced state management through Vue composables, eliminating common reactive pitfalls and providing robust data flow:

**Visual Query Builder State Management**:
```javascript
// Centralized query store with controlled mutations
import { useQueryBuilder } from '@/composables/useQueryBuilder'

const queryBuilder = useQueryBuilder({
  base_table: 'customers',
  joins: [],
  fields: [],
  filters: [],
  target_type: 'customer_users'
})

// Controlled mutations prevent recursive loops
await queryBuilder.setBaseTable(table)
await queryBuilder.setJoins(joins)
await queryBuilder.setFields(fields)

// Read-only reactive state
const { queryConfig, isValidQuery } = queryBuilder
```

**Key Architecture Features**:
- **🔒 Controlled Mutations**: Async methods with safety guards prevent cascading updates
- **🎯 Single Source of Truth**: Centralized state eliminates conflicting reactive sources
- **⚡ Performance Optimized**: Eliminates infinite loop scenarios that plagued earlier iterations
- **💾 Session Persistence**: Automatically saves/restores configuration across page reloads
- **🔄 Real-Time Sync**: All components stay synchronized through centralized store

**Benefits Over Direct v-model**:
```javascript
// ❌ OLD: Direct reactive refs caused infinite loops
const queryConfig = ref({ base_table: '', joins: [], fields: [] })
// Components directly mutated queryConfig causing cascading updates

// ✅ NEW: Controlled mutations with safety guards
const queryBuilder = useQueryBuilder(initialConfig)
await queryBuilder.setBaseTable(table) // Safe, async, controlled
```

### Navigation Architecture

**Inertia.js SPA**:
- Server-side routing with client-side navigation
- Maintains timer overlay across page transitions
- No API complexity for standard CRUD operations

**⚠️ Critical Rule**: Always use Inertia navigation to maintain timer state:
```vue
<!-- ✅ CORRECT -->
<Link :href="url">Navigate</Link>

<!-- ❌ WRONG - breaks timer overlay -->
<a :href="url">Navigate</a>
```

## Performance Considerations

### Database Optimization

**Query Optimization**:
- Eager loading for relationships
- Database indexes on frequently queried fields
- Pagination for large data sets

**Recent Improvements**:
- Timer data embedded in ticket API responses
- Eliminated N+1 query problems in ticket lists
- Optimized permission checking queries

### Caching Strategy

**Redis Caching**:
- Session storage (120-minute lifetime)
- Timer state synchronization
- Permission caching for performance

**Application Caching**:
- Route caching for production
- View compilation caching
- Configuration caching

### Real-Time Performance

**WebSocket Optimization**:
- Connection pooling and management
- Selective channel subscriptions
- Efficient event broadcasting

## Security Architecture

### Authentication Security

**Session Security**:
- HTTPS-only cookies in production
- Secure/HttpOnly cookie flags
- CSRF token rotation

**API Security**:
- Rate limiting on all endpoints
- Token-based authentication with abilities
- Input validation and sanitization

### Authorization Security

**Permission Checking**:
- Policy-based authorization for all resources
- Three-dimensional permission validation
- Account isolation and access control

**Data Protection**:
- Encrypted sensitive data at rest
- Secure password hashing (bcrypt)
- Audit logging for sensitive operations

## Deployment Architecture

### Production Environment

**Server Configuration**:
- Nginx reverse proxy
- PHP-FPM process management
- Redis cluster for high availability
- PostgreSQL with connection pooling

**Process Management**:
- Laravel Horizon for queue processing
- Supervisor for process monitoring  
- Laravel Reverb WebSocket server
- Automated log rotation

### Monitoring & Observability

**Application Monitoring**:
- Laravel Telescope (development)
- Error tracking and reporting
- Performance monitoring
- Database query analysis

**Infrastructure Monitoring**:
- Server resource monitoring
- Database performance tracking
- Redis memory usage
- WebSocket connection monitoring

For deployment guides and configuration details, see [Development Setup](../guides/setup.md).

---

*System Architecture | Service Vault Technical Documentation | Updated: August 22, 2025*