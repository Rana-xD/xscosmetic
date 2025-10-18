# Laravel Valet - Quick Reference Card

## 🚀 Initial Setup

```bash
# 1. Install Homebrew first (do this manually)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# 2. Install PHP 7.4 (REQUIRED before running script)
brew install php@7.4
brew link --force --overwrite php@7.4

# 3. Run the setup script
./setup-new-mac-valet.sh
```

## 📍 Project Information

| Item | Value |
|------|-------|
| **Project Name** | tyche |
| **URL** | https://tyche.cosmetic |
| **Directory** | ~/Sites/tyche |
| **Database** | tyche_cosmetic |
| **DB User** | root |
| **DB Password** | (empty) |

## 🎯 Essential Commands

### Valet Management
```bash
valet start                    # Start Valet services
valet stop                     # Stop Valet services
valet restart                  # Restart Valet services
valet links                    # List all linked sites
valet log                      # View Valet logs
valet status                   # Check Valet status
```

### Site Management
```bash
cd ~/Sites && valet park       # Park directory (all subdirs become sites)
cd ~/Sites/mysite && valet link mysite  # Link specific site
valet secure mysite            # Enable HTTPS
valet unsecure mysite          # Disable HTTPS
valet tld cosmetic             # Change TLD to .cosmetic
```

### Laravel Commands
```bash
cd ~/Sites/tyche

php artisan migrate            # Run migrations
php artisan db:seed            # Seed database
php artisan cache:clear        # Clear cache
php artisan config:clear       # Clear config cache
php artisan tinker             # Laravel REPL
php artisan queue:work         # Run queue worker
```

### Database Commands
```bash
mysql -u root                  # Access MySQL CLI
brew services start mysql      # Start MySQL
brew services stop mysql       # Stop MySQL
brew services restart mysql    # Restart MySQL
```

### Composer Commands
```bash
composer install               # Install dependencies
composer update                # Update dependencies
composer dump-autoload         # Regenerate autoload files
composer require package/name  # Add new package
```

### NPM Commands
```bash
npm install                    # Install Node packages
npm run dev                    # Compile assets (development)
npm run watch                  # Watch and compile assets
npm run prod                   # Compile for production
```

## 🔧 Troubleshooting Quick Fixes

### Site Not Loading
```bash
valet restart
valet links
ping tyche.cosmetic
```

### Database Connection Error
```bash
brew services restart mysql
php artisan config:clear
# Check .env file DB settings
```

### Wrong PHP Version
```bash
php -v
brew link --force --overwrite php@7.4
valet use php@7.4
valet restart
```

### Permission Issues
```bash
cd ~/Sites/tyche
chmod -R 775 storage bootstrap/cache
chown -R $(whoami):staff storage bootstrap/cache
```

### SSL Certificate Issues
```bash
cd ~/Sites/tyche
valet unsecure tyche
valet secure tyche
valet restart
```

## 📂 Important Paths

| Item | Path |
|------|------|
| **Project** | ~/Sites/tyche |
| **Environment** | ~/Sites/tyche/.env |
| **Logs** | ~/Sites/tyche/storage/logs/ |
| **Valet Config** | ~/.config/valet |
| **PHP Config** | /usr/local/etc/php/7.4/php.ini |
| **MySQL Config** | /usr/local/etc/my.cnf |
| **Nginx Config** | ~/.config/valet/Nginx |

## 🔍 Quick Diagnostics

```bash
# Check all services
brew services list

# Check PHP version
php -v

# Check MySQL status
brew services list | grep mysql

# Check Valet status
valet status

# Check project structure
cd ~/Sites/tyche && ls -la

# Check environment file
cat ~/Sites/tyche/.env | grep -E "APP_|DB_"

# View recent logs
tail -20 ~/Sites/tyche/storage/logs/laravel.log
```

## 🚨 Emergency Commands

```bash
# Nuclear option - restart everything
brew services restart mysql
brew services restart nginx
brew services restart dnsmasq
brew services restart php@7.4
valet restart

# Clear all caches
cd ~/Sites/tyche
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload

# Reinstall Valet
valet uninstall
composer global require laravel/valet
valet install
```

## 📊 Performance Optimization

```bash
# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer dump-autoload -o

# Edit PHP settings
nano /usr/local/etc/php/7.4/php.ini
# Increase: memory_limit, upload_max_filesize, post_max_size

# Restart after changes
brew services restart php@7.4
valet restart
```

## 🔐 Security Checklist

- [ ] Set MySQL root password
- [ ] Use HTTPS (valet secure)
- [ ] Keep .env out of Git
- [ ] Update packages regularly
- [ ] Use strong APP_KEY
- [ ] Set proper file permissions

## 📱 Access Points

```bash
# Local development
https://tyche.cosmetic

# Database
mysql -u root -h 127.0.0.1 -P 3306

# Logs
tail -f ~/Sites/tyche/storage/logs/laravel.log

# Valet logs
valet log
```

## 🛠️ Maintenance Schedule

### Daily
```bash
# Check logs for errors
tail -20 ~/Sites/tyche/storage/logs/laravel.log
```

### Weekly
```bash
# Update dependencies
cd ~/Sites/tyche
composer update
npm update

# Clear old logs
rm storage/logs/*.log
```

### Monthly
```bash
# Update system packages
brew update && brew upgrade

# Update Composer
composer self-update

# Update Valet
composer global update laravel/valet

# Backup database
mysqldump -u root tyche_cosmetic > ~/backup_$(date +%Y%m%d).sql
```

## 💡 Pro Tips

1. **Use aliases** - Add to ~/.zshrc:
   ```bash
   alias art="php artisan"
   alias tinker="php artisan tinker"
   alias migrate="php artisan migrate"
   alias serve="php artisan serve"
   ```

2. **Quick project access**:
   ```bash
   alias tyche="cd ~/Sites/tyche"
   ```

3. **Database backup**:
   ```bash
   alias dbbackup="mysqldump -u root tyche_cosmetic > ~/backup_\$(date +%Y%m%d).sql"
   ```

4. **Clear all caches**:
   ```bash
   alias clear-all="php artisan cache:clear && php artisan config:clear && php artisan route:clear && php artisan view:clear"
   ```

## 📞 Quick Help

| Issue | Command |
|-------|---------|
| Site not loading | `valet restart` |
| DB connection error | `brew services restart mysql` |
| PHP errors | `php -v && which php` |
| Permission denied | `chmod -R 775 storage` |
| SSL issues | `valet secure tyche` |
| Slow performance | `php artisan optimize` |

## 🔗 Useful Links

- **Laravel 7.x Docs**: https://laravel.com/docs/7.x
- **Valet Docs**: https://laravel.com/docs/7.x/valet
- **PHP 7.4 Docs**: https://www.php.net/manual/en/
- **MySQL Docs**: https://dev.mysql.com/doc/

---

**Print this page for quick reference! 📄**
