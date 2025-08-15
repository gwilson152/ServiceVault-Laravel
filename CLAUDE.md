# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Service Vault is a comprehensive B2B service ticket and time management platform built with Laravel 12. It is primarily a **ticketing/service request platform** with sophisticated time tracking capabilities, featuring hierarchical customer account management, three-dimensional permission system (functional + widget + page access), account hierarchy permissions, and enterprise-level customization for multi-tenant service delivery.

### Current Status: Phase 15A+ Complete (100% MVP Ready + Full Billing System + TanStack Query Migration + Nuclear Reset + Debug Overlay System)
**✅ Fully Implemented Features:**
- **Super Admin Debug Overlay System**: Comprehensive debug overlays for timer diagnostics and permission analysis with reactive settings, expandable categories, and strict Super Admin-only access - accessible via Settings > Advanced tab
- **Nuclear System Reset**: Complete system reset functionality with multi-layer security (Super Admin + password confirmation), comprehensive audit logging, and safe execution via artisan command - accessible via Settings > Nuclear Reset tab
- **Complete Billing & Financial Management System**: Enterprise-grade invoicing, payment tracking, simplified billing rate management (no currency complexity), dynamic addon templates with API-driven categories, and financial reporting with TanStack Tables and Query optimization
- **Three-Dimensional Permission System**: Complete functional + widget + page permission architecture with role template management
- **Dashboard Preview System**: Real-time role preview with mock data generation and context switching (service provider vs account user)
- **Widget Assignment Interface**: Drag & drop widget management with 12-column grid layout designer and permission validation
- **Role Template Management**: Complete CRUD operations with permission inheritance, cloning, and context-aware validation
- **Context-Aware Security**: Service Provider vs Account User contexts with automatic permission filtering and inheritance
- **Complete Ticket Addon System**: Dynamic addon templates with CRUD operations, API-driven categories, and billing integration
- **Enhanced Ticket Widget System**: TicketTimerStats, TicketFilters, and RecentTimeEntries widgets with real-time functionality
- **Permission-Based Navigation**: Dynamic menu system adapting to user roles and three-dimensional permissions
- **Advanced Ticket Configuration System**: Complete drag-drop workflow management with optimistic UI updates, modal-based CRUD operations, and SLA integration
- **Settings Page Path Parameters**: Tab-based navigation with URL routing and browser history support
- **Optimistic Drag-Drop UX**: Instant visual feedback with background persistence and error recovery for all configuration ordering
- **Widget-Based Dashboard System**: 14+ permission-filtered widgets with auto-discovery and responsive grid layout
- **Comprehensive Tickets Page**: Full-featured ticket management with advanced filtering, dual view modes, and addon integration
- **Enhanced Real-Time Multi-Timer System**: Live duration updates, cross-component sync, manual time override, optimized bulk queries, professional commit workflows, and persistent timer overlay with Inertia.js layouts
- **Advanced Email Configuration System**: Wizard-style SMTP/IMAP setup with OAuth2 and app password support, provider templates, manual port configuration, real-time testing, and horizontal scrolling navigation
- **Service Ticket Integration**: Complete workflow system with timer integration, inline controls, and addon cost tracking
- **TimeEntry Management**: Comprehensive approval workflows with bulk operations, statistical tracking, and tabbed interface with integrated timer management
- **Complete User Management System**: Invitation-based user onboarding with nullable passwords, visibility controls, automatic timezone detection, and conditional password requirements
- **Laravel Sanctum Authentication**: Hybrid session/token auth with 23 granular abilities
- **Domain-Based User Assignment**: Automatic account assignment via email domain patterns
- **Real-Time Broadcasting Infrastructure**: Laravel Echo + Vue composables with cross-component timer synchronization
- **Advanced Permission Caching**: Role-based permission caching with efficient resolution algorithms
- **Comprehensive API**: 58+ endpoints with bulk operations, three-dimensional authentication, authorization, and preview capabilities
- **Cross-Device Timer Sync**: Redis-based state management with conflict resolution
- **Enterprise Authentication**: Token scoping (employee, manager, mobile-app, admin) with widget permissions
- **Modern Frontend Stack**: Vue.js 3.5 + Inertia.js persistent layouts + TanStack Query + Tailwind CSS + Headless UI
- **Optimized Dashboard Layout**: CSS Grid responsive design with progressive breakpoints and widget assignment controls

**🎯 Platform Status:** Fully Production-Ready - All core workflows refined and comprehensive UX improvements completed

