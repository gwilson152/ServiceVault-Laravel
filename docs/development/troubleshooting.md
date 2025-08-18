# Troubleshooting Guide

Common issues and solutions for Service Vault development.

## Authentication & CSRF Issues

### CSRF Token Mismatch Errors

**Problem**: Getting "CSRF token mismatch" errors when submitting forms or making API requests, especially after login or during long sessions.

**Root Cause**: CSRF tokens become stale when sessions are long-running or when there are timing issues between frontend and backend token synchronization.

**Solution**: Service Vault includes an automatic CSRF token refresh system:

**Automatic Handling:**
```javascript
// System automatically handles 419 CSRF errors
// - Refreshes CSRF token on 419 error
// - Retries original request with fresh token
// - No manual intervention required

// Proactive refresh every 10 minutes
// Prevents token staleness during long sessions
```

**Manual Troubleshooting:**
```javascript
// Force refresh CSRF token manually
await window.refreshCSRFToken();

// Check current token
console.log(document.querySelector('meta[name="csrf-token"]').content);

// Verify axios headers
console.log(window.axios.defaults.headers.common['X-CSRF-TOKEN']);
```

**Configuration Check:**
```bash
# Verify CSRF configuration
php artisan config:show session.same_site
php artisan config:show session.secure

# Check if Sanctum CSRF cookie endpoint is working
curl http://localhost:8000/sanctum/csrf-cookie

# Test CSRF token endpoint
curl http://localhost:8000/api/csrf-token
```

**If Issues Persist:**
1. Clear browser cookies and local storage
2. Restart development server
3. Check for conflicting middleware
4. Verify `.env` session configuration

### Session Authentication Issues

**Problem**: Users getting logged out unexpectedly or session not persisting.

**Solutions:**
```bash
# Check session configuration
php artisan config:show session

# Clear application cache
php artisan cache:clear
php artisan config:clear

# Regenerate application key
php artisan key:generate
```

## Database Issues

### Migration Errors

**Problem**: Migrations failing during development or deployment.

**Common Solutions:**
```bash
# Reset database completely
php artisan migrate:fresh --seed

# Check migration status
php artisan migrate:status

# Rollback specific number of migrations
php artisan migrate:rollback --step=3

# Check for circular foreign key dependencies
php artisan migrate --pretend
```

### UUID Generation Issues

**Problem**: Models not generating UUIDs properly.

**Solution**: Ensure models use the `HasUuid` trait:
```php
use App\Traits\HasUuid;

class Model extends Model
{
    use HasUuid;
    
    protected $keyType = 'string';
    public $incrementing = false;
}
```

## Development Server Issues

### Port Conflicts

**Problem**: Development servers failing to start due to port conflicts.

**Solutions:**
```bash
# Laravel server on different port
php artisan serve --port=8001

# Vite dev server on different port
npm run dev -- --port=5174

# Check what's using ports
lsof -i :8000
lsof -i :5173
```

### Hot Module Reload Not Working

**Problem**: Frontend changes not reflecting automatically.

**Solutions:**
```bash
# Restart Vite dev server
npm run dev

# Clear Vite cache
rm -rf node_modules/.vite
npm run dev

# Check Vite configuration
cat vite.config.js
```

## API Issues

### Authentication Errors

**Problem**: API requests returning 401 Unauthorized.

**Debug Steps:**
```bash
# Check if user is authenticated
php artisan tinker
>>> auth()->check()
>>> auth()->user()

# Verify token abilities (if using API tokens)
>>> $user->currentAccessToken()->abilities

# Check middleware stack
php artisan route:list --name=api
```

### Permission Denied Errors

**Problem**: 403 Forbidden errors on API endpoints.

**Debug Steps:**
```php
// Check user permissions
$user->hasPermission('specific.permission');
$user->roleTemplate->permissions;

// Debug policy authorization
php artisan tinker
>>> Gate::allows('view', $model);
>>> $user->can('view', $model);
```

## Frontend Issues

### JavaScript Errors

**Problem**: Vue.js components not working or throwing errors.

**Debug Steps:**
```bash
# Check browser console for errors
# Verify component imports

# Clear frontend cache
rm -rf node_modules
npm install

# Check for TypeScript errors
npm run type-check
```

### Inertia.js Navigation Issues

**Problem**: Page navigation not working correctly.

**Solutions:**
```javascript
// Use Inertia navigation (not regular links)
import { router } from '@inertiajs/vue3';

// Correct navigation
router.visit('/path');

// Avoid regular anchor tags for SPA navigation
// <a href="/path">  ❌ Wrong
// <Link href="/path"> ✅ Correct
```

## Performance Issues

### Slow Database Queries

**Problem**: Application running slowly due to database queries.

**Debug Tools:**
```bash
# Enable query logging
php artisan tinker
>>> DB::enableQueryLog();
>>> // Perform action
>>> dd(DB::getQueryLog());

# Use Laravel Telescope (if installed)
php artisan telescope:install
```

### Memory Issues

**Problem**: PHP memory limit exceeded.

**Solutions:**
```bash
# Increase memory limit temporarily
php -d memory_limit=512M artisan command

# Check memory usage
php artisan tinker
>>> memory_get_usage(true);
>>> memory_get_peak_usage(true);
```

## Environment Configuration

### Configuration Caching Issues

**Problem**: Configuration changes not taking effect.

**Solutions:**
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
```

### Environment Variable Issues

**Problem**: `.env` variables not loading correctly.

**Debug Steps:**
```bash
# Check environment
php artisan env

# Verify specific config values
php artisan tinker
>>> config('app.debug');
>>> env('CUSTOM_VARIABLE');

# Ensure .env file syntax is correct
# No spaces around = sign
# Quote values with spaces
```

## Getting Help

### Debug Information Collection

When reporting issues, include:

```bash
# System information
php --version
composer --version
node --version
npm --version

# Laravel information
php artisan --version
php artisan about

# Environment status
php artisan config:show app.debug
php artisan config:show app.env

# Recent logs
tail -f storage/logs/laravel.log
```

### Log Analysis

```bash
# Check Laravel logs
tail -f storage/logs/laravel.log

# Check web server logs (if applicable)
tail -f /var/log/nginx/error.log
tail -f /var/log/apache2/error.log

# Check browser console for frontend errors
# Check Network tab for failed requests
```

This troubleshooting guide covers the most common development issues. For more specific problems, refer to the relevant documentation sections or check the Laravel, Vue.js, and Inertia.js documentation.