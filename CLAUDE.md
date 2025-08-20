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
- **Enhanced Time Entry Management**: Fixed critical duration calculation bugs, implemented automatic billing rate selection (account default → global default), and made ticket selection optional for flexible time tracking
- **Improved API Consistency**: Standardized parameter naming from `assigned_user_id` to `agent_id` across ticket assignment APIs for better clarity and database schema alignment
- **Fixed Database Query Issues**: Resolved billing rates table column errors and UnifiedSelector initialization problems that were causing 500 errors on time entries page
- **Optimized UnifiedSelector Architecture**: Fixed component initialization order issues and improved error handling for smoother UI interactions across all selector components
- **Complete Billing Page with Approval Workflow**: Enhanced invoice generation with integrated approval workflow, visual status indicators, and bulk operations for time entries and ticket addons
- **Advanced Approval System**: Step-through approval wizard accessible from billing page and time entries, with individual/bulk approval capabilities and progress tracking
- **Smart Invoice Creation**: Account-based item discovery with automatic approval status filtering, real-time totals, and visual indicators for pending vs approved items
- **TanStack Query Integration**: Complete migration from direct fetch calls to TanStack Query for billing operations with automatic cache invalidation and error handling
- **Timer Commit System**: Fixed timer commit workflow - timers now properly update to "committed" status in database with time_entry_id linkage, persistent across page refreshes
- **Standardized Duration Handling**: Corrected duration calculations throughout the system - consistent minutes-based storage with proper validation and display formatting
- **Simplified Timer UX**: Removed unnecessary "Override timer duration" checkbox - duration fields now pre-fill directly from timer data and are immediately editable
- **Enhanced Error Handling**: Added robust error handling to TimerBroadcastOverlay formatDuration function to prevent Vue component crashes
- **Enhanced Edit Ticket Dialog Preselection**: Edit ticket dialog now properly preselects account, customer, and agent with visual context indicators and activeSelection support
- **Enhanced Time Entry Creation from Tickets**: Time entry creation from `/tickets/id` page now automatically preselects account, ticket, and proper billing rate hierarchy (account default → global default)
- **Fixed Selector Dropdown Issues**: Resolved issue where searched selectors wouldn't close after item selection, improving user experience across all selector components
- **Enhanced Unified Selector System**: Self-managing selectors with automatic data loading, debounced case-insensitive search, recent items tracking, focus preservation, and proper dropdown dismissal
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
- **StackedDialog Architecture**: All modals converted to use native dialog with proper stacking and z-index management
- **Streamlined Ticket Creation**: Merged basic info and assignment tabs for improved user experience
- **Enhanced Modal System**: Vertical expansion to fit content, proper modal stacking, and consistent UI
- **Real-Time Ticket Messaging**: Live comments with Laravel Reverb WebSocket broadcasting and permission-based filtering
- **Enhanced Role Management**: Comprehensive permission descriptions, tooltips, notation guides, and improved layout with summary panel
- **Left-Aligned Timer Interface**: Timer overlay and individual timers now align left with improved visual hierarchy
- **Fixed Ticket Addon System**: Resolved UUID generation issues and standardized billable column usage across all models
- **Unified Ticket Edit Dialog**: Ticket detail page uses the same CreateTicketModalTabbed component for editing, providing consistent UI/UX with smart data prefilling

## Documentation

All platform documentation is centralized in `/docs/`. Refer to the documentation index for:

- Development standards and workflows
- Architecture and database design
- Feature specifications
- API documentation
- Deployment guides

**Primary Reference**: [Documentation Index](docs/README.md)

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

## API Reference

```bash
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
# POST   /api/time-entries               # Create new time entry (supports timer_id for timer commits)
#        Required: description, duration (minutes), started_at, account_id
#        Optional: ticket_id, user_id, billing_rate_id, rate_override, billable, notes
#        Duration validation: min:1, max:1440 (1 minute to 24 hours in minutes)
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
# GET    /api/search/role-templates      # Search role templates with permission-aware filtering
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

# Ticket Assignment API (Improved Parameter Naming)
# PUT    /api/tickets/{id}/assign        # Assign/unassign ticket to agent
#        Parameters: agent_id (nullable), assignment_reason, priority, notify_*
#        Updated from deprecated 'assigned_user_id' to clear 'agent_id' parameter

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

*Last Updated: August 20, 2025 - Enhanced Time Entry Management & API Consistency: Implemented automatic billing rate selection (account default → global default) in time entry dialogs. Fixed critical duration calculation bugs throughout the system for consistent minutes-based storage. Made ticket selection optional for flexible time tracking. Standardized API parameter naming from `assigned_user_id` to `agent_id` for better clarity. Resolved database query issues and UnifiedSelector initialization problems causing 500 errors. Improved error handling and component architecture for smoother UI interactions across all selector components.*