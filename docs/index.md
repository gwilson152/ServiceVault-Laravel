# Service Vault Documentation

A comprehensive B2B **ticketing/service request platform** with time management capabilities, built with Laravel 12. Features hierarchical customer account management, three-dimensional permission system, widget-based dashboards, and sophisticated service ticket workflows.

## System Overview

**Service Vault** is a B2B service management platform where **Service Provider Companies** deliver services to **Customer Organizations**. Both service providers and their customers use the same application with different access levels and interfaces.

### Platform Status

**Current Phase**: Phase 15A+ Complete (100% Production Ready + Permission Descriptions + Enhanced UX)  
**Last Updated**: August 18, 2025  
**Status**: Fully production-ready with comprehensive permission descriptions, enhanced role management UX, and complete documentation

### Core Features

- **Enhanced Agent/Customer Architecture**: Clear separation between service providers (Agents) and customers (Account Users) with role-based time entry creation
- **Feature-Specific Agent Permissions**: Granular agent assignment control with four specialized permissions (timers, tickets, time, billing) and multi-layer determination logic
- **TanStack Query Optimization**: Complete migration to TanStack Query with reactive caching, optimistic updates, and background synchronization
- **ABAC Permission System**: Comprehensive attribute-based access control for timer operations with UI-level permission enforcement
- **Enhanced Selector Components**: Professional selectors with integrated creation workflows - HierarchicalAccountSelector and UserSelector both include "Create New" functionality with CSRF protection
- **Advanced Timer Assignment System**: Flexible timer assignment to tickets OR accounts with commitment validation for billing accuracy
- **Three-Dimensional Permission System**: Comprehensive Functional + Widget + Page access control with enhanced role management UX, permission descriptions, and smart tooltips
- **Complete Billing & Financial Management**: Enterprise-grade invoicing, payment tracking, and billing rate management
- **Enhanced Ticket Detail Page**: Comprehensive central hub with tabbed interface for ticket interactions - **FULLY FUNCTIONAL**
- **Real-Time Messaging System**: Laravel Reverb WebSocket server with instant ticket comments, permission-based filtering, and conversation-style UI
- **Glass Effect Timer Overlay**: Modern gradient glass morphism timer overlay with Agent/Customer filtering and ABAC permission controls
- **Unified Time Entry Components**: Consolidated timer commit workflows with multiline work descriptions and automatic data preloading
- **Customer Portal Interface**: Complete portal pages for customer ticket management, project tracking, and billing access
- **Enhanced Account Management**: Full account detail pages with comprehensive CRUD operations and relationship management
- **Invitation-Based User Onboarding**: Complete invitation acceptance workflow with timezone detection and role assignment
- **Live Dashboard Preview System**: Real-time role preview with mock data generation and context switching
- **Advanced Widget Assignment Interface**: Drag & drop widget management with 12-column grid layout designer
- **Enhanced Multi-Timer System**: Professional timer overlays with billing rate selection, streamlined time entry, and commit workflows
- **Service Ticket Management**: Comprehensive workflow with timer integration and addon support
- **Business Account Management**: Hierarchical customer account relationships with visual display
- **Real-Time Broadcasting**: Laravel Echo + Vue composables for live updates
- **Account Context Switching**: Service providers can manage multiple customer organizations

## Quick Start

```bash
# Installation
composer install --optimize-autoloader
npm install
cp .env.example .env && php artisan key:generate
php artisan migrate:fresh --seed

# Development
php artisan serve    # Laravel server (localhost:8000)
npm run dev         # Vite dev server with HMR
```

For complete setup instructions, see [System Documentation](system/index.md).

## Documentation Structure

### 📚 Core Documentation

- **[System Setup & Administration](system/index.md)** - Installation, configuration, and maintenance
- **[API Reference](api/index.md)** - Complete REST API documentation
- **[Architecture Overview](architecture/index.md)** - System design and technical specifications
- **[Component Documentation](components/index.md)** - Vue.js components and UI systems
- **[Development Guide](development/index.md)** - Development workflows and standards
- **[Features Documentation](features/index.md)** - User guides and feature specifications

### 🎯 Key Features

- **[Agent/Customer Architecture](features/agent-customer-architecture.md)** - Enhanced user type system with role-based access control
- **[Feature-Specific Agent Permissions](features/feature-specific-agent-permissions.md)** - Granular agent assignment control with feature-based permissions
- **[Enhanced Timer Assignment](features/enhanced-timer-assignment.md)** - Flexible timer assignment with billing context validation
- **[Three-Dimensional Permissions](features/roles-permissions.md)** - Comprehensive permission system guide
- **[User Management](features/user-management.md)** - Invitation-based user onboarding with visibility controls
- **[Billing & Financial Management](features/billing-system.md)** - Complete invoicing, payments, and billing workflow
- **[Widget-Based Dashboard](features/dashboard-widgets.md)** - Dynamic dashboard with permission filtering
- **[Multi-Timer System](features/time-tracking.md)** - Concurrent timer management with real-time sync
- **[Service Tickets](features/service-tickets.md)** - Complete workflow management system
- **[Account Management](features/business-account-management.md)** - Hierarchical business relationships
- **[Real-Time Messaging](features/real-time-messaging.md)** - Live ticket comments with Laravel Reverb WebSocket broadcasting
- **[Email Configuration](system/email-configuration.md)** - SMTP/IMAP setup with OAuth and app password support
- **[Debug Overlay System](features/debug-overlay-system.md)** - Super Admin debug tools for development and troubleshooting
- **[Nuclear System Reset](system/nuclear-reset.md)** - Complete system reset for development and emergency recovery ⚠️

