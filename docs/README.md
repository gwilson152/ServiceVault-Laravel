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
- **[Tickets & Billing](guides/tickets-billing.md)** - Service tickets, billing, and Time & Addons management
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
- **📥 Universal Import System** - PostgreSQL database connectivity with template-based configuration and visual query builder

## Technology Stack

- **Backend**: Laravel 12 + PostgreSQL + Redis
- **Frontend**: Vue.js 3.5 + Inertia.js + TanStack Query + Tailwind CSS
- **Real-Time**: Laravel Reverb WebSocket server
- **Auth**: Laravel Sanctum (sessions + API tokens)

## Recent Updates (August 2025)

### Tax Management System (Latest)
- **Complete Tax Configuration**: System-wide tax settings configurable during setup and in settings
- **3-State Taxable Controls**: Per-line-item tax override (taxable/not taxable/inherit) with visual indicators
- **Tax Application Modes**: All items, products only, or custom per-item taxation
- **Time Entry Tax Settings**: Configurable default taxability for time entries vs products
- **Tax Inheritance**: Hierarchical tax calculation (invoice → account → system settings)
- **API Integration**: Complete tax management via REST API with validation and calculation logic

### Invoice Line Item Management
- **Drag & Drop Reordering**: Visual reordering of invoice line items with backend persistence
- **Separator Line Items**: Add section headers/separators to organize invoice items
- **Enhanced Tax Display**: Individual line item tax amounts and totals with clear status indicators

### Universal Import System Enhancements  
- **Template-Based Configuration**: Pre-built platform templates (FreeScout, Custom) with automatic query generation
- **Visual Query Builder**: Drag-and-drop interface with TableSelector, JoinBuilder, FieldMapper, and FilterBuilder components
- **Database-Agnostic Architecture**: Supports any PostgreSQL database with intelligent schema introspection
- **Advanced JOIN Support**: Visual configuration of complex multi-table relationships with suggested joins
- **Real-Time Preview**: Live data preview with field mapping validation and transformation testing
- **Simplified Workflow**: Separated connection creation from template configuration for improved user experience

---

*Service Vault - B2B Service Management Platform | Last Updated: August 21, 2025*