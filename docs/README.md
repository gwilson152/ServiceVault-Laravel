# Service Vault Documentation

B2B **ticketing and time management platform** built with Laravel 12. Features role-based permissions, real-time messaging, and service ticket workflows.

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

**Important**: Never automatically start development servers - users start them manually when needed.

## Documentation Structure

### 📚 [User & Developer Guides](guides/)
- **[Setup Guide](guides/setup.md)** - Installation and configuration
- **[Timer System](guides/timers.md)** - Multi-timer usage and features
- **[Tickets & Billing](guides/tickets-billing.md)** - Service tickets and billing
- **[User Management](guides/users-permissions.md)** - Users, roles, and permissions
- **[Import System](guides/import.md)** - PostgreSQL database import and data migration

### 🔧 [API Reference](api/)
- **[Authentication](api/auth.md)** - Login, tokens, and session management
- **[Core Resources](api/resources.md)** - Tickets, timers, users, accounts
- **[Billing](api/billing.md)** - Billing rates, invoices, payments
- **[Import API](api/import.md)** - Database import profiles and job management

### ⚙️ [Technical Documentation](technical/)
- **[System Architecture](technical/architecture.md)** - Overall system design
- **[Database Schema](technical/database.md)** - Models, migrations, relationships
- **[Development Guide](technical/development.md)** - Coding standards and workflows

## Key Features

- **✅ Production Ready** - Stable platform with comprehensive testing
- **🔐 Three-Dimensional Permissions** - Functional + Widget + Page access control
- **⏱️ Multi-Timer System** - Concurrent timers with real-time sync
- **🎫 Service Tickets** - Complete ticketing workflow with messaging
- **💰 Billing Integration** - Rate management and invoice generation
- **📱 Real-Time Updates** - WebSocket broadcasting for live updates
- **📥 Data Import System** - PostgreSQL database connectivity with FreeScout support

## Technology Stack

- **Backend**: Laravel 12 + PostgreSQL + Redis
- **Frontend**: Vue.js 3.5 + Inertia.js + TanStack Query + Tailwind CSS
- **Real-Time**: Laravel Reverb WebSocket server
- **Auth**: Laravel Sanctum (sessions + API tokens)

## Recent Updates (August 2025)

- **Enhanced Import System**: Filter-based import controls with date ranges, status filters, and record limits
- **UUID Architecture**: Complete migration to UUID primary keys for import system tables
- **Relationship Resolution**: Import preview now shows resolved status names and user relationships
- **Import System Fixes**: Resolved route model binding issues and deletion functionality
- **Timer System Fixes**: Fixed timer creation/editing - ticket_id and account_id now save properly
- **Migration Stability**: Fixed duplicate migration errors with existence checks and UUID conversions

---

*Service Vault - B2B Service Management Platform | Last Updated: August 20, 2025*