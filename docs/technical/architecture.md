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
- `UnifiedSelector`: Single selector for all entity types (tickets, accounts, users, etc.)
- `StackedDialog`: Native dialog-based modal system
- `TimerBroadcastOverlay`: Persistent timer interface

**Modal System**:
```vue
<!-- Old modal system (deprecated) -->
<Modal :show="isOpen" @close="closeModal">

<!-- New stacked dialog system -->
<StackedDialog :show="isOpen" @close="closeDialog" max-width="2xl">
```

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