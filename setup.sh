#!/bin/bash

# Service Vault Laravel Setup Script
# This script sets up a complete development environment for Service Vault

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if running as root
if [[ $EUID -eq 0 ]]; then
   log_error "This script should not be run as root"
   exit 1
fi

log_info "Setting up Service Vault development environment..."

# Update system packages
log_info "Updating system packages..."
sudo apt update && sudo apt upgrade -y

# Install system dependencies
log_info "Installing system dependencies..."
sudo apt install -y \
    curl \
    wget \
    git \
    unzip \
    software-properties-common \
    apt-transport-https \
    ca-certificates \
    gnupg \
    lsb-release \
    build-essential \
    sqlite3 \
    libsqlite3-dev \
    nginx \
    supervisor

# Install PHP 8.2 and extensions
log_info "Installing PHP 8.2 and extensions..."
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update
sudo apt install -y \
    php8.2 \
    php8.2-cli \
    php8.2-fpm \
    php8.2-mysql \
    php8.2-pgsql \
    php8.2-sqlite3 \
    php8.2-redis \
    php8.2-xml \
    php8.2-curl \
    php8.2-gd \
    php8.2-mbstring \
    php8.2-zip \
    php8.2-bcmath \
    php8.2-intl \
    php8.2-readline \
    php8.2-opcache \
    php8.2-xdebug

# Configure Xdebug for development
log_info "Configuring Xdebug for development..."
sudo tee /etc/php/8.2/mods-available/xdebug.ini > /dev/null << 'EOF'
zend_extension=xdebug.so
xdebug.mode=debug,develop
xdebug.start_with_request=trigger
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
xdebug.idekey=VSCODE
xdebug.max_nesting_level=512
xdebug.var_display_max_children=256
xdebug.var_display_max_data=1024
xdebug.var_display_max_depth=5
EOF

# Enable Xdebug for CLI and FPM
sudo phpenmod xdebug

# Install Composer
log_info "Installing Composer..."
if ! command -v composer &> /dev/null; then
    curl -sS https://getcomposer.org/installer | php
    sudo mv composer.phar /usr/local/bin/composer
    sudo chmod +x /usr/local/bin/composer
fi

# Install Node.js via NVM (preferred) or system package
log_info "Setting up Node.js..."

