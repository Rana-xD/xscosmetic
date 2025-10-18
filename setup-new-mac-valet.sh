#!/bin/bash

# ============================================
# Laravel Valet Setup for New Mac
# ============================================
# This script sets up a complete Laravel development environment:
# - PHP 7.4
# - MySQL
# - Composer
# - Laravel Valet
# - XS Cosmetic project (as tyche.cosmetic)
# ============================================

set -e  # Exit on error

echo "=========================================="
echo "Laravel Valet Setup for New Mac"
echo "XS Cosmetic Project (tyche.cosmetic)"
echo "=========================================="
echo ""

# ============================================
# Configuration Variables
# ============================================
PHP_VERSION="7.4"
PROJECT_NAME="tyche"
PROJECT_URL="tyche.cosmetic"
SITES_DIR="$HOME/Sites"
PROJECT_DIR="$SITES_DIR/$PROJECT_NAME"
REPO_URL="https://github.com/Rana-xD/xscosmetic.git"

echo "Configuration:"
echo "  PHP Version: $PHP_VERSION"
echo "  Project Name: $PROJECT_NAME"
echo "  Project URL: https://$PROJECT_URL"
echo "  Sites Directory: $SITES_DIR"
echo "  Project Directory: $PROJECT_DIR"
echo ""

# ============================================
# Pre-flight Checks
# ============================================
echo "Step 0: Pre-flight checks..."
echo "----------------------------------------"

# Check if Homebrew is installed
if ! command -v brew &> /dev/null; then
    echo "❌ Error: Homebrew is not installed"
    echo ""
    echo "Please install Homebrew first:"
    echo "  /bin/bash -c \"\$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)\""
    echo ""
    exit 1
fi

echo "✓ Homebrew is installed"
BREW_VERSION=$(brew --version | head -n 1)
echo "  Version: $BREW_VERSION"
echo ""

# Update Homebrew
echo "Updating Homebrew..."
brew update
echo "✓ Homebrew updated"
echo ""

# Check if PHP 7.4 is installed
if ! brew list php@7.4 &> /dev/null; then
    echo "❌ Error: PHP 7.4 is not installed"
    echo ""
    echo "Please install PHP 7.4 first:"
    echo "  brew install php@7.4"
    echo "  brew link --force --overwrite php@7.4"
    echo ""
    exit 1
fi

echo "✓ PHP 7.4 is installed"
PHP_VERSION_INSTALLED=$(php -v | head -n 1)
echo "  Version: $PHP_VERSION_INSTALLED"
echo ""

# ============================================
# STEP 1: Install MySQL
# ============================================
echo "Step 1: Installing MySQL..."
echo "----------------------------------------"

# Check if MySQL is already installed
if brew list mysql &> /dev/null; then
    echo "✓ MySQL is already installed"
else
    echo "Installing MySQL..."
    brew install mysql
    echo "✓ MySQL installed"
fi

# Start MySQL service
echo "Starting MySQL service..."
brew services start mysql

# Wait for MySQL to start
echo "Waiting for MySQL to start..."
sleep 5

# Verify MySQL is running
if pgrep -x "mysqld" > /dev/null; then
    echo "✓ MySQL is running"
else
    echo "⚠ Warning: MySQL may not have started properly"
fi

MYSQL_VERSION=$(mysql --version)
echo "✓ MySQL version: $MYSQL_VERSION"
echo ""

# ============================================
# STEP 2: Install Composer
# ============================================
echo "Step 2: Installing Composer..."
echo "----------------------------------------"

# Check if Composer is already installed
if command -v composer &> /dev/null; then
    echo "✓ Composer is already installed"
    COMPOSER_VERSION=$(composer --version)
    echo "  Version: $COMPOSER_VERSION"
else
    echo "Installing Composer..."
    
    # Download Composer installer
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    
    # Verify installer
    EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
    ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    
    if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
        echo "❌ Error: Invalid Composer installer checksum"
        rm composer-setup.php
        exit 1
    fi
    
    # Install Composer globally
    php composer-setup.php --quiet
    rm composer-setup.php
    sudo mv composer.phar /usr/local/bin/composer
    
    echo "✓ Composer installed"
    composer --version
fi

echo ""

# ============================================
# STEP 3: Install Laravel Valet
# ============================================
echo "Step 3: Installing Laravel Valet..."
echo "----------------------------------------"

# Check if Valet is already installed
if command -v valet &> /dev/null; then
    echo "✓ Valet is already installed"
    valet --version
