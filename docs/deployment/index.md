# Deployment Guide

Production deployment and infrastructure setup for Service Vault.

> **Note**: Complete deployment documentation is integrated into [System Documentation](../system/index.md#system-setup-process)

## Quick Deploy

### Server Requirements
- **PHP 8.2+** with required extensions
- **PostgreSQL 13+** with UUID support  
- **Redis 6+** for caching and sessions
- **Nginx** or Apache web server
- **Node.js 18+** for asset compilation

### Production Setup
```bash
# Clone and install
git clone <repository> servicevault
cd servicevault
composer install --optimize-autoloader --no-dev
npm install && npm run build

# Environment
cp .env.example .env
# Configure: DB_*, REDIS_*, APP_URL, APP_KEY
php artisan key:generate

# Database
php artisan migrate --force
php artisan db:seed --class=ProductionSeeder

# Optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Environment Configuration
```bash
# Core Laravel
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=servicevault
DB_USERNAME=servicevault_user
DB_PASSWORD=secure_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

### Nginx Configuration
```nginx
server {
    listen 443 ssl;
    server_name your-domain.com;
    root /var/www/servicevault/public;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### File Permissions
```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Monitoring & Maintenance

For production monitoring, backup strategies, and maintenance procedures, see [System Administration](../system/index.md#system-monitoring).

## Security Considerations

- Enable HTTPS with valid SSL certificates
- Configure firewall rules (ports 80, 443, 22 only)
- Regular security updates for PHP, PostgreSQL, and OS
- Database connection encryption
- Environment variable security (no secrets in version control)

For detailed security configuration, see [System Authentication](../system/authentication-system.md#security-features).