# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Service Vault is a comprehensive time management and invoicing system built with Laravel 12. It features hierarchical account management, advanced timer synchronization, ABAC permission system, comprehensive billing/invoicing capabilities, and enterprise-level theming for multi-tenant usage.

### Current Status: Phase 8/15 Complete (53% MVP Ready)
**✅ Working Features:**
- App-wide timer overlay with full control suite
- Real-time duration tracking and billing calculations  
- Cross-device timer synchronization via Redis
- Modern Vue.js frontend with responsive design
- Comprehensive backend API with ABAC authorization
- **Complete authentication system** with Laravel Breeze + Inertia.js
- **ABAC permission system** with role templates and hierarchical inheritance
- **User management** with automatic role assignment and invitation system
- **Hybrid Sanctum authentication** - Session auth for web + Token auth for API
- **Token management system** with granular abilities and scoped permissions
- **API security** with token-based authorization and policy integration

**🎯 Next Priority:** Real-time broadcasting (Laravel Echo + WebSockets) and TimeEntry management workflows

## Documentation

All project documentation is centralized in `/docs/index.md`. Refer to the documentation index for:

- Development standards and workflows
- Architecture and database design
- Feature specifications
- API documentation
- Deployment guides

**Primary Reference**: [Documentation Index](docs/index.md)

## Development Server Policy

**IMPORTANT**: Never automatically start development servers without explicit user request:
- **DO NOT** run `php artisan serve` automatically
- **DO NOT** run `npm run dev` automatically  
- **DO NOT** run `npm run build --watch` automatically
- User will manually start/restart development servers when needed

### Documentation Maintenance Policy

**IMPORTANT**: Always update documentation when making code changes:

1. **Code Changes** → Update relevant documentation files
2. **New Features** → Add to `/docs/features/` 
3. **API Changes** → Update `/docs/api/` specifications
4. **Architecture Changes** → Update `/docs/architecture/`
5. **Development Process Changes** → Update `/docs/development/`

**Documentation Structure**:
```
docs/
├── index.md                    # Master index
├── development/                # Development guides
├── architecture/               # System architecture  
├── features/                   # Feature specifications
├── api/                        # API documentation
└── deployment/                 # Infrastructure guides
```

When creating new features or modifying existing ones, ensure documentation is updated in the same commit or pull request.

## Quick Start

```bash
# Development servers (DO NOT run these automatically - user will start them manually)
php artisan serve         # Start Laravel server (http://localhost:8000)
npm run dev              # Start Vite dev server (with HMR)

# Database operations  
php artisan migrate:fresh --seed  # Reset database with test data
php artisan migrate      # Run pending migrations only
php artisan db:seed      # Seed test data only

# Frontend development
npm run build           # Production build
npm run dev             # Development with hot reload

# API testing endpoints (Timer System)
# GET    /api/timers                     # List user timers
# POST   /api/timers                     # Start new timer  
# GET    /api/timers/active/current      # Get current running timer
# POST   /api/timers/{timer}/stop        # Stop timer
# POST   /api/timers/{timer}/pause       # Pause running timer
# POST   /api/timers/{timer}/resume      # Resume paused timer
# POST   /api/timers/{timer}/commit      # Stop and convert to time entry
# DELETE /api/timers/{timer}?force=true  # Force delete timer

# Standard Laravel CLI
php artisan make:model ModelName -mfs          # Model + migration/factory/seeder
php artisan make:controller Api/ModelController --api --model=Model  # API controller
php artisan make:policy ModelPolicy --model=Model  # Authorization policy

# Testing & debugging
php artisan test         # Run test suite
php artisan tinker       # Interactive shell
```

## Authentication Architecture

Service Vault implements a hybrid authentication system supporting both session-based web access and token-based API access:

### Session Authentication (Laravel Breeze)
- **Web Dashboard**: Login/register pages with session cookies
- **Inertia.js Integration**: CSRF-protected single-page application  
- **User Management**: Role assignment and ABAC permission integration

### Token Authentication (Laravel Sanctum)
- **API Access**: Bearer token authentication for mobile and external clients
- **Token Abilities**: Granular permissions system with scoped abilities:
  - `timers:read`, `timers:write`, `timers:delete`, `timers:sync`
  - `projects:read`, `projects:write`, `accounts:read`, `billing:read`
  - `admin:read`, `admin:write` (for administrative access)
- **Predefined Scopes**: Ready-to-use token scopes for common use cases:
  - `employee`: Basic timer and project access
  - `manager`: Team oversight and approval capabilities
  - `mobile-app`: Full mobile application functionality
  - `admin`: Complete administrative access

### API Token Management
- **Token CRUD**: Full REST API for creating, viewing, updating, and deleting tokens
- **Password Verification**: Security confirmation for token operations
- **Token Expiration**: Configurable expiration times for enhanced security
- **Scope-based Creation**: Create tokens with predefined ability sets

### Policy Integration
All Laravel policies (e.g., `TimerPolicy`) support both authentication methods:
```php
// Check token abilities if API authenticated
if ($user->currentAccessToken()) {
    return $user->tokenCan('timers:read');
}
// Default ABAC permissions for web users
return $user->hasPermission('timers.view');
```

## Development Standards

### Laravel CLI-First Approach
Service Vault follows Laravel best practices using CLI-first generation:

```bash
# Standard model generation
php artisan make:model Account -mfs          # Model + migration/factory/seeder
php artisan make:controller Api/AccountController --api --model=Account  # API controller
php artisan make:policy AccountPolicy --model=Account  # Authorization policy
php artisan make:request StoreAccountRequest          # Form validation
php artisan make:resource AccountResource             # API responses
```

### Architecture Principles
- **ABAC Permission System**: Role templates with hierarchical inheritance, no hard-coded roles
- **Database First**: Design schema, then build models and relationships
- **API-First Backend**: RESTful endpoints with consistent JSON responses
- **Component-Based Frontend**: Vue.js 3.5 + Inertia.js with Headless UI + Tailwind CSS
- **Enterprise Theming**: CSS custom properties for multi-tenant branding