**Phase 15A+ Recent Completions:**
- **Enhanced Ticket Detail Pages**: Fully functional central hub with all tabs working (messages, time tracking, addons, activity, billing)
- **Refined Timer Broadcast Overlay**: Smart overlay with Agent/Customer filtering, quick-start functionality, and gradient glass effect design
- **Complete Account Management**: Full account detail pages with comprehensive CRUD operations
- **Invitation Acceptance Workflow**: Complete user onboarding with timezone detection and role assignment
- **Customer Portal Foundation**: Portal dashboard and project interfaces for customer users
- **Enhanced Error Handling**: Comprehensive error states and user-friendly messaging across all components
- **TanStack Query Migration**: Complete migration from axios to TanStack Query for optimized caching, error handling, and data synchronization
- **Simplified Billing System**: Removed currency complexity from billing rates, streamlined settings organization, resolved modal initialization issues
- **Comprehensive ABAC Timer Permissions**: Full attribute-based access control for timer viewing, control, and creation with UI-level permission enforcement
- **Unified Time Entry Components**: Consolidated all timer commit workflows into UnifiedTimeEntryDialog with multiline work descriptions
- **Enhanced Selector Components**: Added account creation to HierarchicalAccountSelector with CSRF protection and seamless UX integration
- **Tabbed Time Management Interface**: Unified Time Entries and Active Timers interface with ABAC permission-based visibility and URL-based tab navigation
- **TimeEntry API Fixes**: Resolved 500 errors in TimeEntryResource with proper closure scoping, duration field corrections, and missing field mappings
- **Enhanced Setup System**: Robust setup completion detection with `system.setup_complete` setting, automatic system data seeding, and dual-middleware protection
- **Advanced Modal Architecture**: Native dialog-based modal stacking with Vue Teleport for proper hierarchy management, nested modal support, and resolved z-index conflicts
- **Refined Ticket Creation UX**: Removed auto-account selection in CreateTicketModal for explicit user control and improved account selection workflow

## Documentation

All platform documentation is centralized in `/docs/index.md`. Refer to the documentation index for:

- Development standards and workflows
- Architecture and database design
- Feature specifications
- API documentation
- Deployment guides

**Primary Reference**: [Documentation Index](docs/index.md)

## Development Server Policy

**IMPORTANT**: Never automatically start development servers without explicit user request:
- **DO NOT** run `php artisan serve` automatically
- **DO NOT** run `npm run dev` automatically  
- **DO NOT** run `npm run build --watch` automatically
- User will manually start/restart development servers when needed

### Documentation Maintenance Policy

**IMPORTANT**: Always update documentation when making code changes:

1. **Code Changes** → Update relevant documentation files
2. **New Features** → Add to `/docs/features/` 
3. **API Changes** → Update `/docs/api/` specifications
4. **Architecture Changes** → Update `/docs/architecture/`
5. **Development Process Changes** → Update `/docs/development/`

**Documentation Structure**:
```
docs/
├── index.md                    # Master index
├── development/                # Development guides
├── architecture/               # System architecture  
├── features/                   # Feature specifications
├── api/                        # API documentation
└── deployment/                 # Infrastructure guides
```

When creating new features or modifying existing ones, ensure documentation is updated in the same commit or pull request.

## Quick Start

