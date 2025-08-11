# Architecture Documentation

System architecture, design patterns, and technical specifications for Service Vault.

## Core System Architecture

### Permission & Authorization
- **[Three-Dimensional Permissions](three-dimensional-permissions.md)** - Functional + Widget + Page permission architecture ✅ CURRENT
- **[ABAC Permission System](abac-permission-system.md)** - Attribute-based access control with role templates ✅ CURRENT 
- **[Authentication & Authorization](auth-system.md)** - Hybrid session + token auth system ✅ CURRENT

### Business Systems
- **[Business Account System](business-account-system.md)** - Hierarchical B2B account management ✅ CURRENT
- **[Timer System](timer-system.md)** - Multi-timer architecture with cross-device sync ✅ CURRENT
- **[Widget System](widget-system.md)** - Permission-driven dashboard widgets ✅ CURRENT

## Technology Stack

### Backend Architecture
- **Laravel 12**: PHP framework with modern features
- **PostgreSQL**: Primary database with UUID support
- **Redis**: Caching, session storage, timer state management
- **Laravel Sanctum**: API authentication with granular abilities
- **Laravel Echo**: WebSocket broadcasting for real-time features

### Frontend Architecture
- **Vue.js 3.5**: Reactive frontend framework with Composition API
- **Inertia.js**: SPA-style navigation with server-side routing
- **Tailwind CSS**: Utility-first styling framework
- **Headless UI**: Accessible component primitives
- **Vite**: Fast development and build tooling

### Real-Time Features
- **WebSocket Broadcasting**: Laravel Echo + Pusher/Socket.io
- **Timer Synchronization**: Redis-based cross-device state management
- **Live Updates**: Real-time UI updates for timers, tickets, notifications

## Database Architecture

### Core Design Patterns
- **UUID Primary Keys**: All models use UUID for better security and distribution
- **Soft Deletes**: Audit trail preservation with deleted_at timestamps
- **Hierarchical Data**: Account parent-child relationships with unlimited nesting
- **Polymorphic Relations**: Flexible associations (comments, attachments, etc.)

### Key Database Features
- **Account Hierarchy**: Parent-child account relationships
- **Role Templates**: JSON-based permission storage
- **Timer State**: PostgreSQL + Redis hybrid storage
- **Audit Logging**: Comprehensive change tracking

## Security Architecture

### Authentication Methods
1. **Session Authentication**: Laravel Breeze for web interface
2. **API Token Authentication**: Laravel Sanctum with abilities
3. **Domain-Based Assignment**: Automatic user-to-account mapping

### Authorization Framework
- **Three-Dimensional Permissions**: Functional, Widget, Page access control
- **Account Hierarchy**: Permission inheritance through business relationships
- **Context-Aware**: Service provider vs account user permission scoping
- **Token Abilities**: Granular API access control (23+ abilities)

### Data Protection
- **Account Isolation**: Complete data separation between business accounts
- **Permission Filtering**: All queries automatically scoped by user permissions
- **Input Validation**: Laravel form requests with comprehensive validation
- **XSS Protection**: Vue.js template escaping and CSP headers

## Performance Architecture

### Caching Strategy
- **Permission Caching**: Redis-based role permission caching (5-minute TTL)
- **Widget Data**: Cached dashboard widget data for performance
- **Timer State**: Redis for real-time timer synchronization
- **Database Query**: Laravel query result caching

### Database Optimization
- **Strategic Indexing**: Composite indexes for common query patterns
- **Eager Loading**: Optimized relationship loading
- **Query Optimization**: N+1 query prevention
- **Connection Pooling**: Efficient database connection management

## Integration Architecture

### API Design
- **RESTful Endpoints**: Consistent resource-based API structure
- **JSON Responses**: Standardized response format with data/meta structure
- **Relationship Loading**: Flexible include parameters for nested data
- **Pagination**: Cursor and page-based pagination support

### Event-Driven Features
- **Model Events**: Laravel model events for audit logging
- **Domain Events**: Business logic event broadcasting
- **Queue Processing**: Background job processing for heavy operations
- **WebSocket Events**: Real-time event broadcasting to connected clients

## Deployment Architecture

### Environment Configuration
- **Multi-Environment**: Development, staging, production configurations
- **Environment Variables**: Secure configuration management
- **Feature Flags**: Environment-specific feature toggling
- **Database Migrations**: Version-controlled schema management

### Scalability Considerations
- **Horizontal Scaling**: Load balancer ready architecture
- **Database Scaling**: Read replicas and connection pooling
- **Cache Scaling**: Redis cluster support
- **Asset Optimization**: Vite production builds with asset versioning