### 🔧 Technical References

- **[Enhanced Dialog System](components/dialog-system.md)** - Auto-stacking dialogs with tabbed interfaces and smart dismissal
- **[Timer System Architecture](architecture/timer-system.md)** - Multi-timer design and synchronization
- **[Billing System Architecture](architecture/billing-system.md)** - Complete billing workflow and database design
- **[Permission System](architecture/three-dimensional-permissions.md)** - Technical permission architecture
- **[Modal Dialog System](architecture/modal-dialog-system.md)** - StackedDialog, TabbedDialog, and dropdown overflow management
- **[Authentication System](system/authentication-system.md)** - Laravel Sanctum implementation
- **[Database Schema](architecture/database-schema.md)** - Complete data model reference

## Technology Stack

- **Backend**: Laravel 12 with PostgreSQL and Redis
- **Frontend**: Vue.js 3.5 + Inertia.js persistent layouts + TanStack Query + Tailwind CSS
- **Authentication**: Laravel Sanctum (session + API tokens)
- **Real-Time**: Laravel Reverb WebSocket server with instant messaging and broadcasting
- **Caching**: Redis for performance and timer state management

## Development Status

Service Vault is **fully production-ready** with comprehensive feature set and all critical workflows refined:

### ✅ **Core Platform Features**
- Three-dimensional permission system with role templates and hierarchical inheritance
- Complete billing & financial management system with invoicing, payments, and rate management
- Enhanced Agent/Customer architecture with role-based time entry creation and UI controls
- Advanced timer assignment system (ticket OR account) with commitment validation for billing accuracy
- Widget-based dashboard with drag & drop configuration and permission filtering
- Multi-timer system with cross-device synchronization and Redis state management
- Complete service ticket workflow with addon support and approval workflows
- Account hierarchy management with domain mapping and subsidiary access

### ✅ **Recent Workflow Refinements (Phase 15A+)**
- **Enhanced Modal Dialog System**: StackedDialog and TabbedDialog with `allowDropdowns` prop for perfect dropdown overflow management and universal selector fix across all modals
- **Enhanced Role Management UX**: Comprehensive permission descriptions, smart tooltips, responsive layout, and permission summary panels for improved role editing experience
- **Enhanced Ticket Detail Page**: Fully functional central hub with all tabs working (messages, time tracking, addons, activity, billing) and proper API integration
- **Professional UI Selector Components**: HierarchicalAccountSelector, TicketSelector, and BillingRateSelector with unified UX patterns, viewport-aware positioning, and conditional display logic
- **Enhanced Timer Creation Workflow**: Streamlined quick start form with smart defaults, account-dependent ticket filtering, and automatic default billing rate selection
- **Refined Timer Broadcast Overlay**: Smart overlay that shows for Agents with polished selector components, proper user type filtering, and enhanced "No active timers" state management
- **Complete Account Management**: Full account detail pages with comprehensive CRUD operations, contact management, and relationship display
- **Invitation Acceptance Workflow**: Complete invitation acceptance component with timezone detection, form validation, and automatic role assignment
- **Customer Portal Architecture**: Full portal interface components for customer ticket management, project tracking, and billing access
- **Enhanced Error Handling**: Comprehensive error states, loading indicators, and user-friendly error messages across all components
- **Enhanced Setup System**: Robust setup completion detection using `system.setup_complete` setting, automatic seeding of essential system data, and dual-middleware protection
- **Enhanced Selector Workflows**: HierarchicalAccountSelector and UserSelector with integrated creation functionality, CSRF protection, and seamless UX patterns
- **Refined Ticket Creation UX**: Removed auto-account selection for explicit user control and improved account selection workflow
- **Database-level Data Integrity**: PostgreSQL triggers for consistency validation and referential integrity
- **Auto-Reopen UX Enhancement**: Smart dropdown behavior with automatic focus and viewport positioning after clearing selections
- **Built-in Label Support**: Integrated label, error, and validation handling eliminating duplicate UI elements

### ✅ **API & Integration Layer**
- **58+ REST API endpoints** with comprehensive coverage for all features
- **Related tickets endpoint** with intelligent similarity matching
- **TanStack Query integration** for optimal data fetching and caching
- **Real-time broadcasting** with Laravel Echo + Vue composables
- **Laravel Sanctum authentication** with hybrid session/token support and 23 granular abilities
- **Domain-based user assignment** with automatic account mapping

### ✅ **User Experience & Interface**
- **Vue.js 3.5 + Inertia.js** persistent layouts with modern reactive patterns
- **Tailwind CSS + HeadlessUI** for consistent, accessible component design
- **TanStack Tables** with advanced filtering, sorting, and pagination
- **Responsive design** with mobile-first approach and progressive breakpoints
- **Dark mode ready** with CSS custom properties for theming

For detailed progress information, see [Development Progress Report](development/progress-report.md).

---

**Service Vault** - Complete B2B Service Management Platform with Enhanced Ticket Management and Professional UI Components

_Last Updated: August 18, 2025 - Phase 15A+ Complete - Enhanced Modal Dialog System with Universal Dropdown Overflow Fix and Comprehensive Permission Descriptions_