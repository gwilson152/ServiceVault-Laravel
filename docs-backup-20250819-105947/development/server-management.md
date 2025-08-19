# Server Management

Simple commands for managing development servers.

## Laravel Development Server

### Start Server
```bash
php artisan serve
```

Default: `http://localhost:8000`

### Custom Host/Port
```bash
php artisan serve --host=0.0.0.0 --port=8001
```

### Stop Server
- **Foreground**: `Ctrl+C`
- **Background**: Find and kill process

## Frontend Development (Vite)

### Start Vite Dev Server
```bash
npm run dev
```

Default: `http://localhost:5173` with hot reload

### Build for Production
```bash
npm run build
```

## Process Management

### Find Running Servers
```bash
# Check specific port
lsof -i :8000

# Find PHP processes
ps aux | grep php | grep serve
```

### Kill Processes
```bash
# Kill by port
lsof -ti :8000 | xargs kill

# Kill by process ID
kill <PID>

# Force kill
kill -9 <PID>
```

## VS Code Integration

Use the configured launch configurations:
- **F5** → "Laravel Serve" - Start server
- **F5** → "Laravel Serve with Debug" - Start with Xdebug
- **Ctrl+Shift+P** → "Tasks: Run Task" → "Laravel: Serve"

## Common Issues

### Port Already in Use
```bash
# Kill existing process
lsof -ti :8000 | xargs kill

# Start on different port
php artisan serve --port=8001
```

### Permission Errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## Production Notes

**Never use `php artisan serve` in production.** Use nginx + PHP-FPM instead.

The setup.sh script configures production-ready nginx configuration.