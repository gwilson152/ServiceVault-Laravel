# Service Vault Documentation

A comprehensive B2B **ticketing/service request platform** with time management capabilities, built with Laravel 12. Features hierarchical customer account management, three-dimensional permission system, widget-based dashboards, and sophisticated service ticket workflows.

## System Overview

**Service Vault** is a B2B service management platform where **Service Provider Companies** deliver services to **Customer Organizations**. Both service providers and their customers use the same application with different access levels and interfaces.

### Platform Status

**Current Phase**: Phase 14B Complete (100% MVP Ready + Agent/Customer Architecture)  
**Last Updated**: August 13, 2025  
**Status**: Production-ready with comprehensive billing workflow, Agent/Customer user architecture, and advanced timer assignment system with database-level data integrity

### Core Features

- **Enhanced Agent/Customer Architecture**: Clear separation between service providers (Agents) and customers (Account Users) with role-based time entry creation
- **Advanced Timer Assignment System**: Flexible timer assignment to tickets OR accounts with commitment validation for billing accuracy
- **Three-Dimensional Permission System**: Comprehensive Functional + Widget + Page access control
- **Complete Billing & Financial Management**: Enterprise-grade invoicing, payment tracking, and billing rate management
- **Enhanced Ticket Detail Page**: Comprehensive central hub with tabbed interface for ticket interactions
- **Real-Time Messaging System**: Internal and external communication with live updates
- **Live Dashboard Preview System**: Real-time role preview with mock data generation and context switching
- **Advanced Widget Assignment Interface**: Drag & drop widget management with 12-column grid layout designer
- **Enhanced Multi-Timer System**: Professional timer overlays with streamlined time entry and commit workflows
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
- **[Development Guide](development/index.md)** - Development workflows and standards
- **[Features Documentation](features/index.md)** - User guides and feature specifications

### 🎯 Key Features

- **[Agent/Customer Architecture](features/agent-customer-architecture.md)** - Enhanced user type system with role-based access control
- **[Enhanced Timer Assignment](features/enhanced-timer-assignment.md)** - Flexible timer assignment with billing context validation
- **[Three-Dimensional Permissions](features/roles-permissions.md)** - Comprehensive permission system guide
- **[User Management](features/user-management.md)** - Invitation-based user onboarding with visibility controls
- **[Billing & Financial Management](features/billing-system.md)** - Complete invoicing, payments, and billing workflow
- **[Widget-Based Dashboard](features/dashboard-widgets.md)** - Dynamic dashboard with permission filtering
- **[Multi-Timer System](features/time-tracking.md)** - Concurrent timer management with real-time sync
- **[Service Tickets](features/service-tickets.md)** - Complete workflow management system
- **[Account Management](features/business-account-management.md)** - Hierarchical business relationships
- **[Email Configuration](system/email-configuration.md)** - SMTP/IMAP setup with OAuth and app password support

### 🔧 Technical References

- **[Timer System Architecture](architecture/timer-system.md)** - Multi-timer design and synchronization
- **[Billing System Architecture](architecture/billing-system.md)** - Complete billing workflow and database design
- **[Permission System](architecture/three-dimensional-permissions.md)** - Technical permission architecture
- **[Authentication System](system/authentication-system.md)** - Laravel Sanctum implementation
- **[Database Schema](architecture/database-schema.md)** - Complete data model reference

## Technology Stack

- **Backend**: Laravel 12 with PostgreSQL and Redis
- **Frontend**: Vue.js 3.5 + Inertia.js persistent layouts + TanStack Query + Tailwind CSS
- **Authentication**: Laravel Sanctum (session + API tokens)
- **Real-Time**: Laravel Echo with WebSocket broadcasting
- **Caching**: Redis for performance and timer state management

## Development Status

Service Vault is **production-ready** with comprehensive feature set:

- ✅ Three-dimensional permission system with role templates
- ✅ Complete billing & financial management system with invoicing, payments, and rate management
- ✅ Enhanced Agent/Customer architecture with role-based time entry creation and UI controls
- ✅ Advanced timer assignment system (ticket OR account) with commitment validation for billing accuracy
- ✅ Enhanced ticket detail page with comprehensive central hub interface  
- ✅ Real-time messaging system with internal and external communication
- ✅ Database-level data integrity with PostgreSQL triggers for consistency validation
- ✅ Widget-based dashboard with drag & drop configuration
- ✅ Multi-timer system with cross-device synchronization
- ✅ Complete service ticket workflow with addon support
- ✅ Account hierarchy management with domain mapping
- ✅ Real-time broadcasting and live updates
- ✅ Comprehensive API with Laravel Sanctum authentication
- ✅ Modern frontend with Vue.js 3.5 and TypeScript support

For detailed progress information, see [Development Progress Report](development/progress-report.md).

---

**Service Vault** - Complete B2B Service Management Platform with Enhanced Ticket Management and Streamlined Time Tracking

_Last Updated: August 13, 2025 - Phase 13B+ Complete - Enhanced Ticket Detail Page with Streamlined Time Entry System_