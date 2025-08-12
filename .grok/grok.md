# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Service Vault is a comprehensive B2B service ticket and time management platform built with Laravel 12. It is primarily a **ticketing/service request platform** with sophisticated time tracking capabilities, featuring hierarchical customer account management, three-dimensional permission system (functional + widget + page access), account hierarchy permissions, and enterprise-level customization for multi-tenant service delivery.

### Current Status: Phase 13A Complete (100% MVP Ready)

**✅ Fully Implemented Features:**

-   **Three-Dimensional Permission System**: Complete functional + widget + page permission architecture with role template management
-   **Dashboard Preview System**: Real-time role preview with mock data generation and context switching (service provider vs account user)
-   **Widget Assignment Interface**: Drag & drop widget management with 12-column grid layout designer and permission validation
-   **Role Template Management**: Complete CRUD operations with permission inheritance, cloning, and context-aware validation
-   **Context-Aware Security**: Service Provider vs Account User contexts with automatic permission filtering and inheritance
-   **Complete Ticket Addon System**: 18 predefined templates with approval workflow and billing integration
-   **Enhanced Ticket Widget System**: TicketTimerStats, TicketFilters, and RecentTimeEntries widgets with real-time functionality
-   **Permission-Based Navigation**: Dynamic menu system adapting to user roles and three-dimensional permissions
-   **Configurable Ticket Status & Category System**: Complete workflow management with business logic and SLA integration
-   **Widget-Based Dashboard System**: 14+ permission-filtered widgets with auto-discovery and responsive grid layout
-   **Comprehensive Tickets Page**: Full-featured ticket management with advanced filtering, dual view modes, and addon integration
-   **Enhanced Real-Time Multi-Timer System**: Live duration updates, cross-component sync, manual time override, optimized bulk queries, professional commit workflows, and persistent timer overlay with Inertia.js layouts
-   **Advanced Email Configuration System**: Wizard-style SMTP/IMAP setup with OAuth2 and app password support, provider templates, manual port configuration, real-time testing, and horizontal scrolling navigation
-   **Service Ticket Integration**: Complete workflow system with timer integration, inline controls, and addon cost tracking
-   **TimeEntry Management**: Comprehensive approval workflows with bulk operations and statistical tracking
-   **User Invitation System**: Email-based invitations with automatic account setup and role assignment
-   **Laravel Sanctum Authentication**: Hybrid session/token auth with 23 granular abilities
-   **Domain-Based User Assignment**: Automatic account assignment via email domain patterns
-   **Real-Time Broadcasting Infrastructure**: Laravel Echo + Vue composables with cross-component timer synchronization
-   **Advanced Permission Caching**: Role-based permission caching with efficient resolution algorithms
-   **Comprehensive API**: 54+ endpoints with bulk operations, three-dimensional authentication, authorization, and preview capabilities
-   **Cross-Device Timer Sync**: Redis-based state management with conflict resolution
-   **Enterprise Authentication**: Token scoping (employee, manager, mobile-app, admin) with widget permissions
-   **Modern Frontend Stack**: Vue.js 3.5 + Inertia.js persistent layouts + TanStack Query + Tailwind CSS + Headless UI
-   **Optimized Dashboard Layout**: CSS Grid responsive design with progressive breakpoints and widget assignment controls

**🎯 Next Development Cycle:** Service Ticket Communication System and Universal Widget Framework Extension

## Documentation

All platform documentation is centralized in `/docs/index.md`. Refer to the documentation index for:

-   Development standards and workflows
-   Architecture and database design
-   Feature specifications
-   API documentation
-   Deployment guides

**Primary Reference**: [Documentation Index](docs/index.md)

## Development Server Policy

**IMPORTANT**: Never automatically start development servers without explicit user request:

-   **DO NOT** run `php artisan serve` automatically
-   **DO NOT** run `npm run dev` automatically
-   **DO NOT** run `npm run build --watch` automatically
-   User will manually start/restart development servers when needed

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

# Authentication & Token Management
# GET    /api/auth/tokens                # List user's API tokens
# POST   /api/auth/tokens                # Create new API token
# GET    /api/auth/tokens/abilities      # Get available token abilities
# POST   /api/auth/tokens/scope          # Create scoped token (employee, manager, etc.)
# DELETE /api/auth/tokens/revoke-all     # Revoke all user tokens

# Domain Mapping Management (Admin/Manager)
# GET    /api/domain-mappings            # List domain mappings
# POST   /api/domain-mappings            # Create domain mapping
# POST   /api/domain-mappings/preview    # Preview assignment for email
# GET    /api/domain-mappings/validate/requirements # System validation

# Standard Laravel CLI
php artisan make:model ModelName -mfs          # Model + migration/factory/seeder
php artisan make:controller Api/ModelController --api --model=Model  # API controller
php artisan make:policy ModelPolicy --model=Model  # Authorization policy

