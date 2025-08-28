# Setup Guide

Complete installation and configuration guide for Service Vault.

## System Requirements

- **PHP**: 8.2+ with extensions: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- **Database**: PostgreSQL 14+ (recommended) or MySQL 8.0+
- **Cache**: Redis 6.0+ (required for timers and sessions)
- **Node.js**: 18+ (for frontend build process)

## Installation

### 1. Clone and Setup

```bash
git clone <repository-url> servicevault
cd servicevault
composer install --optimize-autoloader
npm install
```

### 2. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup (Consolidated Migration System)

```bash
# Configure database in .env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=servicevault
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Fresh deployment - creates complete schema
php artisan migrate:fresh --seed
```

**✅ Clean Migration Architecture (August 2025)**:

Service Vault implements a **consolidated migration system** with no migration history baggage:

- **8 Comprehensive Migrations**: Replaced 87+ fragmented migrations with logical, comprehensive files
- **Fresh Deployments**: `migrate:fresh` creates complete schema without incremental changes
- **FreeScout Import Ready**: Built-in composite unique constraint `(email, user_type)` allows duplicate emails across user types
- **PostgreSQL Optimized**: UUID primary keys, external IDs for import system, and PostgreSQL-specific optimizations
- **Production Ready**: Enterprise-grade schema suitable for immediate deployment

**Migration Files**:
```
├── 0001_01_01_000003_create_core_user_and_account_management.php
├── 0001_01_01_000004_create_permission_and_role_management.php  
├── 0001_01_01_000005_create_ticket_and_service_management.php
├── 0001_01_01_000006_create_timer_and_time_entry_system.php
├── 0001_01_01_000007_create_billing_and_invoice_system.php
├── 0001_01_01_000008_create_universal_import_system.php
├── 0001_01_01_000009_create_email_management_system.php
└── 0001_01_01_000010_create_system_configuration_and_utilities.php
```

**For Production**: Skip the `--seed` flag and use the setup wizard instead:
```bash
php artisan migrate:fresh
```

### 4. Initial Setup Wizard

After database setup, navigate to your application URL to access the setup wizard at `/setup`:

**Company Information**:
- Company Name, Email, Website, Phone
- Company Address
- Contact details for invoicing

**System Configuration**:
- Timezone selection
- Currency (USD, EUR, GBP, CAD, AUD)
- Date and time formats
- Language preference
- Maximum users limit

**Tax Configuration** (New):
- **Enable Tax System**: Turn tax calculations on/off
- **Default Tax Rate**: System-wide tax percentage (default: 6%)
- **Tax Application Mode**: Choose how taxes apply:
  - `All Taxable Items`: Tax both time entries and addons
  - `Products Only (No Services)`: Tax only addons, not time entries (default)
  - `Custom (Per Item)`: Tax determined per line item
- **Time Entries Taxable by Default**: When "All Items" mode is selected (default: false)

**Administrator Account**:
- Admin name, email, and password
- Super Admin role with all permissions

**Advanced Settings**:
- Timer sync interval
- Permission cache TTL
- Real-time features enabled

### 5. Redis Configuration

```bash
# Configure Redis in .env
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Reverb WebSocket Setup (Optional)

For real-time features:

```bash
# Configure broadcasting in .env
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Start Reverb server (separate terminal)
php artisan reverb:start
```

## Development Servers

**Important**: Start these manually - they are not started automatically.

```bash
# Laravel application server
php artisan serve --host=0.0.0.0 --port=8000

# Frontend development server (separate terminal)
npm run dev

# WebSocket server for real-time features (separate terminal)
php artisan reverb:start
```

## Initial Setup Wizard

1. Visit `http://localhost:8000`
2. Follow the setup wizard to:
   - Create super admin account
   - Configure basic settings
   - Set up initial accounts and users

## Production Deployment

### Build Assets

```bash
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Queue Worker (Recommended)

```bash
php artisan queue:work --daemon
```

### Supervisor Configuration

```ini
[program:servicevault-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/servicevault/artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/servicevault/storage/logs/worker.log
```

## Troubleshooting

### Common Issues

**Migration Errors**: See [Technical Guide](../technical/development.md#migration-troubleshooting) for migration fixes.

**Permission Issues**: Ensure storage and bootstrap/cache directories are writable:
```bash
chmod -R 775 storage bootstrap/cache
```

**Redis Connection**: Verify Redis is running and accessible:
```bash
redis-cli ping
```

### System Reset (DESTRUCTIVE)

For complete system reset in development:
```bash
php artisan system:nuclear-reset --user-id=1
```

**⚠️ WARNING**: This destroys ALL data permanently.