else
    echo "Installing Laravel Valet..."
    composer global require laravel/valet
    
    # Add Composer global bin to PATH
    if ! grep -q ".composer/vendor/bin" ~/.zshrc 2>/dev/null; then
        echo "" >> ~/.zshrc
        echo "# Composer global bin" >> ~/.zshrc
        echo 'export PATH="$HOME/.composer/vendor/bin:$PATH"' >> ~/.zshrc
        echo "✓ Added Composer global bin to PATH"
    fi
    
    # Source the updated PATH
    export PATH="$HOME/.composer/vendor/bin:$PATH"
    
    echo "✓ Valet installed"
fi

echo ""

# ============================================
# STEP 4: Install Valet Dependencies
# ============================================
echo "Step 4: Installing Valet dependencies..."
echo "----------------------------------------"

# Install nginx
if brew list nginx &> /dev/null; then
    echo "✓ Nginx is already installed"
else
    echo "Installing Nginx..."
    brew install nginx
    echo "✓ Nginx installed"
fi

# Install dnsmasq
if brew list dnsmasq &> /dev/null; then
    echo "✓ Dnsmasq is already installed"
else
    echo "Installing Dnsmasq..."
    brew install dnsmasq
    echo "✓ Dnsmasq installed"
fi

echo ""

# ============================================
# STEP 5: Configure Valet with PHP 7.4
# ============================================
echo "Step 5: Configuring Laravel Valet with PHP 7.4..."
echo "----------------------------------------"

# Important: When using older PHP versions like 7.4, we need to follow
# a specific sequence to avoid Composer platform check errors
# Reference: https://laracasts.com/discuss/channels/php/issues-with-laravel-valet-when-installing-old-php-version

echo "Stopping any running Valet services..."
valet stop 2>/dev/null || true

echo "Unlinking all PHP versions..."
brew unlink php@7.0 php@7.1 php@7.2 php@7.3 php@7.4 php@8.0 php@8.1 php@8.2 2>/dev/null || true

echo "Linking PHP 7.4..."
brew link --force --overwrite php@7.4

echo "Starting PHP 7.4 service..."
brew services start php@7.4

# Wait for PHP service to start
sleep 2

echo "Updating Composer global packages..."
composer global update

echo "Removing old Valet socket..."
rm -f ~/.config/valet/valet.sock

echo "Installing Valet..."
valet install

echo "✓ Valet installed and configured with PHP 7.4"
echo ""

# ============================================
# STEP 6: Create Sites Directory
# ============================================
echo "Step 6: Creating Sites directory..."
echo "----------------------------------------"

if [ -d "$SITES_DIR" ]; then
    echo "✓ Sites directory already exists: $SITES_DIR"
else
    echo "Creating Sites directory..."
    mkdir -p "$SITES_DIR"
    echo "✓ Sites directory created: $SITES_DIR"
fi

echo ""

# ============================================
# STEP 7: Clone XS Cosmetic Repository
# ============================================
echo "Step 7: Cloning XS Cosmetic repository..."
echo "----------------------------------------"

if [ -d "$PROJECT_DIR" ]; then
    echo "⚠ Warning: Project directory already exists: $PROJECT_DIR"
    read -p "Do you want to remove it and clone fresh? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Removing existing directory..."
        rm -rf "$PROJECT_DIR"
        echo "✓ Existing directory removed"
    else
        echo "⚠ Skipping clone, using existing directory"
        SKIP_CLONE=true
    fi
fi

if [ "$SKIP_CLONE" != "true" ]; then
    echo "Cloning repository..."
    cd "$SITES_DIR"
    git clone "$REPO_URL" "$PROJECT_NAME"
    echo "✓ Repository cloned to: $PROJECT_DIR"
else
    echo "✓ Using existing project directory"
fi

echo ""

# ============================================
# STEP 8: Install Composer Dependencies
# ============================================
echo "Step 8: Installing Composer dependencies..."
echo "----------------------------------------"

cd "$PROJECT_DIR"

if [ -f "composer.json" ]; then
    echo "Installing dependencies (this may take a few minutes)..."
    composer install --no-interaction --prefer-dist
    echo "✓ Composer dependencies installed"
else
    echo "❌ Error: composer.json not found in $PROJECT_DIR"
    exit 1
fi

echo ""

# ============================================
# STEP 9: Configure Environment
# ============================================
echo "Step 9: Configuring environment..."
echo "----------------------------------------"

# Copy .env file
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        echo "Creating .env file..."
        cp .env.example .env
        echo "✓ .env file created"
    else
        echo "⚠ Warning: .env.example not found"
    fi
else
    echo "✓ .env file already exists"
fi

# Generate application key
echo "Generating application key..."
php artisan key:generate
echo "✓ Application key generated"

# Set permissions
echo "Setting permissions..."
chmod -R 775 storage
chmod -R 775 bootstrap/cache
echo "✓ Permissions set"

echo ""

# ============================================
# STEP 10: Configure Database
# ============================================
echo "Step 10: Configuring database..."
echo "----------------------------------------"

