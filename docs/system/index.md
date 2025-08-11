# System Documentation

System configuration, setup processes, and operational procedures for Service Vault.

## System Setup & Configuration

### Initial Setup
- **[Setup Wizard](setup-wizard.md)** - Complete initial system configuration ✅ CURRENT
- **[Authentication System](authentication-system.md)** - User authentication and authorization ✅ CURRENT

### System Requirements
- **PHP 8.2+**: Laravel 12 compatibility
- **PostgreSQL 13+**: Primary database with UUID support
- **Redis 6+**: Caching, sessions, and real-time features
- **Node.js 18+**: Frontend build tools and development
- **Composer 2.x**: PHP dependency management
- **NPM/Yarn**: Frontend package management

### Environment Configuration
```bash
# Core Laravel Configuration
APP_NAME="Service Vault"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://servicevault.example.com

# Database Configuration
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=servicevault
DB_USERNAME=servicevault
DB_PASSWORD=secure_password

# Redis Configuration
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Session & Cache
SESSION_DRIVER=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Broadcasting (WebSocket)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=mt1

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

## System Architecture

### Core Components
1. **Laravel 12**: PHP framework backbone
2. **PostgreSQL**: Primary data storage with UUID keys
3. **Redis**: Caching, sessions, and real-time state management
4. **Vue.js 3.5**: Frontend framework with Composition API
5. **Inertia.js**: SPA navigation with server-side routing
6. **Laravel Echo**: WebSocket broadcasting for real-time features

### Security Framework
- **Three-Dimensional Permissions**: Functional + Widget + Page access control
- **Laravel Sanctum**: Hybrid session + API token authentication
- **UUID Primary Keys**: Enhanced security and distribution readiness
- **Input Validation**: Comprehensive form request validation
- **XSS Protection**: Vue.js template escaping and CSP headers

## System Setup Process

### 1. Fresh Installation
```bash
# Clone repository
git clone <repository-url> servicevault
cd servicevault

# Install dependencies
composer install --optimize-autoloader
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database setup
php artisan migrate:fresh --seed

# Build frontend assets
npm run build
```

### 2. Initial Configuration
Access `/setup` to configure:
- **Company Information**: Name, contact details, address
- **System Settings**: Timezone, currency, date/time formats
- **Administrator Account**: First admin user credentials
- **Advanced Settings**: Cache TTL, sync intervals, feature toggles

### 3. Production Deployment
```bash
# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Set permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Queue worker setup
php artisan queue:work --daemon
```

## System Administration

### User Management
- **Role Templates**: Permission blueprints for different user types
- **Account Hierarchy**: Multi-level business account relationships
- **User Invitations**: Email-based user onboarding system
- **Domain Mapping**: Automatic user-to-account assignment

### Permission System
- **Functional Permissions**: API operations and feature access
- **Widget Permissions**: Dashboard component visibility
- **Page Permissions**: Route and navigation access
- **Context Awareness**: Service provider vs account user scoping

### Timer System Administration
- **Multi-Timer Support**: Concurrent timers per user
- **Cross-Device Sync**: Redis-based state synchronization
- **Admin Oversight**: Cross-user timer monitoring and control
- **Real-Time Updates**: WebSocket broadcasting for live updates

## System Monitoring

### Health Checks
```bash
# Application status
php artisan route:list          # Verify route registration
php artisan config:show         # Check configuration
php artisan queue:monitor       # Queue system status

# Database connectivity
php artisan migrate:status      # Migration status
php artisan tinker             # Database connection test

# Cache and sessions
redis-cli ping                 # Redis connectivity
php artisan cache:clear        # Clear application cache
```

### Performance Monitoring
- **Database Queries**: N+1 query prevention with eager loading
- **Cache Hit Rates**: Redis performance monitoring
- **Timer Sync Performance**: Cross-device synchronization metrics
- **Widget Load Times**: Dashboard performance optimization

### Security Monitoring
- **Authentication Logs**: Login attempts and session management
- **Permission Violations**: Unauthorized access attempts
- **API Rate Limiting**: Request throttling and abuse prevention
- **Input Validation**: Malicious request detection

## System Maintenance

### Database Maintenance
```bash
# Backup database
pg_dump servicevault > backup_$(date +%Y%m%d).sql

# Optimize database
VACUUM ANALYZE;                 # PostgreSQL optimization
REINDEX DATABASE servicevault;  # Index optimization

# Migration management
php artisan migrate:status      # Check migration status
php artisan migrate            # Run pending migrations
```

### Cache Management
```bash
# Clear all caches
php artisan cache:clear         # Application cache
php artisan config:clear        # Configuration cache
php artisan route:clear         # Route cache
php artisan view:clear          # Compiled views

# Permission cache refresh
redis-cli FLUSHDB              # Clear Redis cache
```

### Log Management
```bash
# View logs
tail -f storage/logs/laravel.log

# Log rotation
logrotate -f /etc/logrotate.d/laravel

# Clear old logs
find storage/logs -name "*.log" -type f -mtime +30 -delete
```

## System Integration

### API Integration
- **RESTful Architecture**: Consistent endpoint patterns
- **Token Authentication**: Laravel Sanctum with granular abilities
- **Rate Limiting**: Request throttling per endpoint
- **Response Standards**: Consistent JSON response format

### Real-Time Features
- **WebSocket Broadcasting**: Laravel Echo + Pusher/Socket.io
- **Timer Synchronization**: Cross-device state management
- **Live Updates**: Real-time UI updates for timers and notifications
- **Event Broadcasting**: Domain events for audit logging

### Third-Party Integrations
- **Email Services**: SMTP, SendGrid, Mailgun support
- **File Storage**: Local, S3, or compatible storage systems
- **Payment Processing**: Ready for Stripe, PayPal integration
- **Single Sign-On**: LDAP/AD integration ready

## Troubleshooting

### Common Issues
1. **Permission Denied**: Check file permissions on storage/cache directories
2. **Database Connection**: Verify PostgreSQL credentials and connectivity
3. **Redis Connection**: Ensure Redis server is running and accessible
4. **Asset Loading**: Run `npm run build` for production assets
5. **Timer Sync Issues**: Check Redis connectivity and WebSocket configuration

### Debug Mode
```bash
# Enable debug mode (development only)
APP_DEBUG=true

# View detailed errors
tail -f storage/logs/laravel.log

# Database query debugging
DB_QUERY_LOG=true
```

### Performance Issues
1. **Slow Database**: Check query optimization and indexing
2. **Cache Miss**: Verify Redis configuration and connectivity
3. **Frontend Loading**: Optimize Vite build configuration
4. **Timer Sync Lag**: Check Redis performance and network latency

## System Updates

### Application Updates
```bash
# Pull latest changes
git pull origin main

# Update dependencies
composer install --optimize-autoloader
npm install

# Run migrations
php artisan migrate

# Clear caches
php artisan optimize:clear

# Rebuild frontend
npm run build
```

### Security Updates
- **PHP Security Patches**: Regular PHP version updates
- **Dependency Updates**: Composer and NPM security updates
- **Database Security**: PostgreSQL version maintenance
- **SSL Certificate Renewal**: HTTPS certificate management

Service Vault is designed for reliability, security, and scalability with comprehensive system administration tools and monitoring capabilities.