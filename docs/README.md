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
- **[Email System](guides/email-system.md)** - Email configuration, processing, domain routing, and user management
- **[Users & Permissions](guides/users-permissions.md)** - Role management and permission system
- **[Import System](guides/import.md)** - PostgreSQL database import and data migration

### 🔧 [API Reference](api/)
- **[Authentication](api/auth.md)** - Login, tokens, and session management
- **[Core Resources](api/resources.md)** - Tickets, timers, users, accounts
- **[Email System](api/email-system.md)** - Email configuration, domain mappings, and monitoring
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
- **📧 Email System** - Complete email processing with domain routing, user management, and monitoring
- **📥 Universal Import System** - Advanced PostgreSQL database connectivity with real-time monitoring, duplicate detection, and comprehensive analytics

## Technology Stack

- **Backend**: Laravel 12 + PostgreSQL + Redis
- **Frontend**: Vue.js 3.5 + Inertia.js + TanStack Query + Tailwind CSS
- **Real-Time**: Laravel Reverb WebSocket server
- **Auth**: Laravel Sanctum (sessions + API tokens)

## Recent Updates (August 2025)

### Consolidated Database Migration System ✅
- **✅ Clean Migration Structure**: Consolidated 87+ fragmented migrations into 8 logical, comprehensive migration files
- **✅ Composite Unique Constraint Solution**: Fixed FreeScout import duplicate email issues with `(email, user_type)` composite constraint
- **✅ No Backward Compatibility**: Fresh database setup eliminates migration history baggage for clean deployments
- **✅ Integrated Schema**: All table modifications consolidated into table creation migrations with final structure
- **✅ Updated Models**: All Eloquent models updated to match consolidated schema with proper fillable fields and relationships
- **✅ Fixed Seeders**: All database seeders updated to work with consolidated table structures
- **✅ PostgreSQL Optimizations**: Leverages PostgreSQL-specific features (triggers, partial indexes, check constraints)
- **✅ Import System Ready**: Composite constraint allows same email for different user types (agent vs account_user) while preventing true duplicates

### Unified Email System (Production-Ready)
- **✅ Consolidated Email Management**: Single unified interface combining email configuration, user creation, and domain management
- **✅ Multi-Provider Email Support**: SMTP, IMAP, Gmail, Outlook, Microsoft 365/Exchange with intelligent provider defaults
- **✅ Microsoft 365 Integration**: Advanced folder hierarchy browser with real-time search and visual tree structure
- **✅ Domain-Based Email Routing**: Sophisticated pattern matching system for routing emails to business accounts
- **✅ Automated User Management**: Email-triggered user creation with domain mapping and approval workflows
- **✅ Real-Time Processing Monitor**: Live email activity feed with WebSocket updates, performance metrics, and queue status
- **✅ Comprehensive Admin Dashboard**: System health monitoring, processing statistics, and user creation analytics
- **✅ Advanced Email Template System**: Template management with variable substitution and multi-format support
- **✅ Subject Command Processing**: Email-based command execution with role-based permissions and validation
- **✅ Streamlined Settings Interface**: Removed separate user management tab - all functionality integrated into Email System
- **✅ Production-Ready Architecture**: Queue-based processing, retry mechanisms, comprehensive error handling, and audit logging

### Unified Import Preview Architecture
- **Single Preview Dialog**: Consolidated all import preview functionality into one intelligent component
- **Automatic Type Detection**: Smart detection of template-based vs. query-based imports with adaptive UI
- **Dedicated Progress Dialog**: Separate execution dialog with real-time progress tracking and professional feedback
- **Enhanced Error Handling**: Comprehensive error messages and fallback states for incomplete configurations
- **Consistent User Experience**: Identical preview flow for both dropdown menu "Preview Import Data" and query builder "Preview Import"
- **Eliminated Component Duplication**: Removed SimpleImportPreview and ImportPreviewModal in favor of unified QueryImportPreviewModal
- **Clean Architecture**: Proper separation of concerns between preview and execution responsibilities

### Standardized Layout System & Enhanced Navigation
- **StandardPageLayout Component**: New reusable layout system with configurable slots for header, filters, content, and sidebar
- **Enhanced TabNavigation Component**: Intelligent horizontal scrolling with smooth navigation buttons, mobile-responsive design, and clean scrollbar hiding
- **Improved Tab Switching**: Tickets detail page now uses client-side tab switching (no page reloads) while preserving URLs for bookmarking
- **Enhanced Multi-Select Filters**: Intelligent ticket filtering with defaults (excludes closed tickets) and multi-select for status/priority
- **User-Specific Filter Persistence**: Per-user filter preferences saved to localStorage with automatic restoration
- **Responsive Design Improvements**: Mobile-first layout with collapsible sidebars and optimized filter interfaces
- **Full-Width Desktop Experience**: Removed artificial width constraints for better space utilization on large screens
- **Component Architecture**: Reusable FilterSection, PageHeader, PageSidebar, MultiSelect, and TabNavigation components

### Addon Management & Approval System
- **Enhanced Approval Wizard**: Logical separation of time entries and addons with unified workflow
- **Type-Specific Selection Controls**: Granular selection for time entries and addons with visual separation
- **Improved Time Entry Dialog**: Fixed billing amount calculation when billing rate changes
- **Centralized Tax Management**: Removed per-addon tax rates in favor of invoice-level tax calculation
- **Tax Consistency**: Addon tax rates now determined at invoice/account/global level for consistent application
- **StackedDialog Improvements**: Fixed dropdown clipping issues in approval wizard modals

### Tax Management System
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

### Universal Import System (Production-Ready)  
- **✅ Visual Query Builder**: Fullscreen interface with table hover tooltips, real-time SQL preview, and intelligent filter validation
- **✅ Persistent Query Configurations**: Save custom queries to profiles with automatic restoration after page reload
- **✅ Centralized State Management**: Eliminated recursive loops through controlled mutations and centralized query store
- **✅ Visual Status Indicators**: Profile cards show green cog for custom queries, blue document for templates
- **✅ Advanced Record Management**: Create-only, update-only, and upsert modes with sophisticated duplicate detection
- **✅ Real-Time Monitoring**: WebSocket-based job tracking with floating progress monitor and live statistics
- **✅ Intelligent Duplicate Detection**: Configurable strategies (exact, fuzzy, case-insensitive) with confidence scoring
- **✅ Template-Based Configuration**: Pre-built platform templates (FreeScout, Custom) with automatic query generation
- **✅ Database-Agnostic Architecture**: Supports any PostgreSQL database with intelligent schema introspection
- **✅ Enterprise Audit Trails**: UUID-based tracking with complete lineage and rollback capabilities
- **✅ Bulk Operations**: Approve, retry, and delete operations with comprehensive record management
- **✅ Consistent Query Generation**: Unified SQL generation for validation and sample data with proper field aliases and filtering
- **✅ Comprehensive Analytics**: Import dashboard with trend analysis, performance metrics, and duplicate effectiveness

---

*Service Vault - B2B Service Management Platform | Last Updated: August 26, 2025*