DB_NAME="tyche_cosmetic"
DB_USER="root"
DB_PASSWORD=""

echo "Database configuration:"
echo "  Database: $DB_NAME"
echo "  User: $DB_USER"
echo "  Password: (empty)"
echo ""

read -p "Do you want to create the database now? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Creating database..."
    mysql -u root -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;" 2>/dev/null || {
        echo "⚠ Warning: Could not create database automatically"
        echo "  Please create it manually:"
        echo "  mysql -u root"
        echo "  CREATE DATABASE $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
    }
    
    # Update .env file
    if [ -f ".env" ]; then
        echo "Updating .env with database configuration..."
        sed -i.bak "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" .env
        sed -i.bak "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USER/" .env
        sed -i.bak "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
        echo "✓ Database configuration updated in .env"
    fi
    
    echo "✓ Database created: $DB_NAME"
else
    echo "⚠ Skipped database creation"
    echo "  Remember to create it manually and update .env"
fi

echo ""

# ============================================
# STEP 11: Run Migrations
# ============================================
echo "Step 11: Running database migrations..."
echo "----------------------------------------"

read -p "Do you want to run migrations now? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Running migrations..."
    php artisan migrate --force
    echo "✓ Migrations completed"
else
    echo "⚠ Skipped migrations"
    echo "  Run manually later: php artisan migrate"
fi

echo ""

# ============================================
# STEP 12: Configure Valet for Project
# ============================================
echo "Step 12: Configuring Valet for project..."
echo "----------------------------------------"

cd "$SITES_DIR"

# Park the Sites directory
echo "Parking Sites directory in Valet..."
valet park
echo "✓ Sites directory parked"

# Link the specific project with custom domain
cd "$PROJECT_DIR"
echo "Linking project with custom domain: $PROJECT_URL"
valet link "$PROJECT_NAME"
echo "✓ Project linked"

# Verify the link
echo ""
echo "Valet links:"
valet links
echo ""

# ============================================
# STEP 13: Secure with HTTPS
# ============================================
echo "Step 13: Setting up HTTPS..."
echo "----------------------------------------"

read -p "Do you want to secure the site with HTTPS? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Securing site with HTTPS..."
    cd "$PROJECT_DIR"
    valet secure "$PROJECT_NAME"
    echo "✓ Site secured with HTTPS"
    SITE_URL="https://$PROJECT_NAME.test"
else
    echo "⚠ Skipped HTTPS setup"
    SITE_URL="http://$PROJECT_NAME.test"
fi

echo ""

# ============================================
# STEP 14: Configure Custom TLD (Optional)
# ============================================
echo "Step 14: Configuring custom TLD..."
echo "----------------------------------------"

echo "By default, Valet uses .test TLD"
echo "Current URL: $SITE_URL"
echo ""

read -p "Do you want to use custom TLD '.cosmetic'? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Changing TLD to .cosmetic..."
    valet tld cosmetic
    echo "✓ TLD changed to .cosmetic"
    
    # Re-secure if HTTPS was enabled
    if [[ "$SITE_URL" == https* ]]; then
        echo "Re-securing with new TLD..."
        valet secure "$PROJECT_NAME"
        SITE_URL="https://$PROJECT_NAME.cosmetic"
    else
        SITE_URL="http://$PROJECT_NAME.cosmetic"
    fi
    
    echo "✓ New URL: $SITE_URL"
else
    echo "⚠ Keeping default .test TLD"
fi

echo ""

# ============================================
# STEP 15: Clear Caches and Optimize
# ============================================
echo "Step 15: Clearing caches and optimizing..."
echo "----------------------------------------"

cd "$PROJECT_DIR"

echo "Clearing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear
echo "✓ Caches cleared"

echo ""

# ============================================
# STEP 16: Restart Valet Services
# ============================================
echo "Step 16: Restarting Valet services..."
echo "----------------------------------------"

valet restart
echo "✓ Valet services restarted"

echo ""

# ============================================
# STEP 17: Final Verification
# ============================================
echo "Step 17: Running final verification..."
echo "----------------------------------------"

# Check PHP version
PHP_CHECK=$(php -v | head -n 1)
echo "✓ PHP: $PHP_CHECK"

# Check MySQL
if pgrep -x "mysqld" > /dev/null; then
    echo "✓ MySQL: Running"
else
    echo "⚠ MySQL: Not running"
fi

# Check Composer
COMPOSER_CHECK=$(composer --version 2>/dev/null | head -n 1)
echo "✓ Composer: $COMPOSER_CHECK"

# Check Valet
VALET_CHECK=$(valet --version 2>/dev/null)
echo "✓ Valet: $VALET_CHECK"

# Check Nginx
if pgrep -x "nginx" > /dev/null; then
    echo "✓ Nginx: Running"
