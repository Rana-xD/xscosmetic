# Step-by-Step Setup Guide for XS Cosmetic (Tyche) Project

This guide walks you through setting up the Laravel development environment manually, step by step.

---

## 📋 Table of Contents

1. [Prerequisites](#prerequisites)
2. [Step 1: Install MySQL](#step-1-install-mysql)
3. [Step 2: Install Composer](#step-2-install-composer)
4. [Step 3: Install Laravel Valet](#step-3-install-laravel-valet)
5. [Step 4: Install Valet Dependencies](#step-4-install-valet-dependencies)
6. [Step 5: Configure Valet with PHP 7.4](#step-5-configure-valet-with-php-74)
7. [Step 6: Create Sites Directory](#step-6-create-sites-directory)
8. [Step 7: Clone Repository](#step-7-clone-repository)
9. [Step 8: Install Composer Dependencies](#step-8-install-composer-dependencies)
10. [Step 9: Configure Environment](#step-9-configure-environment)
11. [Step 10: Configure Database](#step-10-configure-database)
12. [Step 11: Run Migrations](#step-11-run-migrations)
13. [Step 12: Configure Valet for Project](#step-12-configure-valet-for-project)
14. [Step 13: Secure with HTTPS](#step-13-secure-with-https)
15. [Step 14: Configure Custom TLD](#step-14-configure-custom-tld)
16. [Step 15: Clear Caches](#step-15-clear-caches)
17. [Step 16: Restart Valet](#step-16-restart-valet)
18. [Step 17: Final Verification](#step-17-final-verification)

---

## Prerequisites

Before you begin, make sure you have:

### ✅ Homebrew Installed

```bash
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
```

### ✅ Xcode Command Line Tools

```bash
xcode-select --install
```

### ✅ PHP 7.4 Installed

```bash
brew install php@7.4
brew link --force --overwrite php@7.4
```

Verify PHP installation:
```bash
php -v
# Should show PHP 7.4.x
```

### ✅ Git Installed

```bash
brew install git
```

---

## Step 1: Install MySQL

### Install MySQL via Homebrew

```bash
brew install mysql
```

### Start MySQL Service

```bash
brew services start mysql
```

### Verify MySQL is Running

```bash
# Check if MySQL process is running
pgrep -x "mysqld"

# Or check with brew services
brew services list | grep mysql
```

### Check MySQL Version

```bash
mysql --version
```

**Expected Output:**
```
mysql  Ver 8.x.x for macos...
```

---

## Step 2: Install Composer

### Download and Install Composer

```bash
# Download installer
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"

# Verify installer signature
EXPECTED_CHECKSUM="$(php -r 'copy("https://composer.github.io/installer.sig", "php://stdout");')"
ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"

if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
    echo "ERROR: Invalid installer checksum"
    rm composer-setup.php
    exit 1
fi

# Install Composer globally
php composer-setup.php --quiet
rm composer-setup.php
sudo mv composer.phar /usr/local/bin/composer
```

### Verify Composer Installation

```bash
composer --version
```

**Expected Output:**
```
Composer version 2.x.x
```

---

## Step 3: Install Laravel Valet

### Install Valet via Composer

```bash
composer global require laravel/valet
```

### Add Composer Global Bin to PATH

Edit your `~/.zshrc` file:

```bash
nano ~/.zshrc
```

Add this line at the end:

```bash
export PATH="$HOME/.composer/vendor/bin:$PATH"
```

Save and reload:

```bash
source ~/.zshrc
```

### Verify Valet Installation

```bash
valet --version
```

---

## Step 4: Install Valet Dependencies

### Install Nginx

```bash
brew install nginx
```

### Install Dnsmasq

```bash
brew install dnsmasq
```

### Verify Installations

```bash
brew list | grep nginx
brew list | grep dnsmasq
```

---

## Step 5: Configure Valet with PHP 7.4

**⚠️ IMPORTANT:** This step is critical for PHP 7.4 to work properly with Valet.

### Stop Valet Services

```bash
valet stop
```

### Unlink All PHP Versions

```bash
brew unlink php@7.0 php@7.1 php@7.2 php@7.3 php@7.4 php@8.0 php@8.1 php@8.2
```

### Link PHP 7.4

```bash
brew link --force --overwrite php@7.4
```

### Start PHP 7.4 Service

```bash
brew services start php@7.4
```

Wait a moment for the service to start:

```bash
sleep 2
```

### Update Composer Global Packages

**This is the critical step!**

```bash
composer global update
```

### Remove Old Valet Socket

```bash
rm -f ~/.config/valet/valet.sock
```

### Install Valet

```bash
valet install
```

### Verify Configuration

```bash
# Check PHP version
php -v

# Check Valet status
valet --version

# Check which PHP binary is being used
which php
```

**Expected Output:**
```
/usr/local/opt/php@7.4/bin/php
```

---

## Step 6: Create Sites Directory

### Create ~/Sites Directory

```bash
mkdir -p ~/Sites
```

### Navigate to Sites Directory

```bash
cd ~/Sites
```

### Verify Directory Creation

```bash
pwd
# Should show: /Users/yourusername/Sites
```

---

## Step 7: Clone Repository

### Clone XS Cosmetic Repository

```bash
cd ~/Sites
git clone https://github.com/Rana-xD/xscosmetic.git tyche
```

### Navigate to Project Directory

```bash
cd tyche
```

### Verify Clone

```bash
ls -la
# Should show Laravel project files
```

---

## Step 8: Install Composer Dependencies

### Install Dependencies

```bash
cd ~/Sites/tyche
composer install --no-interaction --prefer-dist
```

This may take a few minutes. Wait for it to complete.

### Verify Installation

```bash
ls -la vendor/
# Should show many packages
```

---

## Step 9: Configure Environment

### Copy .env File

```bash
cd ~/Sites/tyche
cp .env.example .env
```

### Generate Application Key

```bash
php artisan key:generate
```

### Set Permissions

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Verify .env File

```bash
cat .env | head -n 10
# Should show APP_NAME, APP_ENV, APP_KEY, etc.
```

---

## Step 10: Configure Database

### Create Database

```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS tyche_cosmetic;"
```

If you have a password for MySQL:

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS tyche_cosmetic;"
```

### Update .env File

Edit the `.env` file:

```bash
nano .env
```

Update these lines:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tyche_cosmetic
DB_USERNAME=root
DB_PASSWORD=
```

Save the file (Ctrl+O, Enter, Ctrl+X).

### Verify Database Connection

```bash
php artisan db:show
```

Or test the connection:

```bash
mysql -u root tyche_cosmetic -e "SELECT 1;"
```

---

## Step 11: Run Migrations

### Run Database Migrations

```bash
cd ~/Sites/tyche
php artisan migrate --force
```

### Verify Migrations

```bash
php artisan migrate:status
```

**Expected Output:**
```
Migration name ........................... Batch / Status
2014_10_12_000000_create_users_table ..... [1] Ran
...
```

---

## Step 12: Configure Valet for Project

### Park the Sites Directory

```bash
cd ~/Sites
valet park
```

This makes all directories in `~/Sites` accessible via Valet.

### Link the Project

```bash
cd ~/Sites/tyche
valet link tyche
```

### Verify Links

```bash
valet links
```

**Expected Output:**
```
+-------+------------------+
| Site  | URL              |
+-------+------------------+
| tyche | http://tyche.test|
+-------+------------------+
```

### Test the Site

Open your browser and visit:
```
http://tyche.test
```

---

## Step 13: Secure with HTTPS

### Secure the Site

```bash
cd ~/Sites/tyche
valet secure tyche
```

### Verify HTTPS

```bash
valet links
```

**Expected Output:**
```
+-------+-------------------+
| Site  | URL               |
+-------+-------------------+
| tyche | https://tyche.test|
+-------+-------------------+
```

### Test HTTPS

Open your browser and visit:
```
https://tyche.test
```

You should see a valid SSL certificate.

---

## Step 14: Configure Custom TLD

### Change TLD to .cosmetic

```bash
valet tld cosmetic
```

### Re-secure the Site

```bash
cd ~/Sites/tyche
valet secure tyche
```

### Verify New URL

```bash
valet links
```

**Expected Output:**
```
+-------+-------------------------+
| Site  | URL                     |
+-------+-------------------------+
| tyche | https://tyche.cosmetic  |
+-------+-------------------------+
```

### Test New URL

Open your browser and visit:
```
https://tyche.cosmetic
```

---

## Step 15: Clear Caches

### Clear All Laravel Caches

```bash
cd ~/Sites/tyche

# Clear application cache
php artisan cache:clear

# Clear configuration cache
php artisan config:clear

# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear
```

### Verify Cache Clear

```bash
php artisan config:cache
php artisan route:cache
```

---

## Step 16: Restart Valet

### Restart Valet Services

```bash
valet restart
```

### Verify Services

```bash
brew services list | grep -E 'nginx|dnsmasq|php'
```

**Expected Output:**
```
dnsmasq  started
nginx    started
php@7.4  started
```

---

## Step 17: Final Verification

### Check PHP Version

```bash
php -v
```

**Expected Output:**
```
PHP 7.4.x (cli) ...
```

### Check MySQL

```bash
mysql --version
pgrep -x "mysqld"
```

### Check Composer

```bash
composer --version
```

### Check Valet

```bash
valet --version
```

### Check Valet Links

```bash
valet links
```

### Test the Application

Open your browser and visit:
```
https://tyche.cosmetic
```

You should see the Laravel application running!

---

## 🎉 Setup Complete!

Your development environment is now ready!

### Quick Access

- **Project URL:** https://tyche.cosmetic
- **Project Directory:** ~/Sites/tyche
- **Database:** tyche_cosmetic

### Common Commands

```bash
# Navigate to project
cd ~/Sites/tyche

# Run artisan commands
php artisan migrate
php artisan tinker

# View logs
tail -f storage/logs/laravel.log

# Restart Valet
valet restart

# View Valet logs
valet log
```

---

## 🔧 Troubleshooting

### If Site Doesn't Load

```bash
# Restart Valet
valet restart

# Check Valet status
valet links

# Check nginx error log
valet log
```

### If Database Connection Fails

```bash
# Check MySQL is running
brew services list | grep mysql

# Restart MySQL
brew services restart mysql

# Test connection
mysql -u root tyche_cosmetic -e "SELECT 1;"
```

### If PHP Version is Wrong

```bash
# Check current PHP
php -v
which php

# Re-link PHP 7.4
brew unlink php
brew link --force --overwrite php@7.4

# Restart PHP service
brew services restart php@7.4

# Restart Valet
valet restart
```

### If HTTPS Certificate Issues

```bash
# Re-secure the site
valet unsecure tyche
valet secure tyche

# Restart Valet
valet restart
```

---

## 📚 Additional Resources

- [Laravel Valet Documentation](https://laravel.com/docs/valet)
- [Laravel Documentation](https://laravel.com/docs)
- [Homebrew Documentation](https://docs.brew.sh)
- [PHP 7.4 Documentation](https://www.php.net/manual/en/)

---

## 🆘 Need Help?

If you encounter any issues:

1. Check the troubleshooting section above
2. Review the error logs: `valet log`
3. Check Laravel logs: `tail -f storage/logs/laravel.log`
4. Restart all services: `valet restart && brew services restart mysql`

---

**Last Updated:** October 2025
**PHP Version:** 7.4
**Laravel Version:** 7.x
**Project:** XS Cosmetic (Tyche)