```bash
# Development servers (DO NOT run these automatically - user will start them manually)
php artisan serve         # Start Laravel server (http://localhost:8000)
npm run dev              # Start Vite dev server (with HMR)

# Database operations  
php artisan migrate:fresh --seed  # Reset database with test data
php artisan migrate      # Run pending migrations only
php artisan db:seed      # Seed test data only

# Nuclear System Reset (DESTRUCTIVE - Super Admin only)
php artisan system:nuclear-reset --user-id=1  # Complete system reset with audit logging
# WARNING: Destroys ALL data, clears caches, removes setup_complete flag
# Also available via Settings > Nuclear Reset tab in UI with password confirmation

# Frontend development
npm run build           # Production build
npm run dev             # Development with hot reload

# API testing endpoints (Timer System - Multi-Timer Support)
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

# Time Entry Management API
# GET    /api/time-entries               # List time entries with filtering and pagination
# POST   /api/time-entries               # Create new time entry 
# GET    /api/time-entries/{id}          # Show specific time entry
# PUT    /api/time-entries/{id}          # Update time entry
# DELETE /api/time-entries/{id}          # Delete time entry
# POST   /api/time-entries/{id}/approve  # Approve time entry (managers/admins)
# POST   /api/time-entries/{id}/reject   # Reject time entry (managers/admins)
# POST   /api/time-entries/bulk/approve  # Bulk approve time entries
# POST   /api/time-entries/bulk/reject   # Bulk reject time entries
# GET    /api/time-entries/stats/recent  # Recent statistics for dashboard
# GET    /api/time-entries/stats/approvals # Approval statistics (managers/admins)

# Authentication & Token Management
# GET    /api/auth/tokens                # List user's API tokens
# POST   /api/auth/tokens                # Create new API token
# GET    /api/auth/tokens/abilities      # Get available token abilities
# POST   /api/auth/tokens/scope          # Create scoped token (employee, manager, etc.)
# DELETE /api/auth/tokens/revoke-all     # Revoke all user tokens

# Ticket Configuration Management (Admin/Settings)
# GET    /api/settings/ticket-config     # Get all ticket configuration (statuses, categories, priorities)
# GET    /api/ticket-statuses            # List ticket statuses with filtering
# POST   /api/ticket-statuses            # Create new ticket status
# PUT    /api/ticket-statuses/{id}       # Update ticket status
# DELETE /api/ticket-statuses/{id}       # Delete ticket status
# POST   /api/ticket-statuses/reorder    # Reorder statuses (optimistic drag-drop)
# GET    /api/ticket-categories          # List ticket categories with SLA info
# POST   /api/ticket-categories          # Create new ticket category
# PUT    /api/ticket-categories/{id}     # Update ticket category
# DELETE /api/ticket-categories/{id}     # Delete ticket category
# POST   /api/ticket-categories/reorder  # Reorder categories (optimistic drag-drop)
# GET    /api/ticket-priorities          # List ticket priorities with escalation
# POST   /api/ticket-priorities          # Create new ticket priority
# PUT    /api/ticket-priorities/{id}     # Update ticket priority
# DELETE /api/ticket-priorities/{id}     # Delete ticket priority
# POST   /api/ticket-priorities/reorder  # Reorder priorities (optimistic drag-drop)
# PUT    /api/settings/workflow-transitions # Update workflow transition rules

# Domain Mapping Management (Admin/Manager)  
# GET    /api/domain-mappings            # List domain mappings
# POST   /api/domain-mappings            # Create domain mapping
# POST   /api/domain-mappings/preview    # Preview assignment for email
# GET    /api/domain-mappings/validate/requirements # System validation

# Advanced Settings (Super Admin only - Debug System)
# GET    /api/settings/advanced          # Get advanced settings (debug overlays)
# PUT    /api/settings/advanced          # Update advanced settings 
#        Body: { show_debug_overlay: boolean, show_permissions_debug_overlay: boolean }
#        Requires: Super Admin role + system.configure permission
#        Storage: Database with advanced.* prefix + localStorage sync

# Nuclear System Reset (Super Admin only - DESTRUCTIVE)
# POST   /api/settings/nuclear-reset     # Complete system reset with password confirmation
#        Requires: Super Admin role + current password
#        Returns: Success message + redirect to /setup
#        WARNING: Destroys ALL data permanently

# Standard Laravel CLI
php artisan make:model ModelName -mfs          # Model + migration/factory/seeder
php artisan make:controller Api/ModelController --api --model=Model  # API controller
php artisan make:policy ModelPolicy --model=Model  # Authorization policy

# Testing & debugging
php artisan test         # Run test suite
php artisan tinker       # Interactive shell
```

## Authentication Architecture

Service Vault implements a hybrid authentication system with Laravel Breeze (web sessions) + Laravel Sanctum (API tokens). See [Authentication System Documentation](docs/system/authentication-system.md) for complete details.

**Key Points for Development:**
- Session-based web authentication with Inertia.js integration
- Token-based API authentication with 23 granular abilities
- All policies support both authentication methods
- Three-dimensional permission system (Functional + Widget + Page)

**Quick Reference:**
```php
// Policy integration pattern
if ($user->currentAccessToken()) {
    return $user->tokenCan('timers:read');
}
return $user->hasPermission('timers.view');
```

## Time Management System

Unified time management at `/time-entries` with tabbed interface (Time Entries + Active Timers). See [Time Management Documentation](docs/features/time-management.md) for complete details.