# Testing & debugging
php artisan test         # Run test suite
php artisan tinker       # Interactive shell
```

## Authentication Architecture

Service Vault implements a hybrid authentication system supporting both session-based web access and token-based API access:

### Session Authentication (Laravel Breeze)

-   **Web Dashboard**: Login/register pages with session cookies
-   **Inertia.js Integration**: CSRF-protected single-page application
-   **User Management**: Role assignment and ABAC permission integration

### Token Authentication (Laravel Sanctum)

-   **API Access**: Bearer token authentication for mobile and external clients
-   **Token Abilities**: Granular permissions system with scoped abilities:
    -   `timers:read`, `timers:write`, `timers:delete`, `timers:sync`
    -   `tickets:read`, `tickets:write`, `accounts:read`, `billing:read`
    -   `widgets:dashboard`, `pages:access` (for UI control)
    -   `admin:read`, `admin:write` (for administrative access)
-   **Predefined Scopes**: Ready-to-use token scopes for common use cases:
    -   `employee`: Basic timer and ticket access with essential widgets
    -   `manager`: Service oversight and approval capabilities with management widgets
    -   `mobile-app`: Full mobile application functionality with optimized widget set
    -   `admin`: Complete administrative access with all widgets and pages

### API Token Management

-   **Token CRUD**: Full REST API for creating, viewing, updating, and deleting tokens
-   **Password Verification**: Security confirmation for token operations
-   **Token Expiration**: Configurable expiration times for enhanced security
-   **Scope-based Creation**: Create tokens with predefined ability sets

### Policy Integration

All Laravel policies (e.g., `TimerPolicy`) support both authentication methods:

```php
// Check token abilities if API authenticated
if ($user->currentAccessToken()) {
    return $user->tokenCan('timers:read');
}
// Default ABAC permissions for web users
return $user->hasPermission('timers.view');
```

## Multi-Timer System Architecture

### Concurrent Timer Management

Service Vault supports multiple concurrent timers per user with comprehensive state synchronization:

#### Redis State Management

```php
// Individual timer state keys
user:{user_id}:timer:{timer_id}:state
user:{user_id}:active_timers  // Set of active timer IDs
user:{user_id}:last_sync      // Last synchronization timestamp
```

#### API Endpoints

```bash
# Multi-timer operations
POST /api/timers                    # Create timer (stop_others=false)
GET  /api/timers/active/current     # Get ALL active timers
POST /api/timers/sync               # Sync all timer states
POST /api/timers/bulk              # Bulk operations
```

#### Frontend Integration

```vue
<!-- TimerBroadcastOverlay.vue supports multiple timers -->
<TimerBroadcastOverlay />
<!-- Shows all active timers with individual controls -->
```

### Domain-Based User Assignment

#### Pattern Matching System

```php
// Wildcard domain pattern support
$mapping = DomainMapping::create([
    'domain_pattern' => '*.company.com',  // Matches subdomains
    'account_id' => $account->id,
    'priority' => 1
]);

