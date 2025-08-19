# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Service Vault is a comprehensive B2B service ticket and time management platform built with Laravel 12. It is primarily a **ticketing/service request platform** with sophisticated time tracking capabilities, featuring three-dimensional permission system (functional + widget + page access), and enterprise-level customization for multi-tenant service delivery.

### Current Status: Production-Ready Platform
**✅ Core Platform Features:**
- **Three-Dimensional Permission System**: Functional + Widget + Page access control with role template management
- **Multi-Timer System**: Concurrent timers with Redis state management and real-time sync
- **Unified Selector System**: Consistent selector components for tickets, accounts, users, agents, and billing rates
- **StackedDialog Architecture**: Native dialog-based modal system with proper stacking and z-index management
- **Complete Billing System**: Two-tier billing rate hierarchy (Account-specific → Global) with unified management
- **Feature-Specific Agent Permissions**: Granular agent assignment control for timers, tickets, time entries, and billing
- **Real-Time Broadcasting**: Laravel Reverb WebSocket server with instant ticket messaging and timer synchronization
- **TanStack Query Integration**: Optimized data fetching, caching, and error handling
- **Comprehensive API**: RESTful endpoints with bulk operations and three-dimensional authentication
- **Enterprise Authentication**: Laravel Sanctum hybrid session/token auth with granular abilities

**🎯 Platform Status:** Production-Ready

**Recent Key Improvements:**
- **Enhanced Billing Rate System**: Complete overhaul of billing rate selection and display across timer creation, editing, and commit workflows
- **Timer Billing Rate Preservation**: Timer commit now preserves original billing rates even when accounts have overriding rates with same names
- **Improved Rate Auto-Selection**: Both timer creation and time entry dialogs properly auto-select account default → global default rates
- **Enhanced Rate Display**: Billing rates now show "Description • $XX.XX/hr" format with clear disambiguation between account and global rates
- **Timer System Fixes**: Fixed timer creation and editing issues - ticket_id and account_id now save properly, edit modal displays pre-selected values correctly
- **Currency System Removal**: Removed all currency specifications from billing rates system for simplified pricing structure
- **Migration System Stability**: Fixed duplicate migration errors and improved migration resilience with existence checks
- **Admin Dashboard Enhancement**: Fixed recentActivity data structure to display system activities properly in chronological order
- **Timer Performance Optimization**: Eliminated N+1 query problem in tickets list by embedding user-specific timer data in ticket API responses
- **Account Hierarchy Removal**: Simplified account structure by removing complex hierarchy system while maintaining core functionality
- **Setup Page Streamlining**: Updated setup wizard to remove account hierarchy configuration for cleaner initial setup experience
- **Enhanced Unified Selector System**: Self-managing selectors with automatic data loading, debounced case-insensitive search, recent items tracking, and focus preservation
- **StackedDialog Architecture**: All modals converted to use native dialog with proper stacking and z-index management
- **Streamlined Ticket Creation**: Merged basic info and assignment tabs for improved user experience
- **Enhanced Modal System**: Vertical expansion to fit content, proper modal stacking, and consistent UI
- **Real-Time Ticket Messaging**: Live comments with Laravel Reverb WebSocket broadcasting and permission-based filtering
- **Enhanced Role Management**: Comprehensive permission descriptions, tooltips, notation guides, and improved layout with summary panel
- **Left-Aligned Timer Interface**: Timer overlay and individual timers now align left with improved visual hierarchy
- **Fixed Ticket Addon System**: Resolved UUID generation issues and standardized billable column usage across all models
- **Unified Ticket Edit Dialog**: Ticket detail page uses the same CreateTicketModalTabbed component for editing, providing consistent UI/UX with smart data prefilling

## Documentation

All platform documentation is centralized in `/docs/index.md`. Refer to the documentation index for:

- Development standards and workflows
- Architecture and database design
- Feature specifications
- API documentation
- Deployment guides

**Primary Reference**: [Documentation Index](docs/README.md)

## Development Server Policy

