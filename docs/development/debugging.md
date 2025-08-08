# PHP Debugging Setup for Service Vault

## Quick Setup

### 1. Install Xdebug
```bash
# If using setup.sh (recommended)
./setup.sh

# Or install Xdebug separately
./install-xdebug.sh
```

### 2. Required VS Code Extensions
- **PHP Debug** (`xdebug.php-debug`) - Essential for PHP debugging
- **PHP Tools** (`DEVSENSE.phptools-vscode`) - Advanced PHP support
- **PHP Intelephense** (`bmewburn.vscode-intelephense-client`) - Language server

### 3. Debug Configurations Available

#### A. Listen for Xdebug (Most Common)
1. Set breakpoints in PHP code
2. Start debug session: **F5** → "Listen for Xdebug"
3. Trigger in browser: `http://localhost:8000?XDEBUG_SESSION_START=VSCODE`

#### B. Laravel Serve with Debug
1. **F5** → "Laravel Serve with Debug"
2. Automatically starts server with Xdebug enabled
3. All requests will trigger debugging

#### C. Debug Current File
1. Open a PHP file
2. **F5** → "Launch currently open script"
3. Debugs the specific file

## Browser Debugging

### Manual Trigger
Add to any URL: `?XDEBUG_SESSION_START=VSCODE`

### Browser Extension (Recommended)
Install **Xdebug Helper** extension for Chrome/Firefox:
- Chrome: [Xdebug Helper](https://chrome.google.com/webstore/detail/xdebug-helper/eadndfjplgieldjbigjakmdgkmoaaaoc)
- Firefox: [Xdebug Helper](https://addons.mozilla.org/en-US/firefox/addon/xdebug-helper-for-firefox/)

## Debugging Features

### Breakpoints
- **Line breakpoints**: Click in gutter or press F9
- **Conditional breakpoints**: Right-click breakpoint → Edit Condition
- **Logpoints**: Right-click gutter → Add Logpoint

### Debug Console
- **Variables**: View all variables in current scope
- **Watch**: Monitor specific expressions
- **Call Stack**: See function call hierarchy
- **Debug Console**: Execute PHP code in current context

### Step Controls
- **Continue (F5)**: Resume execution
- **Step Over (F10)**: Execute next line
- **Step Into (F11)**: Enter function calls
- **Step Out (Shift+F11)**: Exit current function

## Laravel-Specific Debugging

### Route Debugging
Set breakpoints in:
- Controllers: `app/Http/Controllers/`
- Middleware: `app/Http/Middleware/`
- Models: `app/Models/`

### API Debugging
1. Set breakpoints in API controllers
2. Use REST Client or Postman
3. Add header: `Cookie: XDEBUG_SESSION=VSCODE`

### Artisan Command Debugging
```bash
# Debug artisan commands
XDEBUG_SESSION=VSCODE php artisan your:command
```

## Troubleshooting

### Xdebug Not Working
```bash
# Check if Xdebug is loaded
php -m | grep xdebug

# Check Xdebug configuration
php --ri xdebug

# Check Xdebug log
tail -f /tmp/xdebug.log
```

### Common Issues

#### Port Conflicts
If port 9003 is in use:
1. Change in Xdebug config: `xdebug.client_port=9004`
2. Update VS Code launch.json port setting
3. Restart PHP-FPM: `sudo systemctl restart php8.2-fpm`

#### Path Mapping Issues
For Docker/remote development, update launch.json:
```json
"pathMappings": {
    "/var/www/html": "${workspaceFolder}"
}
```

#### Firewall Issues
Allow Xdebug port:
```bash
sudo ufw allow 9003/tcp
```

## Performance Notes

### Development vs Production
- Xdebug is **automatically disabled in production**
- Only enabled in local development environment
- No performance impact on production servers

### Disable When Not Debugging
```bash
# Temporarily disable Xdebug
sudo phpdismod xdebug
sudo systemctl restart php8.2-fpm

# Re-enable when needed
sudo phpenmod xdebug
sudo systemctl restart php8.2-fpm
```

## Configuration Files

### Xdebug Config: `/etc/php/8.2/mods-available/xdebug.ini`
```ini
zend_extension=xdebug.so
xdebug.mode=debug,develop
xdebug.start_with_request=trigger
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
xdebug.idekey=VSCODE
```

### VS Code Launch Config: `.vscode/launch.json`
Preconfigured with optimal settings for Laravel debugging.

## Quick Test

1. Create test route in `routes/web.php`:
```php
Route::get('/debug-test', function () {
    $message = "Debug test";
    return view('welcome', compact('message'));
});
```

2. Set breakpoint on `$message = "Debug test";`
3. Start "Listen for Xdebug" in VS Code
4. Visit: `http://localhost:8000/debug-test?XDEBUG_SESSION_START=VSCODE`
5. Breakpoint should trigger!

## Advanced Debugging

### Database Query Debugging
Enable query logging in `.env`:
```env
DB_LOG_QUERIES=true
LOG_LEVEL=debug
```

### Performance Profiling
Uncomment in Xdebug config:
```ini
xdebug.profiler_enable_trigger=1
xdebug.profiler_output_dir=/tmp/xdebug_profiles
```

Trigger with: `?XDEBUG_PROFILE=1`

This setup provides comprehensive PHP debugging capabilities for Service Vault development.