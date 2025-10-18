# New Mac Setup Guide - Laravel Valet

## Overview

This guide helps you set up a complete Laravel development environment on a new Mac with:
- PHP 7.4
- MySQL
- Composer
- Laravel Valet
- XS Cosmetic project (as tyche.cosmetic)

## Prerequisites

### 1. Install Homebrew First

Before running the setup script, install Homebrew:

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

Follow the on-screen instructions to complete the installation.

### 2. Install Xcode Command Line Tools

```bash
xcode-select --install
```

### 3. Install PHP 7.4

**IMPORTANT**: You must install PHP 7.4 before running the script:

```bash
brew install php@7.4
brew link --force --overwrite php@7.4
```

### 4. Install Git (if not already installed)

```bash
brew install git
```

## Quick Start

### One-Command Setup

```bash
./setup-new-mac-valet.sh
```

The script will guide you through the entire setup process with interactive prompts.

## What the Script Does

### Step-by-Step Process

1. **Pre-flight Checks** - Verifies Homebrew and PHP 7.4 are installed
2. **Install MySQL** - Installs MySQL database server
3. **Install Composer** - Installs PHP dependency manager
4. **Install Laravel Valet** - Installs Valet and dependencies
5. **Install Valet Dependencies** - Nginx and Dnsmasq
6. **Configure Valet with PHP 7.4** - Properly switches to PHP 7.4 (important for older versions!)
7. **Create Sites Directory** - Creates ~/Sites folder
8. **Clone Repository** - Clones xscosmetic from GitHub
9. **Install Dependencies** - Runs composer install
10. **Configure Environment** - Sets up .env file
11. **Configure Database** - Creates database and updates .env
12. **Run Migrations** - Sets up database tables
13. **Configure Valet** - Parks Sites directory and links project
14. **Setup HTTPS** - Secures site with SSL certificate
15. **Configure Custom TLD** - Sets up .cosmetic domain
16. **Clear Caches** - Clears Laravel caches
17. **Restart Services** - Restarts Valet services
18. **Final Verification** - Checks all components

## Configuration

### Default Settings

```bash
PHP Version:        7.4
Project Name:       tyche
Project URL:        https://tyche.cosmetic
Sites Directory:    ~/Sites
Project Directory:  ~/Sites/tyche
Repository:         https://github.com/Rana-xD/xscosmetic.git
Database Name:      tyche_cosmetic
Database User:      root
Database Password:  (empty)
```

### Customization

To customize settings, edit the script variables at the top:

```bash
PHP_VERSION="7.4"
PROJECT_NAME="tyche"
PROJECT_URL="tyche.cosmetic"
SITES_DIR="$HOME/Sites"
REPO_URL="https://github.com/Rana-xD/xscosmetic.git"
```

## Post-Installation

### Access Your Application

```bash
# Open in browser
open https://tyche.cosmetic

# Or visit manually
https://tyche.cosmetic
```

### Verify Installation

```bash
# Check PHP version
php -v

# Check MySQL
mysql -u root

# Check Valet
valet --version

# Check Valet links
valet links

# Check Valet status
valet status
```

### Complete Laravel Setup

```bash
cd ~/Sites/tyche

# Install Node dependencies
npm install

# Compile assets
npm run dev

# Run migrations (if not done during setup)
php artisan migrate

# Seed database
php artisan db:seed

# Clear caches
php artisan cache:clear
php artisan config:clear
```

## Common Tasks

### Managing Valet

```bash
# Start Valet
valet start

# Stop Valet
valet stop

# Restart Valet
valet restart

# View logs
valet log

# List all sites
valet links

# Park a directory
cd ~/Sites
valet park

# Link a specific site
cd ~/Sites/myproject
valet link myproject

# Secure with HTTPS
valet secure myproject

# Remove HTTPS
valet unsecure myproject

# Change TLD
valet tld cosmetic

# Uninstall Valet
valet uninstall
```

### Managing MySQL

```bash
# Start MySQL
brew services start mysql

# Stop MySQL
brew services stop mysql

# Restart MySQL
brew services restart mysql

# Access MySQL CLI
mysql -u root

# Create database
mysql -u root -e "CREATE DATABASE mydb;"

# Set root password (optional)
mysql -u root
ALTER USER 'root'@'localhost' IDENTIFIED BY 'password';
FLUSH PRIVILEGES;
```

### Managing PHP

```bash
# Check PHP version
php -v

# Check PHP configuration
php --ini

# Edit PHP configuration
nano /usr/local/etc/php/7.4/php.ini

# Restart PHP-FPM
brew services restart php@7.4

# Switch PHP versions (if multiple installed)
brew unlink php@7.4
brew link php@8.0
valet use php@8.0
```

### Laravel Commands

```bash
cd ~/Sites/tyche

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Seed database
php artisan db:seed

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate key
php artisan key:generate

# Run tinker
php artisan tinker

# View routes
php artisan route:list

# Create controller
php artisan make:controller MyController

# Create model
php artisan make:model MyModel -m

# Run queue worker
php artisan queue:work

# View logs
tail -f storage/logs/laravel.log
```

