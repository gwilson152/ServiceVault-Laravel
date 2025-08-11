# Service Vault Documentation

A comprehensive B2B **ticketing/service request platform** with time management capabilities, built with Laravel 12. Features hierarchical customer account management, three-dimensional permission system, widget-based dashboards, and sophisticated service ticket workflows.

## System Overview

**Service Vault** is a B2B service management platform where **Service Provider Companies** deliver services to **Customer Organizations**. Both service providers and their customers use the same application with different access levels and interfaces.

### Platform Status

**Current Phase**: Phase 13C Complete (100% MVP Ready)  
**Recently Completed**: User Management System with comprehensive user administration, account assignment, and role management  
**Current Focus**: System is production-ready with complete user management and three-dimensional permission system  
**Next Priority**: Production deployment and advanced role template management  

### Core Features

- **Three-Dimensional Permission System**: Comprehensive Functional + Widget + Page access control
- **Live Dashboard Preview System**: Real-time preview of user dashboard experiences with mock data
- **Advanced Widget Assignment Interface**: Drag & drop widget management with visual layout designer
- **Comprehensive User Management**: Full user administration with account assignment, role management, and dual context display
- **Widget-Based Dashboard System**: Single dynamic dashboard with permission-driven widgets
- **Multi-Timer System**: Concurrent timers with cross-device synchronization and app-wide visibility
- **Service Ticket Management**: Comprehensive workflow with timer integration and addon support
- **Business Account Management**: Comprehensive B2B customer relationship management with hierarchy support
- **ABAC Permission System**: Fully customizable role templates with drag & drop widget control
- **Real-Time Broadcasting**: Laravel Echo + Vue composables for live updates
- **Account Context Switching**: Service providers can manage multiple customer organizations
- **Admin Timer Oversight**: Permission-based monitoring and control of all user timers

### User Access Model

**Service Provider Staff:**
- **Super Admin**: All permissions (non-modifiable role with complete system access)
- **Admin**: Full service provider administration (modifiable role)
- **Manager**: Service oversight and ticket management (modifiable role) 
- **Employee**: Service delivery and time tracking (modifiable role)

**Customer Account Staff:**
- **Account Manager**: Primary account + all subsidiaries access (modifiable role)
- **Account User**: Single account access only (modifiable role)

### Architecture Principles

- **Single Dynamic Dashboard**: One dashboard that adapts based on user permissions
- **Three-Dimensional Permission Model**: Functional + Widget + Page access control
- **Business Account System**: Customer accounts with business information, contact details, and hierarchy support
- **Fully Customizable Roles**: Out-of-box role templates, completely customizable permissions
- **Widget-Based UI**: Permission-driven widget system for maximum flexibility
- **Account Context Aware**: Service staff can switch between customer accounts
- **B2B Service Platform**: Service provider → Customer organization relationships

## Technology Stack

### Backend
- **Laravel 12** with PHP 8.2+
- **Database**: PostgreSQL with Redis for real-time state
- **Authentication**: Laravel Sanctum (session + token) with granular abilities including widget/page permissions
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

### ✅ Recently Completed (Phase 13A)

**Phase 13A: Complete Three-Dimensional Permission System**
- ✅ **Three-Dimensional Permission System** - Comprehensive Functional + Widget + Page permission control
- ✅ **Live Dashboard Preview System** - Real-time preview of dashboard experiences with mock data generation
- ✅ **Advanced Widget Assignment Interface** - Drag & drop widget management with visual layout designer
- ✅ **Role Template Management Interface** - Complete CRUD operations for role templates
- ✅ **Permission Matrix Editor** - Visual three-dimensional permission assignment interface
- ✅ **Mock User Service** - Realistic mock data generation for dashboard preview scenarios
- ✅ **Context Switching Preview** - Service Provider vs Account User dashboard experiences
- ✅ **Widget Layout Designer** - 12-column grid system with drag & resize functionality

**Previous Phase Completions:**
1. **Enhanced Ticket System Features (12D)** - Configurable status/category system with workflow management
2. **Permission-Based App-Wide Timer System (12C)** - Enhanced admin oversight with granular permissions
3. **Multi-Timer System Architecture (12B)** - Concurrent timers with cross-device synchronization
4. **Widget-Based Dashboard Foundation (12A)** - Permission-driven widget system with auto-discovery

### 🎯 Next Development Phases (Post-MVP)

**Phase 13B: Production Optimization**
1. **Performance Optimization** - Database query optimization, caching strategies
2. **Security Hardening** - Security audit, rate limiting, CSRF protection enhancements
3. **Monitoring & Logging** - Application performance monitoring, error tracking
4. **Documentation Completion** - User manuals, API documentation, deployment guides

**Phase 14: Advanced Communication Features**
1. **Comment System** - Ticket comments and communication threads
2. **File Attachments** - Upload and manage files per ticket
3. **Real-Time Notifications** - Live updates for ticket changes and assignments
4. **Email Integration** - Automated notifications and ticket updates via email

**Phase 15: Enterprise Features** 
1. **Advanced Reporting** - Custom reports and analytics dashboards
2. **Data Export/Import** - CSV/Excel export capabilities, bulk data import
3. **Third-Party Integrations** - API integrations with external systems
4. **Mobile Application** - Dedicated mobile app for time tracking and ticket management

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

