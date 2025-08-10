# Service Vault Documentation

A comprehensive B2B service management platform built with Laravel 12, featuring widget-based dashboards, multi-timer systems, ABAC permissions, and comprehensive service ticket workflows.

## System Overview

**Service Vault** is a B2B service management platform where **Service Provider Companies** deliver services to **Customer Organizations**. Both service providers and their customers use the same application with different access levels and interfaces.

### Project Status

**Current Phase**: Phase 12D Complete (98% MVP Ready)  
**Recently Completed**: Enhanced Ticket System Features with Configurable Status/Category System  
**Current Focus**: Universal Widget System Extension and Service Ticket Communication (Phase 12E)  
**Next Priority**: Page-Level Widget Framework and Comment System  

### Core Features

- **Widget-Based Dashboard System**: Single dynamic dashboard with permission-driven widgets
- **Multi-Timer System**: Concurrent timers with cross-device synchronization and app-wide visibility
- **Service Ticket Management**: Comprehensive workflow with timer integration and addon support
- **ABAC Permission System**: Fully customizable role templates with hierarchical inheritance
- **Real-Time Broadcasting**: Laravel Echo + Vue composables for live updates
- **Account Context Switching**: Service providers can manage multiple customer organizations
- **Admin Timer Oversight**: Permission-based monitoring and control of all user timers

### User Access Model

**Service Provider Staff:**
- **Super Administrator**: System-wide management (customizable role)
- **Service Manager**: Multi-account oversight and team management (customizable role)
- **Service Employee**: Ticket delivery and time tracking (customizable role)

**Customer Organization Staff:**
- **Account Administrator**: Account management and user administration (customizable role)
- **Account Manager**: Account oversight and service coordination (customizable role)
- **Account User**: Service requests and ticket communication (customizable role)

### Architecture Principles

- **Single Dynamic Dashboard**: One dashboard that adapts based on user permissions
- **Fully Customizable Roles**: Out-of-box role templates, completely customizable permissions
- **Widget-Based UI**: Permission-driven widget system for maximum flexibility
- **Account Context Aware**: Service staff can switch between customer accounts
- **B2B Service Platform**: Service provider → Customer organization relationships

## Technology Stack

### Backend
- **Laravel 12** with PHP 8.2+
- **Database**: PostgreSQL with Redis for real-time state
- **Authentication**: Laravel Sanctum (session + token) with 23 granular abilities
- **Real-time**: Laravel Echo with WebSocket broadcasting
- **Caching**: Redis for permissions, timer state, and widget data

### Frontend
- **Vue.js 3.5+** with Composition API and script setup syntax
- **Inertia.js** for SPA-like experience without API complexity
- **Headless UI + Tailwind CSS** for accessible, responsive design
- **Widget Architecture** with auto-discovery and permission filtering

## Quick Start

### Prerequisites

- PHP 8.2+ with extensions (pdo_pgsql, redis, etc.)
- PostgreSQL 15+ 
- Node.js LTS with npm
- Composer 2.x
- Redis for caching and real-time features
- VS Code with PHP/Vue.js extensions (recommended)

### Setup

```bash
# Clone and install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database operations  
php artisan migrate:fresh --seed  # Reset database with test data
php artisan migrate      # Run pending migrations only
php artisan db:seed      # Seed test data only

# Development servers (DO NOT run these automatically - user will start them manually)
php artisan serve         # Start Laravel server (http://localhost:8000)
npm run dev              # Start Vite dev server (with HMR)

# Frontend development
npm run build           # Production build
npm run dev             # Development with hot reload
```

### Development Workflow

See [`../todos.md`](../todos.md) for complete implementation roadmap and progress tracking.

## Current Implementation Status

### ✅ Working Features

- **Comprehensive Service Ticket System** with workflow engine
- **Multi-Timer System** with cross-device synchronization  
- **ABAC Permission System** with Super Admin inheritance
- **Widget-Based Dashboard** with auto-discovery and permission filtering
- **API Authentication** with Laravel Sanctum and token abilities
- **Real-time Broadcasting** with Laravel Echo
- **Permission-Based Timer Visibility** with admin oversight
- **Timer-Ticket Integration** with comprehensive controls

### ✅ Recently Completed

