# Migration Generation Guide

## Service Vault Migration Strategy

This document outlines the systematic approach to generating all Service Vault database migrations using Laravel 12 CLI tools.

## Core Entities

### 1. Account Management
```bash
php artisan make:model Account -mfs
php artisan make:model Organization -mfs  # Parent organizations
```

### 2. User Management
```bash
# Users table exists, modify it
php artisan make:migration add_service_vault_fields_to_users_table --table=users
```

### 3. Time Tracking
```bash
php artisan make:model Timer -mfs
php artisan make:model TimeEntry -mfs
php artisan make:model Project -mfs
php artisan make:model Task -mfs
php artisan make:model Category -mfs
```

### 4. Billing & Invoicing
```bash
php artisan make:model BillingRate -mfs
php artisan make:model Invoice -mfs
php artisan make:model InvoiceItem -mfs
php artisan make:model Payment -mfs
```

### 5. Permission System
```bash
php artisan make:model Role -mfs
php artisan make:model Permission -mfs
php artisan make:model RoleTemplate -mfs
```

### 6. Custom Fields & Settings
```bash
php artisan make:model CustomField -mfs
php artisan make:model Setting -mfs
php artisan make:model Theme -mfs
```

## Pivot Tables (Many-to-Many Relationships)

```bash
# User-Account relationships
php artisan make:migration create_account_user_table --create=account_user

# Role-Permission relationships  
php artisan make:migration create_permission_role_table --create=permission_role

# User-Role relationships (per account)
php artisan make:migration create_role_user_table --create=role_user

# Project-User assignments
php artisan make:migration create_project_user_table --create=project_user

# Timer-Project relationships
php artisan make:migration create_project_timer_table --create=project_timer
```

## System Tables

```bash
php artisan make:model ActivityLog -mfs      # Audit trail
php artisan make:model Notification -mfs     # User notifications
php artisan make:model ApiToken -mfs         # API access tokens
php artisan make:model WebhookCall -mfs      # Webhook logging
```

## Generation Order

Execute in this order to handle foreign key dependencies:

1. **Core Entities First**
   - Account, Organization
   - Modified Users table
   - Role, Permission, RoleTemplate

2. **Business Entities**
   - Project, Task, Category
   - CustomField, Setting, Theme

3. **Time Tracking**
   - Timer, TimeEntry

4. **Billing System**
   - BillingRate, Invoice, InvoiceItem, Payment

5. **Pivot Tables**
   - All relationship tables

6. **System Tables**
   - ActivityLog, Notification, ApiToken, WebhookCall

## Clean Slate Regeneration

To completely regenerate all migrations:

```bash
# 1. Remove existing migrations (except Laravel defaults)
rm database/migrations/2025_*

# 2. Reset database
php artisan migrate:reset

# 3. Generate all entities using the order above
# ... (execute all commands in order)

# 4. Run fresh migrations
php artisan migrate:fresh --seed
```

## Verification

After generation, verify:

1. All models have corresponding migrations
2. All migrations have proper foreign key constraints
3. Pivot tables are correctly named (alphabetical order)
4. Indexes are properly defined
5. No circular dependencies in foreign keys

## File Tracking

Generated files for each entity:
- `app/Models/EntityName.php`
- `database/migrations/[timestamp]_create_entity_names_table.php`
- `database/factories/EntityNameFactory.php`
- `database/seeders/EntityNameSeeder.php`

This systematic approach ensures a complete, consistent, and properly structured database schema for Service Vault.

## Migration Troubleshooting

### Common Issues and Solutions

#### Duplicate Table Errors
**Error**: `SQLSTATE[42P07]: Duplicate table: 7 ERROR: relation "table_name" already exists`

**Solution**: Add existence check to migration:
```php
public function up(): void
{
    // Check if table already exists before creating
    if (Schema::hasTable('table_name')) {
        return;
    }
    
    Schema::create('table_name', function (Blueprint $table) {
        // table definition
    });
}
```

#### Migration State Inconsistencies
**Problem**: Migration shows as run but table doesn't exist, or vice versa.

**Solution**: 
```bash
# Check migration status
php artisan migrate:status

# Roll back specific migration
php artisan migrate:rollback --step=1

# Re-run migrations
php artisan migrate
```

#### Field Validation Errors
**Problem**: API requests failing due to missing validation rules for database fields.

**Solution**: Update corresponding Request classes:
```php
// Example: StoreTimerRequest.php
public function rules(): array
{
    return [
        'ticket_id' => 'nullable|exists:tickets,id',
        'account_id' => 'nullable|exists:accounts,id',
        'user_id' => 'nullable|exists:users,id',
        // ... other rules
    ];
}
```

#### Currency Field Removal
If removing currency fields from existing tables:
```bash
# Create migration to drop currency columns
php artisan make:migration remove_currency_from_billing_rates_table --table=billing_rates

# In migration file:
public function up()
{
    Schema::table('billing_rates', function (Blueprint $table) {
        if (Schema::hasColumn('billing_rates', 'currency')) {
            $table->dropColumn('currency');
        }
    });
}
```

### Best Practices for Stable Migrations

1. **Always check for existence** before creating/dropping structures
2. **Use reversible operations** in down() methods
3. **Update Request validation** when adding new fields
4. **Test migrations** on fresh database before deploying
5. **Keep migrations atomic** - one logical change per migration
6. **Use descriptive names** that indicate the purpose of the change

### Emergency Recovery

If migrations fail in production:
```bash
# Mark specific migration as run without executing
php artisan migrate:fake [migration_name]

# Force run specific migration
php artisan migrate --force

# Nuclear option - fresh install (DESTRUCTIVE)
php artisan system:nuclear-reset --user-id=1
```