**IMPORTANT**: Never automatically start development servers without explicit user request:
- **DO NOT** run `php artisan serve` automatically
- **DO NOT** run `npm run dev` automatically  
- **DO NOT** run `npm run build --watch` automatically
- User will manually start/restart development servers when needed

### Documentation Maintenance Policy

**IMPORTANT**: Always update documentation when making code changes:

1. **Code Changes** → Update relevant documentation files
2. **New Features** → Add to `/docs/guides/` 
3. **API Changes** → Update `/docs/api/` specifications
4. **Architecture Changes** → Update `/docs/technical/`
5. **Development Process Changes** → Update `/docs/technical/development.md`

**Streamlined Documentation Structure**:
```
docs/
├── README.md                   # Master documentation index
├── guides/                     # User & developer guides
│   ├── setup.md               # Installation and configuration
│   ├── timers.md              # Timer system usage
│   ├── tickets-billing.md     # Service tickets and billing
│   └── users-permissions.md   # Users, roles, and permissions
├── api/                        # API reference documentation
│   ├── auth.md                # Authentication and tokens
│   ├── resources.md           # Core API endpoints
│   └── billing.md             # Billing and financial APIs
└── technical/                  # Technical implementation details
    ├── architecture.md         # System architecture
    ├── database.md            # Database schema reference
    └── development.md         # Development workflows
```

When creating new features or modifying existing ones, ensure documentation is updated in the same commit or pull request.

## Quick Start

```bash
# Development servers (DO NOT run these automatically - user will start them manually)
php artisan serve         # Start Laravel server (http://localhost:8000)
php artisan reverb:start  # Start WebSocket server (http://localhost:8080) - for real-time features
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

# Real-Time Broadcasting API (Laravel Reverb WebSocket)
# GET    /broadcasting/auth              # WebSocket channel authorization (session-based)
# Private channels: ticket.{id}, user.{id}, account.{id}
# Events: comment.created, timer.started, timer.stopped, etc.

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

# Unified Selector Search API (Self-Managing Components)
# GET    /api/search/tickets             # Search tickets with permission-aware filtering
# GET    /api/search/accounts            # Search accounts with permission-aware filtering  
# GET    /api/search/users               # Search users with permission-aware filtering
# GET    /api/search/agents              # Search agents with permission-aware filtering
# GET    /api/search/billing-rates       # Search billing rates with permission-aware filtering
# Parameters: q (search term), limit, permission_level, sort_field, sort_direction, agent_type
# Features: Case-insensitive search (ILIKE), debounced queries, intelligent ranking

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
- **Advanced CSRF Token Management**: Automatic refresh on 419 errors with proactive 10-minute intervals

**Quick Reference:**
```php
// Policy integration pattern
if ($user->currentAccessToken()) {
    return $user->tokenCan('timers:read');
}
return $user->hasPermission('timers.view');
```

**CSRF Token Management:**
```javascript
// Automatic CSRF refresh available globally
window.refreshCSRFToken();

// Automatic handling of 419 errors with retry
// Proactive refresh every 10 minutes
// No manual page refresh required
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
- **Timer Overlay**: `TimerBroadcastOverlay.vue` with persistent state and left-aligned UI layout

**⚠️ CRITICAL Navigation Rule:**
Always use Inertia.js navigation (`router.visit()` or `<Link>`) to maintain timer overlay persistence. Regular `<a>` tags or `window.location.href` cause full page reloads and break timer state.