# Check if NVM is already installed
if [ -d "$HOME/.nvm" ] || [ -s "$HOME/.nvm/nvm.sh" ]; then
    log_success "NVM is already installed"
    # Source NVM to make it available in this script
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    [ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
else
    log_info "Installing NVM..."
    curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.0/install.sh | bash
    
    # Source NVM for immediate use
    export NVM_DIR="$HOME/.nvm"
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
    [ -s "$NVM_DIR/bash_completion" ] && \. "$NVM_DIR/bash_completion"
fi

# Install and use Node.js LTS
log_info "Installing Node.js LTS via NVM..."
nvm install --lts
nvm use --lts
nvm alias default lts/*

# Verify installation
NODE_VERSION=$(node --version)
NPM_VERSION=$(npm --version)
log_success "Node.js $NODE_VERSION (LTS) and npm $NPM_VERSION installed via NVM"

# Create .nvmrc file for project
log_info "Creating .nvmrc file..."
echo "lts/*" > .nvmrc

# Install PostgreSQL client (server is external)
log_info "Installing PostgreSQL client..."
sudo apt install -y postgresql-client-16

# Install Redis
log_info "Installing Redis..."
sudo apt install -y redis-server
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Configure external PostgreSQL database
log_info "Setting up external PostgreSQL database connection..."
DB_HOST="postgres1.drivenw.local"
DB_PORT="5432"
DB_NAME="servicevault_development"
DB_USER="service_vault"
DB_PASS="drv@13LLC!!!"

# Test database connection
log_info "Testing database connection..."
PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c "SELECT version();" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    log_success "Database connection successful"
else
    log_error "Failed to connect to database. Please check connection details."
fi

# Create Laravel project if not exists
if [ ! -f "composer.json" ]; then
    log_info "Creating Laravel project..."
    composer create-project laravel/laravel . "^11.0"
fi

# Install PHP dependencies
log_info "Installing PHP dependencies..."
composer install

# Install Laravel Sanctum
log_info "Installing Laravel Sanctum..."
composer require laravel/sanctum

# Install Inertia.js
log_info "Installing Inertia.js..."
composer require inertiajs/inertia-laravel
php artisan inertia:install --no-interaction

# Install additional Laravel packages
log_info "Installing additional Laravel packages..."
composer require \
    spatie/laravel-permission \
    barryvdh/laravel-dompdf \
    pusher/pusher-php-server \
    predis/predis \
    laravel/horizon

# Install Node.js dependencies
log_info "Installing Node.js dependencies..."
npm install

# Install Vue.js and UI dependencies
log_info "Installing Vue.js and UI dependencies..."
npm install \
    @vitejs/plugin-vue \
    vue@^3.5.0 \
    @headlessui/vue \
    @heroicons/vue \
    tailwindcss \
    @tailwindcss/forms \
    @tailwindcss/typography \
    autoprefixer \
    postcss

# Create .env file
log_info "Creating .env file..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Update .env with database configuration
log_info "Configuring environment variables..."
sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=pgsql/" .env
sed -i "s/DB_HOST=127.0.0.1/DB_HOST=${DB_HOST}/" .env
sed -i "s/DB_PORT=3306/DB_PORT=${DB_PORT}/" .env
sed -i "s/DB_DATABASE=laravel/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=root/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_PASS}/" .env

# Configure Redis
sed -i 's/CACHE_STORE=database/CACHE_STORE=redis/' .env
sed -i 's/SESSION_DRIVER=database/SESSION_DRIVER=redis/' .env
sed -i 's/QUEUE_CONNECTION=database/QUEUE_CONNECTION=redis/' .env

# Configure broadcasting
sed -i 's/BROADCAST_CONNECTION=log/BROADCAST_CONNECTION=pusher/' .env

# Generate application key
log_info "Generating application key..."
php artisan key:generate

# Create storage symlink
log_info "Creating storage symlink..."
php artisan storage:link

# Run migrations
log_info "Running database migrations..."
php artisan migrate --force

# Publish Sanctum migration
log_info "Publishing Sanctum configuration..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# Create Tailwind config
log_info "Creating Tailwind configuration..."
cat > tailwind.config.js << 'EOF'
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';
import typography from '@tailwindcss/typography';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: {
                    50: '#eff6ff',
                    500: '#3b82f6',
                    600: '#2563eb',
                    700: '#1d4ed8',
                },
                timer: {
                    running: '#10b981',
                    paused: '#f59e0b',
                    stopped: '#6b7280',
                }
            }
        },
    },

    plugins: [forms, typography],
};
EOF

# Create PostCSS config
log_info "Creating PostCSS configuration..."
cat > postcss.config.js << 'EOF'
export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},
  },
}
EOF

# Update Vite config for Vue and Inertia
log_info "Updating Vite configuration..."
cat > vite.config.js << 'EOF'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            ssr: 'resources/js/ssr.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
EOF

# Set proper permissions
log_info "Setting file permissions..."
sudo chown -R $USER:$USER .
chmod -R 755 .
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Create basic directory structure
log_info "Creating directory structure..."
mkdir -p app/Services
mkdir -p app/Repositories
mkdir -p resources/js/Components/{UI,Timer,Theme,Selectors}
mkdir -p resources/js/Pages/{Dashboard,Portal}
mkdir -p resources/js/Layouts
mkdir -p resources/js/Composables
mkdir -p resources/css/themes
mkdir -p database/seeders
mkdir -p tests/Feature
mkdir -p tests/Unit

# Create supervisor configuration for queue worker
log_info "Setting up supervisor for queue workers..."
sudo tee /etc/supervisor/conf.d/servicevault-worker.conf > /dev/null << EOF
[program:servicevault-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $(pwd)/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$USER
numprocs=1
redirect_stderr=true
stdout_logfile=$(pwd)/storage/logs/worker.log
stopwaitsecs=3600
EOF

sudo supervisorctl reread
sudo supervisorctl update

# Configure and optimize nginx
log_info "Configuring nginx as primary web server..."

# Enable nginx and disable Apache if present
sudo systemctl stop apache2 2>/dev/null || true
sudo systemctl disable apache2 2>/dev/null || true
sudo systemctl enable nginx

# Create optimized nginx configuration for Service Vault
sudo tee /etc/nginx/sites-available/servicevault << 'EOF'
server {
    listen 80 default_server;
    listen [::]:80 default_server;
    
    server_name localhost servicevault.local *.servicevault.local;
    root PROJECT_ROOT/public;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    index index.php index.html;
    charset utf-8;

    # Laravel routes
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Static assets optimization
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header X-Content-Type-Options "nosniff";
        try_files $uri =404;
    }

    # Handle favicon and robots
    location = /favicon.ico { 
        access_log off; 
        log_not_found off; 
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location = /robots.txt { 
        access_log off; 
        log_not_found off; 
        try_files $uri /index.php?$query_string;
    }

    # PHP-FPM processing
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param HTTPS off;
        
        # Optimize for Laravel
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_read_timeout 60s;
        
        include fastcgi_params;
    }

    # WebSocket support for real-time features
    location /socket.io/ {
        proxy_pass http://127.0.0.1:6001;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }

    # Block access to sensitive files
    location ~ /\.(?!well-known).* {
        deny all;
        access_log off;
        log_not_found off;
    }
    
    location ~ ^/(storage|bootstrap/cache) {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # Logs
    access_log /var/log/nginx/servicevault-access.log;
    error_log /var/log/nginx/servicevault-error.log;
    
    # Error pages
    error_page 404 /index.php;
    error_page 500 502 503 504 /50x.html;
    
    # Client limits
    client_max_body_size 100M;
    client_body_timeout 60s;
    client_header_timeout 60s;
}
EOF

# Replace PROJECT_ROOT placeholder with actual path
sudo sed -i "s|PROJECT_ROOT|$(pwd)|g" /etc/nginx/sites-available/servicevault

# Remove default nginx sites and enable Service Vault
sudo rm -f /etc/nginx/sites-enabled/default
sudo rm -f /etc/nginx/sites-enabled/000-default
sudo ln -sf /etc/nginx/sites-available/servicevault /etc/nginx/sites-enabled/

# Optimize nginx main configuration
log_info "Optimizing nginx configuration..."
sudo tee /etc/nginx/conf.d/servicevault-optimization.conf << 'EOF'
# Service Vault nginx optimizations
worker_processes auto;
worker_cpu_affinity auto;

events {
    worker_connections 2048;
    use epoll;
    multi_accept on;
}

http {
    # Basic settings
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;
    
    # Buffer optimization
    client_body_buffer_size 128k;
    client_max_body_size 100m;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;
    output_buffers 1 32k;
    postpone_output 1460;
    
    # Timeout optimization
    client_body_timeout 12;
    client_header_timeout 12;
    keepalive_requests 100;
    send_timeout 10;
    
    # Rate limiting for API endpoints
    limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
    limit_req_zone $binary_remote_addr zone=login:10m rate=5r/m;
}
EOF

# Test and reload nginx
log_info "Testing nginx configuration..."
sudo nginx -t
if [ $? -eq 0 ]; then
    log_success "Nginx configuration is valid"
    sudo systemctl reload nginx
else
    log_error "Nginx configuration test failed"
    exit 1
fi

# Save database connection info
log_info "Saving database connection info..."
cat > database_connection.txt << EOF
External PostgreSQL Database Configuration:
Host: ${DB_HOST}
Port: ${DB_PORT}
Database: ${DB_NAME}
Username: ${DB_USER}
Password: ${DB_PASS}

Connection configured in .env file.
EOF

# Final setup steps
log_info "Running final setup..."
composer dump-autoload
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Start services
log_info "Starting services..."
sudo systemctl start nginx
sudo systemctl start php8.2-fpm
sudo systemctl start redis-server

log_success "Service Vault development environment setup complete!"
log_info "Web Server: Nginx is configured as the primary web server"
log_info "Next steps:"
echo "1. Review database connection info in database_connection.txt"
echo "2. Reload your shell or run 'source ~/.bashrc' to enable NVM"
echo "3. Run 'nvm use' to activate Node.js LTS (or it will auto-activate in new terminals)"
echo "4. Run 'npm run dev' to start the development server with Vite"
echo "5. Visit http://localhost to see your application (served by nginx)"
echo "6. Optional: Add 'servicevault.local' to /etc/hosts for custom domain"
echo "7. Laravel development server not needed - nginx handles all requests"
echo ""
log_info "Useful commands:"
echo "- nvm use (activate Node.js LTS for this project)"
echo "- php artisan migrate (run database migrations)"
echo "- php artisan db:seed (seed database with test data)"
echo "- npm run build (build for production)"
echo "- php artisan queue:work (start queue worker manually)"
echo "- php artisan horizon (start Horizon dashboard)"