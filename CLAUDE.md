# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Service Vault is a comprehensive B2B service ticket and time management platform built with Laravel 12. It is primarily a **ticketing/service request platform** with sophisticated time tracking capabilities, featuring hierarchical customer account management, three-dimensional permission system (functional + widget + page access), account hierarchy permissions, and enterprise-level customization for multi-tenant service delivery.

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
- **Unified Selector System**: Single component handles tickets, accounts, users, agents, and billing rates with consistent interface
- **StackedDialog Architecture**: All modals converted to use native dialog with proper stacking and z-index management
- **Streamlined Ticket Creation**: Merged basic info and assignment tabs for improved user experience
- **Enhanced Modal System**: Vertical expansion to fit content, proper modal stacking, and consistent UI
- **Real-Time Ticket Messaging**: Live comments with Laravel Reverb WebSocket broadcasting and permission-based filtering
- **Enhanced Role Management**: Comprehensive permission descriptions, tooltips, notation guides, and improved layout with summary panel
- **Left-Aligned Timer Interface**: Timer overlay and individual timers now align left with improved visual hierarchy
- **Fixed Ticket Addon System**: Resolved UUID generation issues and standardized billable column usage across all models

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
- **Timer Overlay**: `TimerBroadcastOverlay.vue` with persistent state and left-aligned UI layout

**⚠️ CRITICAL Navigation Rule:**
Always use Inertia.js navigation (`router.visit()` or `<Link>`) to maintain timer overlay persistence. Regular `<a>` tags or `window.location.href` cause full page reloads and break timer state.

**Domain-Based Assignment:**
Automatic user-to-account mapping via domain patterns. See [Authentication System](docs/system/authentication-system.md#domain-based-user-assignment).

## Unified Selector System

Service Vault uses a single `UnifiedSelector` component for consistent entity selection across the entire application:

**Supported Types:**
- `ticket` - Ticket selection with creation support
- `account` - Hierarchical account selection with creation support  
- `user` - User selection (customer users)
- `agent` - Agent selection with feature-specific types (timer, ticket, time, billing)
- `billing-rate` - Billing rate selection with hierarchy display

**Key Features:**
- **Consistent Interface**: Same props and events across all entity types
- **Creation Support**: Built-in "Create New" options with proper modal stacking
- **Type-Specific Configurations**: Icons, colors, badges, and behaviors per type
- **Hierarchical Display**: Visual hierarchy for accounts and billing rates
- **Modal Stacking**: `nested` prop for proper z-index management
- **Search & Filter**: Built-in search and filtering capabilities

**Usage Example:**
```vue
<UnifiedSelector
  v-model="selectedId"
  type="account"
  :items="availableAccounts"
  label="Account"
  placeholder="Select account..."
  :hierarchical="true"
  :can-create="true"
  :nested="true"
  @item-selected="handleSelection"
  @item-created="handleCreation"
/>
```

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
<UnifiedSelector type="agent" agent-type="timer" :items="availableAgents" />

<!-- UnifiedTimeEntryDialog uses time agent type -->
<UnifiedSelector type="agent" agent-type="time" :items="availableAgents" />
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

## 📚 Detailed Technical Documentation

For comprehensive technical details, refer to the organized documentation in `/docs/`:

### **Core System Architecture**
- **[Authentication System](docs/system/authentication-system.md)** - Laravel Sanctum, token management, domain-based assignment
- **[Three-Dimensional Permissions](docs/architecture/three-dimensional-permissions.md)** - Functional + Widget + Page permission system
- **[Feature-Specific Agent Permissions](docs/features/feature-specific-agent-permissions.md)** - Granular agent assignment control with multi-layer determination
- **[Timer System Architecture](docs/architecture/timer-system.md)** - Multi-timer design, Redis state, real-time sync
- **[Time Management](docs/features/time-management.md)** - Tabbed interface, API endpoints, permission matrix

### **Additional Documentation**
- **[Documentation Index](docs/index.md)** - Complete documentation structure and overview
- **[API Reference](docs/api/index.md)** - REST API endpoints and specifications  
- **[Development Guide](docs/development/index.md)** - Development workflows and standards
- **[Features Documentation](docs/features/index.md)** - User guides and feature specifications

This CLAUDE.md file focuses on essential information for AI development assistance. For detailed implementation guides, architecture deep-dives, and comprehensive feature documentation, always refer to the structured documentation in the `/docs` folder.

---

*Last Updated: August 18, 2025 - Ticket Addon System Fixes: Resolved UUID generation issues in TicketAddon model by properly implementing HasUuid trait. Standardized billable column usage across Timer, TimeEntry, and related controllers for consistency. Removed incorrect timeEntries relationship from TicketAddon model (addons and time entries are both associated with tickets, but not with each other).*