1. **Permission-Based App-Wide Timer System** - Enhanced admin oversight with granular permissions
2. **TicketTimerControls Component** - Inline timer controls per ticket with dynamic states
3. **Enhanced MultiTimerFAB** - Admin overlay showing all active timers with control actions
4. **useAppWideTimers Composable** - Centralized timer management with permission checks
5. **SimpleTimerFAB Fallback** - Basic timer functionality for users without advanced permissions

### ✅ Recently Completed (Phase 12D)

**Phase 12D: Enhanced Ticket System Features**
- ✅ **TicketTimerStatsWidget** - Active timers summary for current user with real-time controls
- ✅ **TicketFiltersWidget** - Advanced filtering with saved views and quick filters
- ✅ **RecentTimeEntriesWidget** - Latest time entries across tickets with approval actions
- ✅ **Permission-Based Navigation System** - Dynamic menu based on user permissions
- ✅ **Configurable Ticket Statuses** - TicketStatus model with workflow management and 7 defaults
- ✅ **Configurable Ticket Categories** - TicketCategory model with SLA management and 7 defaults

### ⚠️ Next Implementation Priorities (Phase 12E)

**Phase 12E: Universal Widget System Extension**
1. **Page-Level Widget Framework** - Extend widget framework to all pages beyond dashboard
2. **Widget Area Components** - Reusable widget containers for any page
3. **Enhanced Widget Registry** - Page-specific widget registration and filtering
4. **Widget Layout Persistence** - Save custom widget arrangements per page

**Phase 13: Service Ticket Communication**
5. **Comment System** - Ticket comments and communication threads
6. **File Attachments** - Upload and manage files per ticket
7. **Real-Time Notifications** - Live updates for ticket changes

## Phase 12 Implementation Progress

### ✅ Phase 12A Complete: App-Wide Multi-Timer System
- **Enhanced MultiTimerFAB Component** with admin overlay for all active timers
- **Permission-Based Timer Visibility** with granular access controls  
- **Admin Timer Management API** with cross-user control capabilities
- **useAppWideTimers Composable** with centralized timer management
- **TicketTimerControls Component** with inline timer controls per ticket
- **CommitTimeEntryDialog Component** for universal time entry creation

### ✅ Phase 12B Complete: Timer-Integrated Tickets Page System
- ✅ **Complete Ticket Addon Management System** with 18 templates and approval workflow
- ✅ **Widget-Enhanced Tickets Page** with advanced filtering and dual view modes
- ✅ **Timer Integration** with inline controls per ticket

### ✅ Phase 12C Complete: Ticket Addon Management System  
- ✅ **Comprehensive Addon System** with template-based creation and billing integration
- ✅ **Approval Workflow** with pending → approved/rejected states
- ✅ **Real-Time Calculations** with automatic subtotal, discount, tax, and total calculations

### ✅ Phase 12D Complete: Enhanced Ticket System Features
- ✅ **Ticket-Specific Widget Library** with TicketTimerStats, TicketFilters, and RecentTimeEntries
- ✅ **Permission-Based Navigation System** with dynamic menu adaptation
- ✅ **Configurable Status/Category System** with 7 defaults each and workflow management

### 🚀 Phase 12E Next: Universal Widget System Extension
Complete the universal widget architecture:
- Page-Level Widget Framework for any page beyond dashboard
- Widget Area Components as reusable containers
- Enhanced Widget Registry with page-specific filtering
- Widget Layout Persistence for custom arrangements per page

**Target Completion**: Phase 12E achieves complete universal widget system (99% MVP Ready)

## API Endpoints

### Timer System (Multi-Timer Support)
```bash
# GET    /api/timers                     # List user timers (paginated)
# POST   /api/timers                     # Start new timer (stop_others=false for concurrent)
# GET    /api/timers/active/current      # Get ALL active timers with totals
# POST   /api/timers/{timer}/stop        # Stop timer
# POST   /api/timers/{timer}/pause       # Pause running timer
# POST   /api/timers/{timer}/resume      # Resume paused timer
# POST   /api/timers/{timer}/commit      # Stop and convert to time entry
# DELETE /api/timers/{timer}?force=true  # Force delete timer
# POST   /api/timers/sync                # Cross-device timer synchronization
# POST   /api/timers/bulk                # Bulk operations (stop, pause, resume, delete)
```

