#!/bin/bash

# Xdebug Installation Script for Service Vault
# Run this if you need to add Xdebug to an existing PHP installation

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

log_info "Installing Xdebug for PHP 8.2..."

# Install Xdebug
sudo apt update
sudo apt install -y php8.2-xdebug

# Configure Xdebug for VS Code debugging
log_info "Configuring Xdebug..."
sudo tee /etc/php/8.2/mods-available/xdebug.ini > /dev/null << 'EOF'
zend_extension=xdebug.so

; Xdebug 3 configuration for VS Code
xdebug.mode=debug,develop
xdebug.start_with_request=trigger
xdebug.client_host=127.0.0.1
xdebug.client_port=9003
xdebug.log=/tmp/xdebug.log
xdebug.idekey=VSCODE

; Performance settings
xdebug.max_nesting_level=512
xdebug.var_display_max_children=256
xdebug.var_display_max_data=1024
xdebug.var_display_max_depth=5

; Optional: Profiling (uncomment if needed)
; xdebug.profiler_enable_trigger=1
; xdebug.profiler_output_dir=/tmp/xdebug_profiles
EOF

# Enable Xdebug for CLI and FPM
sudo phpenmod xdebug

# Restart services
log_info "Restarting PHP services..."
sudo systemctl restart php8.2-fpm 2>/dev/null || true

# Verify installation
log_info "Verifying Xdebug installation..."
if php -m | grep -q xdebug; then
    log_success "Xdebug installed successfully!"
    echo ""
    echo "Xdebug Configuration:"
    php -r "echo 'Xdebug version: ' . phpversion('xdebug') . PHP_EOL;"
    php -r "echo 'Debug mode: ' . ini_get('xdebug.mode') . PHP_EOL;"
    php -r "echo 'Client port: ' . ini_get('xdebug.client_port') . PHP_EOL;"
else
    log_error "Xdebug installation failed!"
    exit 1
fi

echo ""
log_info "VS Code Debugging Setup:"
echo "1. Install 'PHP Debug' extension (xdebug.php-debug)"
echo "2. Set breakpoints in your PHP code"
echo "3. Start 'Listen for Xdebug' debug configuration"
echo "4. Trigger Xdebug with ?XDEBUG_SESSION_START=VSCODE in browser"
echo "5. Or use 'Laravel Serve with Debug' launch configuration"
echo ""
log_info "Browser debugging trigger: http://localhost:8000?XDEBUG_SESSION_START=VSCODE"
log_success "Xdebug setup complete!"