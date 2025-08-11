# Service Vault Documentation

A comprehensive B2B **ticketing/service request platform** with time management capabilities, built with Laravel 12. Features hierarchical customer account management, three-dimensional permission system, widget-based dashboards, and sophisticated service ticket workflows.

## System Overview

**Service Vault** is a B2B service management platform where **Service Provider Companies** deliver services to **Customer Organizations**. Both service providers and their customers use the same application with different access levels and interfaces.

### Platform Status

**Current Phase**: Phase 13A Complete (100% MVP Ready)  
**Last Updated**: August 11, 2025  
**Status**: Production-ready with complete three-dimensional permission system

### Core Features

- **Three-Dimensional Permission System**: Comprehensive Functional + Widget + Page access control
- **Live Dashboard Preview System**: Real-time role preview with mock data generation and context switching
- **Advanced Widget Assignment Interface**: Drag & drop widget management with 12-column grid layout designer
- **Multi-Timer System**: Concurrent timers with cross-device synchronization and app-wide visibility
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

- **[Three-Dimensional Permissions](features/roles-permissions.md)** - Comprehensive permission system guide
- **[Widget-Based Dashboard](features/dashboard-widgets.md)** - Dynamic dashboard with permission filtering
- **[Multi-Timer System](features/time-tracking.md)** - Concurrent timer management with real-time sync
- **[Service Tickets](features/service-tickets.md)** - Complete workflow management system
- **[Account Management](features/business-account-management.md)** - Hierarchical business relationships

### 🔧 Technical References

- **[Timer System Architecture](architecture/timer-system.md)** - Multi-timer design and synchronization
- **[Permission System](architecture/three-dimensional-permissions.md)** - Technical permission architecture
- **[Authentication System](system/authentication-system.md)** - Laravel Sanctum implementation
- **[Database Schema](architecture/database-schema.md)** - Complete data model reference

## Technology Stack

- **Backend**: Laravel 12 with PostgreSQL and Redis
- **Frontend**: Vue.js 3.5 + Inertia.js + Tailwind CSS
- **Authentication**: Laravel Sanctum (session + API tokens)
- **Real-Time**: Laravel Echo with WebSocket broadcasting
- **Caching**: Redis for performance and timer state management

## Development Status

Service Vault is **production-ready** with comprehensive feature set:

- ✅ Three-dimensional permission system with role templates
- ✅ Widget-based dashboard with drag & drop configuration
- ✅ Multi-timer system with cross-device synchronization
- ✅ Complete service ticket workflow with addon support
- ✅ Account hierarchy management with domain mapping
- ✅ Real-time broadcasting and live updates
- ✅ Comprehensive API with Laravel Sanctum authentication
- ✅ Modern frontend with Vue.js 3.5 and TypeScript support

For detailed progress information, see [Development Progress Report](development/progress-report.md).

---

**Service Vault** - Complete B2B Service Management Platform with Three-Dimensional Permission System

_Last Updated: August 11, 2025 - Phase 13A Complete - 100% MVP Ready_