## Troubleshooting

### Site Not Loading

**Problem**: Site doesn't load in browser

**Solutions**:
```bash
# Restart Valet
valet restart

# Check Valet links
valet links

# Re-link the site
cd ~/Sites/tyche
valet link tyche

# Check DNS
ping tyche.test

# Clear browser cache
# Open browser in incognito mode

# Check Nginx logs
valet log
```

### Database Connection Error

**Problem**: Cannot connect to database

**Solutions**:
```bash
# Check if MySQL is running
brew services list

# Start MySQL
brew services start mysql

# Test connection
mysql -u root

# Check .env file
cd ~/Sites/tyche
cat .env | grep DB_

# Update .env if needed
nano .env

# Clear config cache
php artisan config:clear
```

### PHP Version Issues

**Problem**: Wrong PHP version being used or Composer platform check errors

**Solutions**:
```bash
# Check current PHP version
php -v

# Check which PHP is being used
which php

# IMPORTANT: For PHP 7.4 (older versions), use this specific sequence
# Reference: https://laracasts.com/discuss/channels/php/issues-with-laravel-valet-when-installing-old-php-version

valet stop
brew unlink php@7.0 php@7.1 php@7.2 php@7.3 php@7.4 php@8.0 php@8.1 php@8.2
brew link --force --overwrite php@7.4
brew services start php@7.4
composer global update
rm -f ~/.config/valet/valet.sock
valet install

# Alternative: Use valet use with --force flag
valet use php@7.4 --force

# Update PATH in ~/.zshrc if needed
nano ~/.zshrc
# Add: export PATH="/usr/local/opt/php@7.4/bin:$PATH"

# Reload shell
source ~/.zshrc

# Restart Valet
valet restart
```

**Common Error**:
```
Fatal error: Composer detected issues in your platform: 
Your Composer dependencies require a PHP version ">= 7.2.9". 
You are running 7.1.33.
```

**Fix**: Follow the sequence above. The key is to unlink all PHP versions first, then link the target version, update Composer global packages, and reinstall Valet.

### Composer Issues

**Problem**: Composer not found or errors

**Solutions**:
```bash
# Check if Composer is installed
composer --version

# Reinstall Composer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
rm composer-setup.php

# Update Composer
composer self-update

# Clear Composer cache
composer clear-cache

# Install dependencies
cd ~/Sites/tyche
composer install
```

### Permission Issues

**Problem**: Permission denied errors

**Solutions**:
```bash
cd ~/Sites/tyche

# Fix storage permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Fix ownership
chown -R $(whoami):staff storage
chown -R $(whoami):staff bootstrap/cache

# Verify permissions
ls -la storage
```

### SSL Certificate Issues

**Problem**: HTTPS not working or certificate errors

**Solutions**:
```bash
# Re-secure the site
cd ~/Sites/tyche
valet unsecure tyche
valet secure tyche

# Trust the certificate
valet trust

# Restart Valet
valet restart

# Clear browser cache and restart browser
```

### Valet Not Starting

**Problem**: Valet services won't start

**Solutions**:
```bash
# Check what's running
brew services list

# Stop all Valet services
brew services stop nginx
brew services stop dnsmasq
brew services stop php@7.4

# Reinstall Valet
valet uninstall
composer global require laravel/valet
valet install

# Check for port conflicts
lsof -i :80
lsof -i :443

# Kill conflicting processes
sudo killall nginx
```

## Advanced Configuration

### Multiple PHP Versions

Install and switch between PHP versions:

```bash
# Install multiple versions
brew install php@7.4
brew install php@8.0
brew install php@8.1

# Switch to PHP 7.4
brew unlink php
brew link --force --overwrite php@7.4
valet use php@7.4

# Switch to PHP 8.0
brew unlink php@7.4
brew link --force --overwrite php@8.0
valet use php@8.0
```

### Custom PHP Configuration

```bash
# Edit PHP configuration
nano /usr/local/etc/php/7.4/php.ini

# Common settings to adjust:
memory_limit = 512M
upload_max_filesize = 64M
post_max_size = 64M
max_execution_time = 300

# Restart PHP-FPM
brew services restart php@7.4
valet restart
```

### Multiple Sites

```bash
# Park entire directory
cd ~/Sites
valet park

# All subdirectories become sites:
# ~/Sites/project1 -> http://project1.test
# ~/Sites/project2 -> http://project2.test

# Or link individual sites
cd ~/Sites/myproject
valet link custom-name
# Access at: http://custom-name.test
```

### Custom Valet Drivers

Create custom Valet driver for specific frameworks:

```bash
# Create driver file
nano ~/.config/valet/Drivers/CustomValetDriver.php

# Example driver structure
<?php

class CustomValetDriver extends ValetDriver
{
    public function serves($sitePath, $siteName, $uri)
    {
        return file_exists($sitePath.'/custom-file.php');
    }

    public function isStaticFile($sitePath, $siteName, $uri)
    {
        // Return path to static file or false
    }

    public function frontControllerPath($sitePath, $siteName, $uri)
    {
        return $sitePath.'/public/index.php';
    }
}
```

### Database GUI Tools

Install database management tools:

```bash
# TablePlus (recommended)
brew install --cask tableplus

# Sequel Ace (free)
brew install --cask sequel-ace

# MySQL Workbench
brew install --cask mysqlworkbench
```

## Environment-Specific Configuration

### Development Environment

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=https://tyche.cosmetic

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tyche_cosmetic
DB_USERNAME=root
DB_PASSWORD=

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

### Testing Environment

```env
APP_ENV=testing
APP_DEBUG=true

DB_CONNECTION=mysql
DB_DATABASE=tyche_cosmetic_test
```

## Performance Optimization

### OPcache Configuration

```bash
# Edit PHP configuration
nano /usr/local/etc/php/7.4/php.ini

# Add OPcache settings
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=1
opcache.revalidate_freq=2

# Restart PHP
brew services restart php@7.4
```

### Laravel Optimization

```bash
cd ~/Sites/tyche

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Optimize autoloader
composer dump-autoload -o
```

## Backup and Migration

### Backup Your Setup

```bash
# Backup database
mysqldump -u root tyche_cosmetic > ~/backup_$(date +%Y%m%d).sql

# Backup project
cd ~/Sites
tar -czf tyche_backup_$(date +%Y%m%d).tar.gz tyche

# Backup .env
cp ~/Sites/tyche/.env ~/Sites/tyche/.env.backup
```

### Restore Backup

```bash
# Restore database
mysql -u root tyche_cosmetic < ~/backup_20231017.sql

# Restore project
cd ~/Sites
tar -xzf tyche_backup_20231017.tar.gz
```

## Useful Resources

### Documentation
- [Laravel Documentation](https://laravel.com/docs/7.x)
- [Laravel Valet Documentation](https://laravel.com/docs/7.x/valet)
- [PHP 7.4 Documentation](https://www.php.net/manual/en/)
- [MySQL Documentation](https://dev.mysql.com/doc/)

### Tools
- [TablePlus](https://tableplus.com/) - Database GUI
- [Postman](https://www.postman.com/) - API testing
- [VS Code](https://code.visualstudio.com/) - Code editor
- [iTerm2](https://iterm2.com/) - Terminal replacement

### Homebrew Packages
```bash
# Useful development tools
brew install git
brew install node
brew install redis
brew install imagemagick
brew install wget
brew install curl
```

## Maintenance

### Regular Tasks

```bash
# Update Homebrew
brew update
brew upgrade

# Update Composer
composer self-update

# Update Valet
composer global update laravel/valet

# Update project dependencies
cd ~/Sites/tyche
composer update
npm update

# Clear old logs
cd ~/Sites/tyche
rm storage/logs/*.log
```

### Monthly Checklist

- [ ] Update Homebrew packages
- [ ] Update Composer dependencies
- [ ] Update NPM packages
- [ ] Backup database
- [ ] Clear old logs
- [ ] Check disk space
- [ ] Review error logs

## Security Best Practices

### Set MySQL Root Password

```bash
mysql -u root
ALTER USER 'root'@'localhost' IDENTIFIED BY 'secure_password';
FLUSH PRIVILEGES;
exit;

# Update .env
nano ~/Sites/tyche/.env
# Change: DB_PASSWORD=secure_password
```

### Keep Software Updated

```bash
# Regular updates
brew update
brew upgrade
composer self-update
composer global update
```

### Use Environment Variables

Never commit sensitive data to Git:
```bash
# Add to .gitignore
echo ".env" >> .gitignore
echo ".env.backup" >> .gitignore
```

## Uninstallation

### Remove Everything

```bash
# Uninstall Valet
valet uninstall

# Remove Valet from Composer
composer global remove laravel/valet

# Stop and remove services
brew services stop nginx
brew services stop dnsmasq
brew services stop php@7.4
brew services stop mysql

# Uninstall packages
brew uninstall nginx
brew uninstall dnsmasq
brew uninstall php@7.4
brew uninstall mysql
brew uninstall composer

# Remove project
rm -rf ~/Sites/tyche

# Remove configuration
rm -rf ~/.config/valet
```

## Support

### Getting Help

1. Check this documentation
2. Review Laravel documentation
3. Check Valet GitHub issues
4. Search Stack Overflow
5. Ask in Laravel community forums

### Common Commands Reference

```bash
# Quick reference card
valet start          # Start Valet
valet stop           # Stop Valet
valet restart        # Restart Valet
valet links          # List sites
valet log            # View logs
php artisan migrate  # Run migrations
php artisan tinker   # Laravel REPL
composer install     # Install dependencies
npm run dev          # Compile assets
```

---

**Happy Coding! 🎉**