### Role & Permission Management
```bash
# Role Template Management
# GET    /api/role-templates                      # List all role templates
# POST   /api/role-templates                      # Create new role template
# GET    /api/role-templates/{roleTemplate}      # Get specific role template
# PUT    /api/role-templates/{roleTemplate}      # Update role template
# DELETE /api/role-templates/{roleTemplate}      # Delete role template
# POST   /api/role-templates/{roleTemplate}/clone # Clone existing role template

# Permission Management
# GET    /api/role-templates/permissions/available # Get all available permissions
# GET    /api/widget-permissions                  # Get all widget permissions
# POST   /api/widget-permissions/sync             # Sync widget permissions

# Dashboard Preview System
# GET    /api/role-templates/{roleTemplate}/preview/dashboard   # Full dashboard preview
# GET    /api/role-templates/{roleTemplate}/preview/widgets     # Widget preview
# GET    /api/role-templates/{roleTemplate}/preview/navigation  # Navigation preview
# GET    /api/role-templates/{roleTemplate}/preview/layout      # Layout preview

# Widget Assignment Interface
# GET    /api/role-templates/{roleTemplate}/widgets  # Get current widget assignments
# PUT    /api/role-templates/{roleTemplate}/widgets  # Update widget assignments and layout
```

### User Management
```bash
# User CRUD operations
# GET    /api/users                      # List users with filtering and search
# POST   /api/users                      # Create user with account/role assignment
# GET    /api/users/{user}               # Get user with comprehensive details
# PUT    /api/users/{user}               # Update user and assignments
# DELETE /api/users/{user}               # Deactivate user

# User detail endpoints
# GET    /api/users/{user}/tickets       # Get user's assigned tickets
# GET    /api/users/{user}/time-entries  # Get user's time entries
# GET    /api/users/{user}/activity      # Get user activity and analytics
# GET    /api/users/{user}/accounts      # Get user's account assignments
```

# Service Tickets
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
- UserManagementWidget - User administration with account/role management
- AccountManagementWidget - Business customer account management
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
- Business account management with company information, contact details, addresses
- Account hierarchy support for corporate structures and subsidiaries
- Service ticket workflows with customer communication
- Time tracking tied to customer billing

### Permission-First Design
- All features controlled by granular permissions
- No hardcoded roles - everything is permission-based
- Dynamic UI adaptation based on user capabilities
- Fine-grained authorization for every action

## Development Statistics

- **Database Tables**: 30+ with comprehensive relationships and three-dimensional permission support
- **API Endpoints**: 52+ with authentication, authorization, comprehensive user management, and dashboard preview capabilities
- **Widget Registry**: 15+ widgets with auto-discovery and permission-based filtering
- **Widget Components**: 12+ functional Vue components with real-time data integration
- **Role Templates**: 6 default role templates with unlimited customization potential
- **Vue Components**: 40+ including advanced modals, user management, and drag & drop interfaces
- **Permission System**: 60+ granular permissions across functional, widget, and page dimensions
- **User Management System**: Complete CRUD with dual context display and account/role assignment
- **Timer System**: Multi-timer with cross-device sync, admin oversight, and ticket integration
- **Dashboard Preview System**: Complete mock data generation and real-time preview capabilities
- **Widget Assignment Interface**: Advanced drag & drop with visual layout designer and 12-column grid system

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

## Recent Major Completion (August 11, 2025)

### 🎉 Phase 13A: Three-Dimensional Permission System Complete

**Core Permission Management:**
- **Three-Dimensional Permission Model**: Complete implementation of Functional + Widget + Page permission control
- **Role Template Management**: Full CRUD interface for creating and managing role templates
- **Permission Matrix Editor**: Visual interface for assigning permissions across all three dimensions
- **Hierarchical Permission Inheritance**: Account hierarchy support with permission cascading

**Advanced Dashboard Management:**
- **Live Dashboard Preview System**: Real-time preview of dashboard experiences with context switching
- **Mock User Service**: Sophisticated mock data generation for realistic preview scenarios  
- **Dashboard Preview Modal**: Comprehensive preview interface with multiple view modes
- **Context-Aware Previews**: Separate Service Provider and Account User experience previews

**Widget Assignment Interface:**
- **Drag & Drop Widget Management**: Visual interface for assigning widgets to role templates
- **Visual Layout Designer**: 12-column grid system with drag, drop, and resize capabilities
- **Widget Configuration**: Per-widget settings for enabled by default, configurable status
- **Real-Time Layout Visualization**: Live preview of widget positioning and sizing

**Technical Architecture:**
- **15 New API Endpoints**: Complete REST API for role, permission, and widget management
- **5 New Vue.js Components**: Advanced modals and interfaces with drag & drop functionality
- **Database Schema Extensions**: New tables and columns supporting three-dimensional permissions
- **Enhanced Authorization Framework**: Policy-based access control for all management interfaces

### 🚀 Production Readiness Achieved

**Service Vault** is now **100% MVP Complete** with:
- ✅ Comprehensive three-dimensional permission system
- ✅ Complete role and template management capabilities  
- ✅ Advanced dashboard preview and widget assignment interfaces
- ✅ Comprehensive user management system with account/role assignment
- ✅ Full API coverage for all management features with detailed documentation
- ✅ Production-ready architecture with proper security and validation

---

**Service Vault** - Complete B2B Service Management Platform with Three-Dimensional Permission System

_Last Updated: August 11, 2025 - Phase 13A: Three-Dimensional Permission System Complete - 100% MVP Ready_