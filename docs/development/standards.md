# Service Vault Development Standards

## Overview

Service Vault follows Laravel 12 best practices with a **CLI-first approach** for consistency, maintainability, and adherence to framework standards.

## ✅ Implemented Standards (Current Status)

### ABAC Permission System ✅ COMPLETE

-   **PermissionService**: Comprehensive ABAC logic with caching
-   **Laravel Gates**: System and account-level permissions in AppServiceProvider
-   **CheckPermission Middleware**: Route-level authorization
-   **Model Policies**: AccountPolicy, TimerPolicy, TimeEntryPolicy

### API Development ✅ PARTIAL

-   **AccountController**: Complete with hierarchical selector support
-   **API Resources**: AccountResource for consistent JSON responses
-   **Form Requests**: StoreAccountRequest, UpdateAccountRequest
-   **Authorization**: Integrated with ABAC permission system

## Standard Development Workflow

### 1. Model Generation (REQUIRED)

Always use the complete model generation command:

```bash
php artisan make:model ModelName -mfs
```

This generates:

-   **Model** (`app/Models/ModelName.php`)
-   **Migration** (`database/migrations/[timestamp]_create_[table]_table.php`)
-   **Factory** (`database/factories/ModelNameFactory.php`)
-   **Seeder** (`database/seeders/ModelNameSeeder.php`)

✅ **Status**: All core models generated (Account, Timer, TimeEntry, Project, etc.)

### 2. API Controller Generation

Generate API controllers with model binding:

```bash
php artisan make:controller Api/ModelNameController --api --model=ModelName
```

This creates RESTful API endpoints with proper model binding.

✅ **Status**: AccountController complete, TimerController/TimeEntryController/ProjectController generated

### 3. Authorization Setup

Generate policies for each model:

```bash
php artisan make:policy ModelNamePolicy --model=ModelName
```

✅ **Status**: AccountPolicy, TimerPolicy, TimeEntryPolicy generated and integrated

### 4. Validation Setup

Generate form requests for validation:

```bash
php artisan make:request StoreModelNameRequest
php artisan make:request UpdateModelNameRequest
```

✅ **Status**: Account requests complete, others pending

### 5. API Response Formatting

Generate API resources for consistent responses:

```bash
php artisan make:resource ModelNameResource
php artisan make:resource ModelNameCollection
```

## Migration Standards

### Table Creation

Use the model generation command instead of standalone migrations:

```bash
# ✅ Correct
php artisan make:model Account -mfs

# ❌ Avoid
php artisan make:migration create_accounts_table
```

### Table Modifications

For existing tables:

```bash
php artisan make:migration add_fields_to_users_table --table=users
```

### Pivot Tables

For many-to-many relationships:

```bash
php artisan make:migration create_account_user_table --create=account_user
```

## Naming Conventions

### Models

-   Singular, PascalCase: `Account`, `TimeEntry`, `BillingRate`
-   File location: `app/Models/`

### Controllers

-   PascalCase with Controller suffix: `AccountController`
-   API controllers: `Api/AccountController`
-   File location: `app/Http/Controllers/` or `app/Http/Controllers/Api/`

### Migrations

-   Snake_case with descriptive action: `create_accounts_table`, `add_fields_to_users_table`
-   File location: `database/migrations/`

### Policies

-   PascalCase with Policy suffix: `AccountPolicy`
-   File location: `app/Policies/`

### Requests

-   PascalCase with action prefix: `StoreAccountRequest`, `UpdateAccountRequest`
-   File location: `app/Http/Requests/`

### Resources

-   PascalCase with Resource suffix: `AccountResource`, `AccountCollection`
-   File location: `app/Http/Resources/`

## File Structure Standards

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Api/              # API controllers
│   │   ├── Dashboard/        # Admin dashboard controllers
│   │   └── Portal/           # Customer portal controllers
│   ├── Requests/             # Form validation classes
│   ├── Resources/            # API response resources
│   └── Middleware/           # Custom middleware
├── Models/                   # Eloquent models
├── Policies/                 # Authorization policies
├── Services/                 # Business logic services
└── Repositories/             # Data access layer (if needed)

database/
├── factories/                # Model factories for testing
├── migrations/               # Database migrations
└── seeders/                  # Database seeders

tests/
├── Feature/                  # Integration tests
└── Unit/                     # Unit tests
```

## Benefits of This Approach

1. **Consistency**: All team members follow the same patterns
2. **Completeness**: No missing factory, seeder, or policy files
3. **Laravel Standard**: Follows official Laravel conventions
4. **IDE Support**: Better autocompletion and navigation
5. **Testing Ready**: Factories and seeders generated automatically
6. **Security**: Policies generated for authorization from the start

## Quality Checklist

Before committing code, ensure:

-   [ ] Model generated with `-mfs` flags
-   [ ] API controller generated with `--api --model` flags
-   [ ] Policy created for authorization
-   [ ] Form requests created for validation
-   [ ] API resources created for response formatting
-   [ ] Migration follows naming conventions
-   [ ] All relationships properly defined in models
-   [ ] Factory provides realistic test data
-   [ ] Seeder creates necessary initial data

## Examples

### Complete Account Entity Setup

```bash
# 1. Generate model structure
php artisan make:model Account -mfs

# 2. Generate API controller
php artisan make:controller Api/AccountController --api --model=Account

# 3. Generate policy
php artisan make:policy AccountPolicy --model=Account

# 4. Generate form requests
php artisan make:request StoreAccountRequest
php artisan make:request UpdateAccountRequest

# 5. Generate API resources
php artisan make:resource AccountResource
php artisan make:resource AccountCollection
```

This creates a complete, consistent, and maintainable entity structure following Laravel best practices.
