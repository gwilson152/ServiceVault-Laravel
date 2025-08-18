# Service Vault Documentation

B2B **ticketing and time management platform** built with Laravel 12. Features account hierarchy, role-based permissions, real-time messaging, and service ticket workflows.

## Quick Overview

**Service Vault** enables service companies to manage customer tickets, track time, and handle billing. Both service providers and customers use the platform with role-based access controls.

**Status**: Production Ready  
**Last Updated**: August 18, 2025

### Key Features

- **Ticket Management**: Full service ticket workflow with real-time messaging
- **Time Tracking**: Multi-timer system with cross-device sync and billing integration  
- **Account Management**: Hierarchical customer accounts with role-based access
- **Permission System**: Three-dimensional access control (Functional + Widget + Page)
- **Billing**: Invoice generation, payment tracking, and rate management
- **Real-Time**: WebSocket messaging with Laravel Reverb
- **User Experience**: Modern Vue.js interface with unified selectors and modals

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

## Documentation

### Core Guides
- **[System Setup](system/index.md)** - Installation and configuration
- **[API Reference](api/index.md)** - REST API endpoints
- **[Development](development/index.md)** - Development workflow
- **[Features](features/index.md)** - User feature guides

### Key Systems
- **[Permission System](features/roles-permissions.md)** - Three-dimensional access control
- **[Time Management](features/time-management.md)** - Multi-timer and time tracking
- **[Billing System](features/billing-rate-selector.md)** - Rate management and invoicing
- **[Real-Time Messaging](features/real-time-messaging.md)** - WebSocket ticket messaging
- **[Account Preselection](features/account-preselection.md)** - Auto-selection in modals

### Architecture
- **[Timer System](architecture/timer-system.md)** - Multi-timer architecture
- **[Modal System](architecture/modal-dialog-system.md)** - Dialog and modal handling  
- **[Authentication](system/authentication-system.md)** - Laravel Sanctum setup

## Technology Stack

- **Backend**: Laravel 12 + PostgreSQL + Redis
- **Frontend**: Vue.js 3.5 + Inertia.js + TanStack Query + Tailwind CSS  
- **Real-Time**: Laravel Reverb WebSocket server
- **Auth**: Laravel Sanctum (sessions + API tokens)

---

**Service Vault** - B2B Service Management Platform  
_Last Updated: August 18, 2025 - Production Ready with Account Preselection_