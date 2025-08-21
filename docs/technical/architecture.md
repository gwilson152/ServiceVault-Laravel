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
- **StackedDialog Components**: All billing and approval workflows use native dialog elements
- **Nested Modal Support**: Approval wizard can launch from both Time & Addons and Billing pages without z-index conflicts

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