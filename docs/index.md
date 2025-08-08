# Service Vault Documentation

A comprehensive time management and invoicing system built with Laravel 12, featuring multi-role dashboards, ABAC permissions, real-time timer synchronization, and enterprise theming.

## System Overview

**Service Vault** is an enterprise-grade time management and invoicing platform with:

### Core Features

-   **Multi-Role Dashboard System**: Admin, Employee, Manager, and Customer Portal interfaces
-   **ABAC Permission System**: Role templates with hierarchical inheritance (no hard-coded roles)
-   **Real-time Timer Synchronization**: Cross-device timer state with WebSocket updates
-   **Hierarchical Account Management**: Unlimited depth organizational structures
-   **Domain-based User Assignment**: Automatic account assignment via email domains
-   **Enterprise Theming**: Multi-tenant branding with CSS custom properties
-   **AccountSelector Component**: Critical hierarchical selection for domain mapping
-   **Comprehensive Billing**: Two-tier rate structure with account overrides

### Technology Stack

-   **Backend**: Laravel 12 with PHP 8.2+
-   **Database**: PostgreSQL 15+ (primary), MySQL (alternative)
-   **Frontend**: Inertia.js + Vue.js 3.5+ with Composition API
-   **UI Framework**: Headless UI + Tailwind CSS (accessibility-first)
-   **Authentication**: Laravel Sanctum with multi-tenant support
-   **Real-time**: Laravel Echo with Pusher/Socket.io
-   **Caching**: Redis for sessions, permissions, timer state
-   **Testing**: PHPUnit + Laravel Dusk for browser testing

### Dashboard Architecture

#### Admin Dashboard (`/dashboard/admin`)

-   **System Management**: User management, role template configuration
-   **Account Hierarchy**: Create and manage organizational structures
-   **Permission Administration**: ABAC role template management (super-admin)
-   **Billing Configuration**: System-wide and account-specific rate management
-   **Data Import/Export**: Comprehensive import system with field mapping
-   **System Settings**: Global configuration and theme management

#### Employee Dashboard (`/dashboard/employee`)

-   **Time Tracking**: Advanced timer with billing rate selection and real-time sync
-   **Ticket Management**: Create, update, and resolve assigned tickets
-   **Personal Analytics**: Time summaries, productivity metrics
-   **Account Access**: View accounts based on permissions and assignments
-   **Timer Controls**: Cross-device synchronization with pause/resume

#### Manager Dashboard (`/dashboard/manager`)

-   **Team Oversight**: View and approve team time entries
-   **Project Management**: Assign tickets and manage project workflows
-   **Rate Management**: Configure project-specific billing rates
-   **Team Analytics**: Productivity reports and project status dashboards
-   **Approval Workflows**: Multi-stage time entry approval system

#### Customer Portal (`/portal`)

-   **Account-Scoped Access**: Limited view of own account data only
-   **Ticket Viewing**: Progress tracking with real-time status updates
-   **Time Visibility**: View time spent and associated costs (if permitted)
-   **Invoice Access**: Download invoices and payment history (if permitted)
-   **Account Theming**: Branded experience with account-specific themes

## Quick Start

### Prerequisites

-   PHP 8.2+ with extensions (pdo_pgsql, redis, etc.)
-   PostgreSQL 15+ (postgres1.drivenw.local configured)
-   Node.js LTS with npm (via NVM recommended)
-   Composer 2.x
-   Redis for caching and sessions
-   VS Code with PHP/Vue.js extensions (recommended)

### Setup

```bash
# Clone and setup (automated script)
./setup.sh

# Start development servers
php artisan serve --host=0.0.0.0 --port=8000   # Laravel backend
npm run dev                                      # Vite frontend

# Database operations
php artisan migrate:fresh --seed                # Reset with test data
php artisan test                                # Run test suite
```

### Development Tasks

See [`todos.md`](todos.md) for complete implementation roadmap.

**Current Phase**: API Controllers & Backend Logic (60% Complete)  
**Next Priority**: AccountSelector Component → Domain Mapping Feature

### Recent Achievements

-   ✅ **ABAC Permission System**: Complete with PermissionService, Gates, and middleware
-   ✅ **Laravel CLI-First Development**: Following proper Laravel standards
-   ✅ **AccountController**: With hierarchical selector support for domain mapping
-   ✅ **Authorization Policies**: AccountPolicy, TimerPolicy, TimeEntryPolicy implemented

## Documentation Structure

### [Development](development/)

Standards, tools, and workflows for Service Vault development.

-   Development standards and Laravel CLI approach
-   VS Code setup, debugging, and testing
-   Database development and migration strategies
-   Frontend development with Vue.js and theming

### [Architecture](architecture/)

System architecture, database design, and technical specifications.

