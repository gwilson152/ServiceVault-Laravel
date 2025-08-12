# API Documentation

RESTful API specifications, authentication, and integration guides.

## API Overview
- **[Authentication](authentication.md)** - Laravel Sanctum, session + token auth ✅ CURRENT
- **[Token Management](tokens.md)** - API token CRUD with granular abilities ✅ NEW

## Core Resources
- **[Accounts API](accounts.md)** - Hierarchical business account management ✅ CURRENT
- **[Users API](users.md)** - User management with account/role assignment ✅ CURRENT
- **[Roles & Permissions API](roles-permissions.md)** - Three-dimensional permission system ✅ CURRENT
- **[Timers API](timers.md)** - Multi-timer system with cross-device sync ✅ COMPACTED
- **[Time Entries API](time-entries.md)** - Time tracking with approval workflows ✅ NEW
- **[Tickets API](tickets.md)** - Service ticket workflow and management ✅ NEW
- **[Widgets API](widgets.md)** - Widget registry and dashboard APIs ✅ CURRENT

## Billing & Invoicing
*Documentation pending - APIs exist but need documentation*

## Administration
- **[Domain Mappings API](domain-mappings.md)** - Email domain to account assignment ✅ NEW
- **[User Invitations API](user-invitations.md)** - User invitation system
- **[Navigation API](navigation.md)** - Dynamic navigation and breadcrumbs
- **[Token Management API](tokens.md)** - API token CRUD and abilities
- **[Settings API](settings.md)** - System settings including email configuration ✅ NEW

## Real-Time Integration
Service Vault uses Laravel Echo + WebSocket for real-time updates:
- Timer state broadcasting on `user.{userId}` channels
- Ticket updates and notifications
- Cross-device timer synchronization

See individual API documentation for WebSocket event details.