### Admin Timer Management
```bash
# GET    /api/admin/timers/all-active    # Get all active timers (admin only)
# POST   /api/admin/timers/{timer}/pause # Admin pause any user's timer
# POST   /api/admin/timers/{timer}/resume # Admin resume any user's timer
# POST   /api/admin/timers/{timer}/stop  # Admin stop any user's timer
```

### Authentication & Token Management
```bash
# GET    /api/auth/tokens                # List user's API tokens
# POST   /api/auth/tokens                # Create new API token
# GET    /api/auth/tokens/abilities      # Get available token abilities
# POST   /api/auth/tokens/scope          # Create scoped token (employee, manager, etc.)
# DELETE /api/auth/tokens/revoke-all     # Revoke all user tokens
```

### Service Tickets
```bash
# Standard Laravel API resource endpoints
# GET    /api/service-tickets            # List tickets
# POST   /api/service-tickets            # Create ticket
# GET    /api/service-tickets/{ticket}   # Get ticket
# PUT    /api/service-tickets/{ticket}   # Update ticket
# DELETE /api/service-tickets/{ticket}   # Delete ticket

# Workflow endpoints
# POST   /api/service-tickets/{ticket}/transition # Change ticket status
# POST   /api/service-tickets/{ticket}/assign     # Assign team members
# GET    /api/service-tickets/{ticket}/timers     # Get ticket timers
```

## Dashboard Architecture

### Widget-Based System

The dashboard uses a comprehensive widget system with:

- **Auto-Discovery**: Automatically scans for widget components
- **Permission-Based Filtering**: Widgets only show for users with required permissions
- **Context Awareness**: Widgets adapt to service provider vs. account user contexts
- **Real-Time Data**: Widgets refresh automatically with live data

### Available Widgets

**Administration Widgets:**
- SystemHealthWidget - System status monitoring
- SystemStatsWidget - User, account, timer statistics  
- UserManagementWidget - User administration shortcuts
- AccountManagementWidget - Customer account management
- AllTimersWidget - Admin monitoring of all active timers (Admin only)

**Service Delivery Widgets:**
- TicketOverviewWidget - Service tickets across accounts
- MyTicketsWidget - Assigned tickets for current user

**Time Management Widgets:**
- TimeTrackingWidget - Active timer management
- TimeEntriesWidget - Recent time entries and approvals

**Productivity Widgets:**
- QuickActionsWidget - Common actions based on role

### Permission-Based Visibility

Widgets are filtered based on user permissions:
```php
// Example widget permissions
'permissions' => ['admin.read', 'super_admin'],  // Admin only
'permissions' => ['tickets.view.assigned'],      // Basic ticket access
'permissions' => [],                             // Always available
```

## Timer System Architecture

### Multi-Timer Support

Service Vault supports multiple concurrent timers per user:

- **Concurrent Timers**: Users can run multiple timers simultaneously
- **Cross-Device Sync**: Redis-based state synchronization
- **Permission-Based Visibility**: Different timer interfaces based on user permissions
- **Admin Oversight**: Administrators can monitor and control all user timers

### Timer Components

**MultiTimerFAB** (Advanced Users):
- Multiple concurrent timer management
- Admin overlay showing all active timers
- Real-time admin controls for any user's timer
- Expandable interface with detailed timer information

**SimpleTimerFAB** (Basic Users):
- Single timer interface
- Basic start/stop/pause functionality
- Timer description editing
- Commit to time entry

**TicketTimerControls** (Ticket Integration):
- Inline timer controls per service ticket
- Dynamic button states based on user's timer status
- Support for multiple users working on same ticket
- Integration with CommitTimeEntryDialog

### Permission Structure

**Basic Timer Permissions**:
- `time.track` - Can track time
- `timers.create` - Can create timers
- `timers.manage.own` - Can manage own timers

**Advanced Timer Permissions**:
- `admin.read` - Can view admin features
- `timers.view.all` - Can see all timers
- `timers.manage.team` - Can manage team timers
- `managers.oversight` - Manager oversight capabilities

## Authentication System

### Hybrid Authentication

Service Vault implements a hybrid authentication system:

- **Session Authentication** (Laravel Breeze): Web dashboard with session cookies
- **Token Authentication** (Laravel Sanctum): API access with bearer tokens
- **Granular Abilities**: 23+ token abilities for specific functionality
- **Predefined Scopes**: Ready-to-use token scopes (employee, manager, mobile-app, admin)