-   System overview and technology stack
-   Database schema and hierarchical data models
-   Core systems: authentication, timers, billing, themes
-   Security, performance, and scalability considerations

### [Features](features/)

Functional specifications and user guides for Service Vault features.

-   Core features: accounts, users, time tracking, projects
-   Advanced features: billing, permissions, custom fields
-   Timer system with cross-device synchronization
-   Reporting, analytics, and administrative features

### [API](api/)

RESTful API specifications, authentication, and integration guides.

-   API overview, authentication, and rate limiting
-   Core resource endpoints for all entities
-   Real-time WebSocket events and synchronization
-   Integration guides and testing documentation

### [Deployment](deployment/)

Production deployment, server configuration, and infrastructure.

-   Production requirements and environment setup
-   Server configuration: nginx, PHP-FPM, Redis, SSL
-   Deployment processes and CI/CD pipelines
-   Infrastructure, monitoring, and maintenance

### Legacy Documentation

-   **[Previous Docs](../docs.old/)** - Original Next.js documentation (reference only)

## Technology Stack

### Backend

-   **Laravel 12** - PHP framework with PostgreSQL
-   **Laravel Sanctum** - API authentication
-   **Redis** - Caching, sessions, queues
-   **Laravel Echo** - Real-time broadcasting

### Frontend

-   **Inertia.js** - SPA without API complexity
-   **Vue.js 3.5+** - Progressive JavaScript framework
-   **Headless UI** - Accessible UI components
-   **Tailwind CSS** - Utility-first styling with custom themes

### Database

-   **PostgreSQL** - Primary database
-   **Redis** - Cache and session storage
-   **Nested Set Model** - Hierarchical accounts

## Development Workflow

### Standard Commands

```bash
# Laravel development
php artisan serve              # Start dev server
php artisan migrate           # Run migrations
php artisan db:seed           # Seed database
php artisan test              # Run tests

# Frontend development
npm run dev                   # Start Vite dev server
npm run build                 # Build for production

# Database management
php artisan migrate:fresh --seed  # Reset database
php artisan tinker            # Interactive PHP shell
```

### VS Code Integration

-   **Debug Configurations** - Laravel serve, migration, testing
-   **Extensions** - PHP debugging, Laravel IntelliSense, Vue support
-   **Tasks** - Integrated build and test tasks
-   **Database GUI** - PostgreSQL connection configured

## Key Features

### Hierarchical Accounts

-   Unlimited depth organization structure
-   Domain-based user assignment
-   Inherited permissions and settings
-   Custom branding per account

### Advanced Timer System

-   Real-time cross-device synchronization
-   Multiple timer support
-   Automatic conflict resolution
-   Background time tracking

### ABAC Permission System

-   Attribute-based access control
-   Role templates with inheritance
-   Account-scoped permissions
-   Fine-grained authorization

### Comprehensive Billing

-   Two-tier rate structure
-   Historical rate snapshotting
-   Automated invoice generation
-   Multi-currency support

### Enterprise Theming

-   CSS custom properties
-   Account-level branding
-   User preference overrides
-   Multi-tenant theme support

## Project Structure

```
service-vault/
├── app/
│   ├── Http/Controllers/Api/    # RESTful API controllers
│   ├── Models/                  # Eloquent models
│   ├── Policies/               # Authorization policies
│   └── Services/               # Business logic
├── database/
│   ├── migrations/             # Database schema
│   ├── factories/              # Test data factories
│   └── seeders/               # Database seeders
├── resources/
│   ├── js/                    # Vue.js frontend
│   └── css/                   # Theme-aware styles
├── docs/                      # Documentation
└── tests/                     # Feature and unit tests
```

## Contributing

### Code Standards

-   **Laravel CLI-first** - Use `php artisan make:*` commands
-   **Model + Migration** - Always generate with `-mfs` flags
-   **API Resources** - Consistent JSON responses
-   **Policies** - Authorization for every model
-   **Tests** - Feature and unit test coverage

### Git Workflow

-   Feature branches from `main`
-   Descriptive commit messages
-   Pull request reviews required
-   Automated testing on PRs

## Deployment

### Production Requirements

-   PHP 8.2+ with OPcache
-   PostgreSQL 15+ with connection pooling
-   Redis for caching and sessions
-   Nginx with SSL termination
-   Queue workers with Supervisor

### Environment Configuration

-   Multi-environment `.env` files
-   Database connection pooling
-   Redis clustering for scale
-   CDN for static assets

## Support

### Development Help

-   Check existing documentation first
-   Use VS Code debugging tools
-   Laravel Tinker for testing
-   Review test cases for examples

### Issue Reporting

-   Use descriptive titles
-   Include error logs and stack traces
-   Provide reproduction steps
-   Tag with appropriate labels

---

**Service Vault** - Professional time tracking and invoicing for modern teams.