else
    echo "⚠ Nginx: Not running"
fi

# Check Dnsmasq
if pgrep -x "dnsmasq" > /dev/null; then
    echo "✓ Dnsmasq: Running"
else
    echo "⚠ Dnsmasq: Not running"
fi

# Check project directory
if [ -d "$PROJECT_DIR" ]; then
    echo "✓ Project directory: $PROJECT_DIR"
else
    echo "❌ Project directory not found"
fi

# Check .env file
if [ -f "$PROJECT_DIR/.env" ]; then
    echo "✓ Environment file: .env exists"
else
    echo "⚠ Environment file: .env not found"
fi

echo ""

# ============================================
# Setup Complete - Summary
# ============================================
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "Installation Summary:"
echo "----------------------------------------"
echo "  PHP Version:        $(php -v | head -n 1 | awk '{print $2}')"
echo "  MySQL:              $(mysql --version | awk '{print $5}' | sed 's/,//')"
echo "  Composer:           $(composer --version | awk '{print $3}')"
echo "  Valet:              $(valet --version)"
echo "  Project Directory:  $PROJECT_DIR"
echo "  Project URL:        $SITE_URL"
echo ""

echo "Access Your Application:"
echo "----------------------------------------"
echo "  🌐 URL: $SITE_URL"
echo "  📁 Directory: $PROJECT_DIR"
echo ""

echo "Database Information:"
echo "----------------------------------------"
echo "  Database Name: $DB_NAME"
echo "  Username:      $DB_USER"
echo "  Password:      (empty)"
echo "  Host:          127.0.0.1"
echo "  Port:          3306"
echo ""

echo "Useful Commands:"
echo "----------------------------------------"
echo "Valet:"
echo "  valet start               - Start Valet services"
echo "  valet stop                - Stop Valet services"
echo "  valet restart             - Restart Valet services"
echo "  valet links               - List all Valet links"
echo "  valet secure <name>       - Secure site with HTTPS"
echo "  valet unsecure <name>     - Remove HTTPS"
echo "  valet log                 - View Valet logs"
echo ""

echo "Laravel:"
echo "  php artisan serve         - Start development server"
echo "  php artisan migrate       - Run migrations"
echo "  php artisan db:seed       - Seed database"
echo "  php artisan cache:clear   - Clear cache"
echo "  php artisan config:clear  - Clear config cache"
echo ""

echo "MySQL:"
echo "  mysql -u root             - Access MySQL CLI"
echo "  brew services start mysql - Start MySQL"
echo "  brew services stop mysql  - Stop MySQL"
echo ""

echo "Project Management:"
echo "  cd $PROJECT_DIR"
echo "  composer install          - Install dependencies"
echo "  composer update           - Update dependencies"
echo "  npm install               - Install Node packages"
echo "  npm run dev               - Compile assets"
echo ""

echo "Next Steps:"
echo "----------------------------------------"
echo "  1. Open your browser: $SITE_URL"
echo "  2. Configure your database in .env if needed"
echo "  3. Run migrations: php artisan migrate"
echo "  4. Seed database: php artisan db:seed"
echo "  5. Install Node packages: npm install && npm run dev"
echo ""

echo "Troubleshooting:"
echo "----------------------------------------"
echo "  If site doesn't load:"
echo "    valet restart"
echo "    valet links"
echo "    ping $PROJECT_NAME.test"
echo ""
echo "  If database connection fails:"
echo "    mysql -u root"
echo "    Check .env database settings"
echo ""
echo "  View logs:"
echo "    valet log"
echo "    tail -f storage/logs/laravel.log"
echo ""

echo "Configuration Files:"
echo "----------------------------------------"
echo "  Environment:    $PROJECT_DIR/.env"
echo "  Valet Config:   ~/.config/valet"
echo "  Nginx Config:   ~/.config/valet/Nginx"
echo "  PHP Config:     /usr/local/etc/php/7.4/php.ini"
echo "  MySQL Config:   /usr/local/etc/my.cnf"
echo ""

echo "Important Notes:"
echo "----------------------------------------"
echo "  ⚠ PHP 7.4 is added to your PATH in ~/.zshrc"
echo "  ⚠ Composer global bin is added to your PATH"
echo "  ⚠ Restart your terminal or run: source ~/.zshrc"
echo "  ⚠ MySQL root user has no password by default"
echo "  ⚠ Valet services start automatically on boot"
echo ""

echo "=========================================="
echo "🎉 Happy Coding!"
echo "=========================================="
echo ""

# Open browser (optional)
read -p "Do you want to open the site in your browser now? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Opening $SITE_URL in browser..."
    open "$SITE_URL"
fi

echo ""
echo "Setup script completed successfully!"
echo ""