// Automatic assignment on registration
$user = User::create($data);
$account = DomainAssignmentService::assignUserToAccount($user->email);
```

#### Management API

```bash
GET  /api/domain-mappings                 # List mappings
POST /api/domain-mappings                 # Create mapping
POST /api/domain-mappings/preview         # Preview assignment
GET  /api/domain-mappings/validate/requirements  # Validation
```

### Laravel Sanctum Token Management

#### Token Abilities System

```php
// 23 granular abilities across all system features
class TokenAbilityService {
    public const ABILITIES = [
        // Timer Management
        'timers:read' => 'View timer data',
        'timers:write' => 'Create and update timers',
        'timers:sync' => 'Cross-device synchronization',

        // Service Tickets
        'tickets:read' => 'View ticket information',
        'tickets:write' => 'Create and modify tickets',
        'tickets:account' => 'Access account hierarchy tickets',

        // Widget & UI Control
        'widgets:dashboard' => 'Access dashboard widgets',
        'widgets:configure' => 'Configure widget settings',
        'pages:access' => 'Access specific pages',

        // Account Management
        'accounts:read' => 'View account data',

        // Administrative
        'admin:read' => 'View administrative data',
        'admin:write' => 'Administrative operations',
    ];
}
```

#### Predefined Token Scopes

```php
// Ready-to-use scopes for common scenarios
public const SCOPES = [
    'employee' => ['timers:read', 'timers:write', 'tickets:read', 'widgets:dashboard'],
    'manager' => ['timers:read', 'timers:write', 'tickets:write', 'tickets:account', 'widgets:configure'],
    'mobile-app' => ['timers:read', 'timers:write', 'timers:sync', 'tickets:read', 'widgets:dashboard'],
    'admin' => ['*']  // All abilities including widgets and pages
];
```

### Real-Time Broadcasting Infrastructure

#### Laravel Echo Configuration

```javascript
// Conditional loading based on environment
if (import.meta.env.VITE_ENABLE_BROADCASTING === "true") {
    window.Echo = new Echo({
        broadcaster: "pusher",
        // Production WebSocket configuration
    });
} else {
    // Mock Echo for development
    window.Echo = mockEchoImplementation;
}
```

#### Vue Composable Integration

```javascript
// useTimerBroadcasting.js - Real-time timer management
export function useTimerBroadcasting() {
    // Handles multiple timer events
    // Cross-device synchronization
    // Optimistic UI updates
}
```

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

-   **Hybrid Authentication**: Laravel Sanctum (session + token) with granular abilities
-   **Multi-Timer Architecture**: Concurrent timer support with Redis state management
-   **Account Hierarchy System**: Customer account trees with visual hierarchical display and subsidiary access permissions
-   **Three-Dimensional Permission System**: Functional + Widget + Page access control
-   **ABAC Permission System**: Role templates with hierarchical inheritance and UI control
-   **Widget-Based Dashboard**: Permission-driven widget system with auto-discovery
-   **Real-Time Infrastructure**: Laravel Echo + Vue composables (WebSocket ready)
-   **API-First Backend**: RESTful endpoints with consistent JSON responses
-   **Component-Based Frontend**: Vue.js 3.5 + Inertia.js + Headless UI + Tailwind CSS
-   **Enterprise Theming**: CSS custom properties for multi-tenant branding

## Three-Dimensional Permission System

Service Vault implements a sophisticated permission system with three complementary dimensions:

### 1. Functional Permissions (What users can DO)

```php
// Account Hierarchy Permissions
'accounts.hierarchy.access'  // Access subsidiary accounts under same root
'accounts.manage'           // Manage account settings
'users.manage.account'      // Manage users within account hierarchy

// Service Ticket Permissions
'tickets.view.account'      // View tickets for accounts user belongs to
'tickets.edit.account'      // Edit tickets for user's accounts
'tickets.create.account'    // Create tickets for user's accounts
'tickets.assign.account'    // Assign tickets within user's accounts

// Time Tracking Permissions
'time.view.account'         // View time entries for account tickets
'time.edit.account'         // Edit time entries for account tickets
'time.admin'               // Administrative time tracking control

// System Administration
'admin.manage'             // System administration
'system.configure'         // System configuration
'billing.manage'           // Billing and financial management
```

### 2. Widget Permissions (What users can SEE on dashboard)

```php
// Dashboard Widget Categories
'widgets.dashboard.system-health'      // System Health widget
'widgets.dashboard.ticket-overview'    // Service Tickets Overview widget
'widgets.dashboard.my-tickets'         // My Tickets widget
'widgets.dashboard.time-tracking'      // Active Timers widget
'widgets.dashboard.all-timers'         // All Active Timers (Admin only)
'widgets.dashboard.billing-overview'   // Billing Overview widget
'widgets.dashboard.account-activity'   // Account Activity widget
'widgets.dashboard.quick-actions'      // Quick Actions widget

// Widget Configuration
'widgets.configure'                    // Configure widget settings
'dashboard.customize'                  // Customize dashboard layout
```

### 3. Page Access Permissions (What pages users can ACCESS)

```php
// Administrative Pages
'pages.admin.system'                   // System Administration page
'pages.settings.roles'                 // Roles & Permissions page
'pages.admin.users'                    // User Management page

// Service Management Pages
'pages.tickets.manage'                 // Tickets Management page
'pages.tickets.create'                 // Ticket Creation page
'pages.reports.account'                // Account Reports page

// Financial Pages
'pages.billing.overview'               // Billing Overview page
'pages.reports.billing'                // Billing Reports page

// Customer Portal Pages
'pages.portal.dashboard'               // Customer Portal Dashboard
'pages.portal.tickets'                 // Customer Ticket Portal
```

### Permission Model Example: Account Manager Role

```php
// Account Manager gets ALL THREE permission types:
$accountManagerPermissions = [
    // Functional Permissions - WHAT they can do
    'accounts.hierarchy.access',
    'tickets.view.account',
    'tickets.edit.account',
    'tickets.create.account',
    'users.manage.account',
    'time.view.account',

    // Widget Permissions - WHAT they can see on dashboard
    'widgets.dashboard.ticket-overview',
    'widgets.dashboard.account-activity',
    'widgets.dashboard.billing-overview',
    'widgets.dashboard.my-tickets',
    'widgets.configure',

    // Page Permissions - WHAT pages they can access
    'pages.tickets.manage',
    'pages.reports.account',
    'pages.billing.overview',
    'pages.portal.dashboard'
];
```