**Domain-Based Assignment:**
Automatic user-to-account mapping via domain patterns. See [Authentication System](docs/system/authentication-system.md#domain-based-user-assignment).

## Unified Selector System

Service Vault uses a **self-managing** `UnifiedSelector` component for consistent entity selection across the entire application. Selectors automatically handle their own data loading, search, caching, and permissions.

**Supported Types:**
- `ticket` - Ticket selection with creation support
- `account` - Account selection with creation support  
- `user` - User selection (customer users)
- `agent` - Agent selection with feature-specific types (timer, ticket, time, billing)
- `billing-rate` - Billing rate selection with hierarchy display

**Key Features:**
- **Self-Managing Data**: Automatic data loading, search, and caching via TanStack Query
- **Permission-Aware**: Built-in permission filtering based on user context
- **Case-Insensitive Search**: Debounced search with PostgreSQL ILIKE for smooth UX
- **Recent Items Tracking**: localStorage-based recent selections with API fallback
- **Custom Sorting**: Configurable sort field and direction per selector
- **Focus Preservation**: Input maintains focus during search operations
- **Creation Support**: Built-in "Create New" options with proper modal stacking
- **Type-Specific Configurations**: Icons, colors, badges, and behaviors per type
- **Modal Stacking**: `nested` prop for proper z-index management

**Usage Example (New Self-Managing Architecture):**
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

**Available Props:**
- `type` - Entity type (required)
- `sort-field` - Custom sort field (e.g., 'name', 'created_at')
- `sort-direction` - Sort direction ('asc' or 'desc')
- `agent-type` - For agent selectors: 'timer', 'ticket', 'time', 'billing'
- `filter-set` - Applied filters for context-aware results
- `custom-items` - Override with custom dataset for special cases
- `clearable` - Allow clearing selection (default: true)
- `recent-items-limit` - Number of recent items to show (default: 10)
- `search-min-length` - Minimum characters before API search (default: 2)

**Migration from Old Pattern:**
```vue
<!-- OLD: Manual data management -->
<UnifiedSelector :items="availableAccounts" />

<!-- NEW: Self-managing (no items prop needed) -->
<UnifiedSelector type="account" />
```

**Technical Implementation:**
- **Query Composables**: `/resources/js/Composables/queries/useSelectorQueries.js`
- **Search API Controller**: `/app/Http/Controllers/Api/SearchController.php`
- **Permission Integration**: Automatic filtering based on user context and abilities
- **TanStack Query**: Optimized caching with reactive query keys
- **Recent Items**: localStorage persistence with `selector_recent_{type}` keys

## StackedDialog Architecture

Service Vault uses a native `<dialog>`-based modal system for proper stacking and accessibility:

**Key Features:**
- **Native Dialog Elements**: Uses HTML5 `<dialog>` for proper modal behavior
- **Automatic Stacking**: Manages z-index automatically for nested modals
- **Consistent Header**: Unified header with title and close button
- **Vertical Expansion**: Dialogs expand to fit content without artificial height limits
- **Teleport Support**: Proper rendering outside component tree

**Usage Example:**
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

**Modal Conversion:** All core modals have been converted from the old `Modal` component to `StackedDialog` for consistency and proper stacking behavior.

## Billing Rate System

Service Vault features a **simplified two-tier billing rate hierarchy** for clear and manageable pricing:

**Rate Priority (Highest to Lowest):**
1. **Account-Specific Rates** - Custom rates for individual accounts
2. **Global Default Rates** - System-wide fallback rates

**Key Implementation Notes:**
- **Unified Components**: Shared `BillingRateModal` component works across settings and account contexts
- **Visual Hierarchy**: Color-coded badges (Blue for account rates, Green for global rates)
- **Inheritance System**: Child accounts inherit parent account rates unless overridden
- **Unified Selector**: `UnifiedSelector` type="billing-rate" with group headers and rate hierarchy
- **API Integration**: `/api/billing-rates?account_id={id}` returns account + inherited + global rates

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
- **Three-Dimensional Permission System**: Functional + Widget + Page access control
- **Unified Component System**: Single components handle multiple entity types consistently
- **StackedDialog Architecture**: Native dialog-based modals with proper z-index management
- **Real-Time Infrastructure**: Laravel Echo + Vue composables for live updates
- **API-First Backend**: RESTful endpoints with consistent JSON responses
- **Modern Frontend Stack**: Vue.js 3.5 + Inertia.js + TanStack Query + Tailwind CSS

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

**Centralized Permission Service:** Use `App\Services\PermissionService` for all agent filtering and permission logic to ensure consistency across the application. See [Permission System Documentation](docs/architecture/permissions.md).

## Feature-Specific Agent Permission System

Service Vault implements a comprehensive feature-specific agent permission system that provides granular control over which users can act as agents for different system features. See [Feature-Specific Agent Permissions](docs/features/feature-specific-agent-permissions.md) for complete details.

**Core Agent Permissions:**
- `timers.act_as_agent` - Timer creation and assignment
- `tickets.act_as_agent` - Ticket assignment and management  
- `time.act_as_agent` - Time entry creation and assignment
- `billing.act_as_agent` - Billing responsibility assignment

**Multi-Layer Agent Determination:**
1. **Primary**: Users with `user_type = 'agent'`
2. **Secondary**: Users with feature-specific `*.act_as_agent` permissions
3. **Tertiary**: Internal account users with relevant fallback permissions

**API Integration:**
```bash
# Feature-specific agent endpoints
GET /api/users/agents?agent_type=timer          # Timer agents
GET /api/users/agents?agent_type=time           # Time entry agents  
GET /api/users/assignable                       # Ticket agents (uses tickets.act_as_agent)
GET /api/users/billing-agents                   # Billing agents
```

**Component Integration:**
```vue
<!-- StartTimerModal uses timer agent type -->
<UnifiedSelector type="agent" agent-type="timer" />

<!-- UnifiedTimeEntryDialog uses time agent type -->
<UnifiedSelector type="agent" agent-type="time" />
```

**Permission Helper Methods:**
```php
// In UserController
private function getRequiredAgentPermission(string $agentType): string
{
    return match($agentType) {
        'timer' => 'timers.act_as_agent',
        'ticket' => 'tickets.act_as_agent',
        'time' => 'time.act_as_agent', 
        'billing' => 'billing.act_as_agent',
        default => 'timers.act_as_agent'
    };
}
```

## 📚 Complete Documentation Reference

For comprehensive technical details, refer to the **streamlined documentation** in `/docs/`:

### **User & Developer Guides**
- **[Setup Guide](docs/guides/setup.md)** - Installation, configuration, and quick start
- **[Timer System Guide](docs/guides/timers.md)** - Multi-timer usage, concurrent timers, real-time sync
- **[Tickets & Billing Guide](docs/guides/tickets-billing.md)** - Service tickets, billing rates, invoice generation
- **[Users & Permissions Guide](docs/guides/users-permissions.md)** - Three-dimensional permissions, role management, feature-specific agents

### **API Reference Documentation**
- **[Authentication API](docs/api/auth.md)** - Laravel Sanctum, tokens, sessions, domain mapping
- **[Core Resources API](docs/api/resources.md)** - Tickets, timers, time entries, users, accounts
- **[Billing & Financial API](docs/api/billing.md)** - Billing rates, invoices, payments, reporting

### **Technical Implementation**
- **[System Architecture](docs/technical/architecture.md)** - Technology stack, core systems, performance, security
- **[Database Schema](docs/technical/database.md)** - PostgreSQL schema, relationships, indexes, migrations
- **[Development Guide](docs/technical/development.md)** - Coding standards, testing, deployment, troubleshooting

### **Documentation Index**
- **[Master Documentation Index](docs/README.md)** - Complete overview with quick start and recent fixes

This CLAUDE.md file focuses on essential development context. For detailed implementation guides, API specifications, and system architecture, always refer to the streamlined documentation structure in `/docs/`.

---

*Last Updated: August 19, 2025 - Enhanced Unified Selector System: Complete overhaul of selector architecture to self-managing components. Selectors now automatically handle data loading, debounced case-insensitive search (PostgreSQL ILIKE), recent items tracking with localStorage, and focus preservation during search operations. Added new /api/search/* endpoints with permission-aware filtering and customizable sorting. Fixed query reactivity issues and dropdown state management for smooth search UX. Updated CreateTicketModalTabbed to use new selector architecture. All selectors now support sort-field, sort-direction, and filter-set props for enhanced customization.*