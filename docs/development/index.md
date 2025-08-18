# Development Documentation

Development standards, workflows, and tools for Service Vault.

## Current Status
- **[Progress Report](progress-report.md)** - Phase 13A complete with three-dimensional permissions ✅ CURRENT

## Quick Start

### Development Environment
```bash
# Clone and setup
git clone <repository>
composer install
npm install
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Development servers (start manually)
php artisan serve           # Laravel backend (http://localhost:8000)
npm run dev                 # Vite frontend (hot reload)
```

## Core Standards

### 1. Laravel CLI-First Development
- **[Standards](standards.md)** - CLI-first approach, naming conventions ✅ CURRENT
- **[Migration Generation](migration-generation.md)** - Database schema generation strategy ✅ CURRENT
- **[Server Management](server-management.md)** - Development server commands ✅ CURRENT

### 2. Code Quality Standards
```bash
# Required model generation
php artisan make:model ModelName -mfs

# API controller with model binding
php artisan make:controller Api/ModelController --api --model=Model

# Complete authorization setup
php artisan make:policy ModelPolicy --model=Model
```

## Development Tools

### Debugging & Testing
- **[Debugging](debugging.md)** - Xdebug configuration and VS Code setup ✅ CURRENT
- **[Troubleshooting](troubleshooting.md)** - Common issues and solutions including CSRF token management ✅ CURRENT
- **Testing**: PHPUnit configured with database testing
- **Code Style**: PSR-12 standards with PHP CS Fixer

### Database Development
- **UUID Primary Keys**: All models use UUIDs for security
- **Soft Deletes**: Audit trail preservation
- **Factories & Seeders**: Test data generation
- **Migration Ordering**: Proper dependency management

## Architecture Integration

### Three-Dimensional Permissions
Development must integrate with permission system:
```php
// Controller authorization
$this->authorize('permission.name');

// Middleware usage
Route::middleware(['auth', 'check_permission:admin.read']);

// Policy integration
public function view(User $user, Model $model): bool
{
    return $user->hasPermission('resource.view');
}
```

### API Development Patterns
- **RESTful Structure**: Consistent endpoint patterns
- **Resource Classes**: Standardized JSON responses
- **Form Requests**: Input validation and authorization
- **Laravel Sanctum**: Token authentication with abilities

### Frontend Development
- **Vue.js 3.5**: Composition API with TypeScript support
- **Inertia.js**: SPA-style navigation with server-side routing
- **Widget System**: Permission-driven dashboard components
- **Real-Time**: Laravel Echo + WebSocket integration

## Development Workflow

### 1. Feature Development
```bash
# 1. Create feature branch
git checkout -b feature/new-feature

# 2. Generate complete model structure
php artisan make:model Feature -mfs
php artisan make:controller Api/FeatureController --api --model=Feature
php artisan make:policy FeaturePolicy --model=Feature

# 3. Implement feature with tests
php artisan make:request StoreFeatureRequest
php artisan make:resource FeatureResource

# 4. Run tests and checks
php artisan test
npm run build
```

### 2. Database Changes
```bash
# New models
php artisan make:model NewModel -mfs

# Existing table modifications  
php artisan make:migration add_fields_to_table --table=existing_table

# Pivot tables
php artisan make:migration create_model_a_model_b_table --create=model_a_model_b
```

### 3. API Development
```bash
# Generate complete API structure
php artisan make:controller Api/ResourceController --api --model=Resource
php artisan make:request StoreResourceRequest
php artisan make:request UpdateResourceRequest
php artisan make:resource ResourceResource
php artisan make:policy ResourcePolicy --model=Resource
```

## Quality Assurance

### Code Standards Checklist
- [ ] Model generated with `-mfs` flags
- [ ] API controller with `--api --model` flags
- [ ] Policy created for authorization
- [ ] Form requests for validation
- [ ] API resources for response formatting
- [ ] Tests written for new functionality
- [ ] Permission integration implemented

### Testing Requirements
- **Unit Tests**: Model logic, service classes
- **Feature Tests**: API endpoints, authentication
- **Browser Tests**: Critical user workflows
- **Database Tests**: Migration rollbacks, seeding

### Performance Standards
- **Database**: Proper indexing, eager loading
- **Caching**: Redis for permissions, widget data
- **Frontend**: Optimized builds, lazy loading
- **API**: Pagination, resource optimization

## Deployment

### Environment Configuration
- **Development**: `.env` with debug enabled
- **Staging**: Production-like with debugging
- **Production**: Optimized configuration

### Build Process
```bash
# Frontend production build
npm run build

# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Database migrations
php artisan migrate --force
```

## Documentation Requirements

When adding features, update:
1. **API Documentation**: Endpoint specifications
2. **Architecture Docs**: System design changes
3. **Feature Docs**: User-facing functionality
4. **CLAUDE.md**: Development context updates

## Key Development Principles

1. **Laravel Standards**: Follow framework conventions
2. **CLI-First**: Use artisan commands for consistency
3. **Permission Integration**: All features must respect permissions
4. **API-First**: Design APIs before UI implementation
5. **Test Coverage**: Write tests for all new functionality
6. **Documentation**: Keep docs current with implementation