### Token Management

```bash
# Create scoped tokens for different use cases
POST /api/auth/tokens/scope
{
    "scope": "employee",  // employee, manager, mobile-app, admin
    "name": "Mobile App Token"
}
```

## Documentation Structure

### [Development](development/)
Standards, tools, and workflows for Service Vault development.

### [Architecture](architecture/)
System architecture, database design, and technical specifications.

### [Features](features/)
Functional specifications and user guides for Service Vault features.

### [API](api/)
RESTful API specifications, authentication, and integration guides.

### [Deployment](deployment/)
Production deployment, server configuration, and infrastructure.

## Key Architectural Patterns

### Widget-Based Dashboard
- Single dashboard with permission-driven widgets
- Auto-discovery system using filesystem scanning
- Permission-based widget filtering
- Context awareness (service_provider, account_user, both)

### Dynamic Role System
- Fully customizable permissions with sensible defaults
- ABAC (Attribute-Based Access Control) implementation
- Role templates with hierarchical inheritance
- Super Admin with dynamic permission inheritance

### Account Context Awareness
- Service provider staff can manage multiple customer accounts
- Account context switching for multi-organization management
- Account-scoped data filtering and permissions

### B2B Service Model
- Service providers deliver services to customer organizations
- Hierarchical account management (Individual, Organization, Container)
- Service ticket workflows with customer communication
- Time tracking tied to customer billing

### Permission-First Design
- All features controlled by granular permissions
- No hardcoded roles - everything is permission-based
- Dynamic UI adaptation based on user capabilities
- Fine-grained authorization for every action

## Development Statistics

- **Database Tables**: 25+ with comprehensive relationships
- **API Endpoints**: 35+ with authentication and authorization
- **Widget Registry**: 13+ widgets with auto-discovery system
- **Widget Components**: 8+ functional Vue components with real data
- **Role Templates**: 10+ default roles with full customization
- **Vue Components**: 25+ with widget architecture
- **Permission System**: 50+ granular permissions
- **Timer System**: Multi-timer with cross-device sync and admin oversight

## Contributing

### Code Standards
- **Laravel CLI-first** - Use `php artisan make:*` commands for consistency
- **Permission-Based** - No hardcoded roles, everything uses permissions
- **Widget Architecture** - Extend widget system for new features
- **Vue 3 Composition API** - Use script setup syntax
- **Real-Time Updates** - Integrate with broadcasting system

### Git Workflow
- Feature branches from `main`
- Descriptive commit messages with emoji
- Pull request reviews required
- Automated testing and linting

---

## Recent Fixes and Improvements (August 9, 2025)

### ✅ Widget System Stabilization
- **Fixed HTTP 500 Errors**: Resolved CheckPermission middleware configuration issues that were blocking API requests
- **Authorization Framework**: Added `AuthorizesRequests` trait to base Controller for proper policy-based authorization
- **Widget Component Issues**: Created missing widget components (`BillingOverviewWidget`, `AccountActivityWidget`)
- **Permission System**: Fixed inconsistency between User and PermissionService super admin checking

### ✅ API Endpoint Stabilization  
- **Timer API**: Fixed `/api/timers/active/current` endpoint authorization errors
- **Admin Timer Management**: Resolved permission checking for admin timer oversight endpoints
- **Route Configuration**: Properly configured CheckPermission middleware with required parameters
- **Cache Management**: Cleared route and permission caches for immediate effect

### ✅ Widget System Cleanup
- **Removed Unused Features**: Eliminated TeamPerformanceWidget and team-related permissions (not implemented)
- **Component Registration**: Updated WidgetLoader with all available widget components
- **Mock Data**: Added realistic placeholder data for widgets pending full implementation

### 🔧 Technical Improvements
- **Middleware Registration**: Added `check_permission` alias in bootstrap/app.php
- **Permission Consistency**: Ensured PermissionService matches User model permission checking logic
- **Error Handling**: Improved error responses from HTTP 500s to proper 401/403 status codes

---

**Service Vault** - B2B Service Management Platform with Widget-Based Architecture and Multi-Timer System

_Last Updated: August 9, 2025 - Phase 12D: Enhanced Ticket System Features with Configurable Status/Category System Complete_