**Key Implementation Notes:**
- **Time Entries Tab**: CRUD operations with approval workflows
- **Active Timers Tab**: RBAC-based visibility (own vs all timers)
- **URL Routes**: `/time-entries/{tab}` where tab = `time-entries|timers`
- **Permission-Based API Access**: `/api/timers/user/active` vs `/api/admin/timers/all-active`
- **Auto-Refresh**: 30-second intervals for live updates
- **Timer Commit Integration**: Automatic tab switching on commit

## Multi-Timer System Architecture

Service Vault supports concurrent timers with Redis state management and real-time sync. See [Timer System Architecture](docs/architecture/timer-system.md) for complete technical details.

**Key Implementation Notes:**
- **Concurrent Timers**: Multiple active timers per user with cross-device sync
- **Redis State**: `user:{user_id}:timer:{timer_id}:state` pattern
- **Real-Time Broadcasting**: Laravel Echo + Vue composables  
- **Timer Overlay**: `TimerBroadcastOverlay.vue` with persistent state

**⚠️ CRITICAL Navigation Rule:**
Always use Inertia.js navigation (`router.visit()` or `<Link>`) to maintain timer overlay persistence. Regular `<a>` tags or `window.location.href` cause full page reloads and break timer state.

**Domain-Based Assignment:**
Automatic user-to-account mapping via domain patterns. See [Authentication System](docs/system/authentication-system.md#domain-based-user-assignment).

## Development Standards

### Laravel CLI-First Approach
Service Vault follows Laravel best practices using CLI-first generation:

```bash
# Standard model generation
php artisan make:model Account -mfs          # Model + migration/factory/seeder
php artisan make:controller Api/AccountController --api --model=Account  # API controller
php artisan make:policy AccountPolicy --model=Account  # Authorization policy
php artisan make:request StoreAccountRequest          # Form validation
php artisan make:resource AccountResource             # API responses
```

### Architecture Principles
- **Hybrid Authentication**: Laravel Sanctum (session + token) with granular abilities
- **Multi-Timer Architecture**: Concurrent timer support with Redis state management
- **Account Hierarchy System**: Customer account trees with visual hierarchical display and subsidiary access permissions
- **Three-Dimensional Permission System**: Functional + Widget + Page access control
- **ABAC Permission System**: Role templates with hierarchical inheritance and UI control
- **Widget-Based Dashboard**: Permission-driven widget system with auto-discovery
- **Real-Time Infrastructure**: Laravel Echo + Vue composables (WebSocket ready)
- **API-First Backend**: RESTful endpoints with consistent JSON responses
- **Component-Based Frontend**: Vue.js 3.5 + Inertia.js + Headless UI + Tailwind CSS
- **Enterprise Theming**: CSS custom properties for multi-tenant branding

## Three-Dimensional Permission System

Service Vault uses a three-dimensional permission system: **Functional** (what users can DO) + **Widget** (what they SEE on dashboard) + **Page** (what pages they can ACCESS). See [Three-Dimensional Permissions](docs/architecture/three-dimensional-permissions.md) for complete details.

**Key Development Pattern:**
```php
// Check permission across all dimensions
$user->hasPermission('tickets.view.account');      // Functional
$user->hasPermission('widgets.dashboard.tickets'); // Widget  
$user->hasPermission('pages.tickets.manage');      // Page Access
```

**Permission Storage:** Role templates store three separate arrays (`permissions`, `widget_permissions`, `page_permissions`) with unified checking via `hasPermission()` method.

## 📚 Detailed Technical Documentation

For comprehensive technical details, refer to the organized documentation in `/docs/`:

### **Core System Architecture**
- **[Authentication System](docs/system/authentication-system.md)** - Laravel Sanctum, token management, domain-based assignment
- **[Three-Dimensional Permissions](docs/architecture/three-dimensional-permissions.md)** - Functional + Widget + Page permission system
- **[Timer System Architecture](docs/architecture/timer-system.md)** - Multi-timer design, Redis state, real-time sync
- **[Time Management](docs/features/time-management.md)** - Tabbed interface, API endpoints, permission matrix

### **Additional Documentation**
- **[Documentation Index](docs/index.md)** - Complete documentation structure and overview
- **[API Reference](docs/api/index.md)** - REST API endpoints and specifications  
- **[Development Guide](docs/development/index.md)** - Development workflows and standards
- **[Features Documentation](docs/features/index.md)** - User guides and feature specifications

This CLAUDE.md file focuses on essential information for AI development assistance. For detailed implementation guides, architecture deep-dives, and comprehensive feature documentation, always refer to the structured documentation in the `/docs` folder.