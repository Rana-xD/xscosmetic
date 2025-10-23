# Quick Reference Card - Redis Cache System

## 📍 Server Information

**Application Path:** `/var/www/xscosmetic`  
**Server OS:** Ubuntu 20.04 LTS x64  
**PHP Version:** 7.4  
**Laravel Version:** 7  
**Cache Driver:** Redis  

---

## 🚀 Quick Commands

### Cache Management

```bash
# Navigate to application
cd /var/www/xscosmetic

# Check cache status
php artisan cache:resync-products

# Force resync cache
php artisan cache:resync-products --force

# Warm cache (initial load)
php artisan cache:warm-products

# Clear all cache
php artisan cache:clear
php artisan config:clear
php artisan view:clear
```

### Redis Management

```bash
# Check Redis status
sudo systemctl status redis-server

# Start Redis
sudo systemctl start redis-server

# Stop Redis
sudo systemctl stop redis-server

# Restart Redis
sudo systemctl restart redis-server

# Redis CLI (with password)
redis-cli -a YOUR_PASSWORD

# Check Redis memory
redis-cli -a YOUR_PASSWORD info memory

# Count cached products
redis-cli -a YOUR_PASSWORD DBSIZE

# Get last sync time
redis-cli -a YOUR_PASSWORD GET products:last_sync

# Clear all Redis data
redis-cli -a YOUR_PASSWORD FLUSHALL
```

### Service Restarts

```bash
# Restart PHP-FPM
sudo systemctl restart php7.4-fpm

# Restart Nginx
sudo systemctl restart nginx

# Restart Apache
sudo systemctl restart apache2

# Restart all services
sudo systemctl restart redis-server php7.4-fpm nginx
```

---

## 📁 Important File Paths

### Application Files
```
/var/www/xscosmetic/                    # Application root
/var/www/xscosmetic/.env                # Environment config
/var/www/xscosmetic/storage/logs/       # Laravel logs
/var/www/xscosmetic/app/Services/       # Cache service
```

### Configuration Files
```
/etc/redis/redis.conf                   # Redis config
/etc/php/7.4/fpm/php.ini               # PHP config
/etc/nginx/sites-available/            # Nginx config
```

### Log Files
```
/var/www/xscosmetic/storage/logs/laravel.log    # Laravel logs
/var/log/redis/redis-server.log                 # Redis logs
/var/log/php7.4-fpm.log                         # PHP-FPM logs
/var/log/nginx/error.log                        # Nginx error logs
/var/log/cache-resync.log                       # Cache resync logs
```

---

## ⚙️ Configuration Values

### .env Settings
```env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=YOUR_PASSWORD
REDIS_PORT=6379
REDIS_DB=0
```

### Cache Constants
```php
CACHE_TTL = 7200              # 2 hours
RESYNC_INTERVAL = 1800        # 30 minutes
```

---

## 🔍 Monitoring Commands

### Check Cache Statistics
```bash
cd /var/www/xscosmetic
php artisan tinker --execute="print_r(app(\App\Services\ProductCacheService::class)->getCacheStats());"
```

### Monitor Logs in Real-Time
```bash
# Laravel logs
tail -f /var/www/xscosmetic/storage/logs/laravel.log

# Redis logs
sudo tail -f /var/log/redis/redis-server.log

# PHP-FPM logs
sudo tail -f /var/log/php7.4-fpm.log

# Nginx logs
sudo tail -f /var/log/nginx/error.log

# Filter for cache-related logs
tail -f /var/www/xscosmetic/storage/logs/laravel.log | grep -i cache
```

### Check System Resources
```bash
# Memory usage
free -h

# Disk usage
df -h

# Redis memory
redis-cli -a YOUR_PASSWORD info memory | grep used_memory_human

# PHP processes
ps aux | grep php-fpm

# Redis process
ps aux | grep redis
```

---

## 📅 Cron Jobs

### Setup Cache Resync Cron

```bash
# Edit crontab
crontab -e

# Add one of these lines:

# Option 1: Resync every 30 minutes
*/30 * * * * cd /var/www/xscosmetic && php artisan cache:resync-products >> /var/log/cache-resync.log 2>&1

# Option 2: Resync every hour
0 * * * * cd /var/www/xscosmetic && php artisan cache:resync-products >> /var/log/cache-resync.log 2>&1

# Option 3: Force resync daily at 2 AM
0 2 * * * cd /var/www/xscosmetic && php artisan cache:resync-products --force >> /var/log/cache-resync.log 2>&1

# Option 4: Laravel scheduler (run every minute)
* * * * * cd /var/www/xscosmetic && php artisan schedule:run >> /dev/null 2>&1
```

### View Cron Logs
```bash
# View resync logs
tail -f /var/log/cache-resync.log

# View cron system logs
grep CRON /var/log/syslog

# List active cron jobs
crontab -l
```

---

## 🐛 Troubleshooting

### Cache Not Working

```bash
# 1. Check Redis is running
sudo systemctl status redis-server

# 2. Test Redis connection
redis-cli -a YOUR_PASSWORD ping

# 3. Check PHP Redis extension
php -m | grep redis

# 4. Clear all caches
cd /var/www/xscosmetic
php artisan cache:clear
php artisan config:clear

# 5. Restart services
sudo systemctl restart redis-server php7.4-fpm nginx

# 6. Warm cache
php artisan cache:warm-products
```

### Permission Issues

```bash
cd /var/www/xscosmetic

# Fix storage permissions
sudo chown -R www-data:www-data storage/
sudo chmod -R 775 storage/

# Fix bootstrap cache permissions
sudo chown -R www-data:www-data bootstrap/cache/
sudo chmod -R 775 bootstrap/cache/

# Fix all application permissions
sudo chown -R www-data:www-data /var/www/xscosmetic/
sudo chmod -R 755 /var/www/xscosmetic/
```

### Redis Connection Issues

```bash
# 1. Check Redis is listening
sudo netstat -tulpn | grep 6379

# 2. Test connection
redis-cli -h 127.0.0.1 -p 6379 -a YOUR_PASSWORD ping

# 3. Check Redis config
sudo nano /etc/redis/redis.conf

# 4. Restart Redis
sudo systemctl restart redis-server

# 5. Check Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

### Performance Issues

```bash
# 1. Check Redis memory
redis-cli -a YOUR_PASSWORD info memory

# 2. Check cache hit rate
redis-cli -a YOUR_PASSWORD info stats | grep keyspace

# 3. Monitor Redis in real-time
redis-cli -a YOUR_PASSWORD monitor

# 4. Check Laravel logs for slow queries
tail -f /var/www/xscosmetic/storage/logs/laravel.log | grep -i slow

# 5. Clear and rebuild cache
cd /var/www/xscosmetic
php artisan cache:clear
redis-cli -a YOUR_PASSWORD FLUSHALL
php artisan cache:warm-products
```

---

## 📊 Performance Benchmarks

### Expected Performance (7000 Products)

| Metric | Without Redis | With Redis |
|--------|--------------|------------|
| Page Load | 8-10s | < 1s |
| DB Queries | 7000+ | 1-2 |
| Memory | 450MB | 180MB |
| Cache Hit | N/A | 95%+ |

### Cache Resync Times

| Products | Time | Memory |
|----------|------|--------|
| 1,000 | 0.8s | 25MB |
| 5,000 | 2.5s | 95MB |
| 7,000 | 4.8s | 180MB |
| 10,000 | 6.2s | 240MB |

---

## 🔐 Security Checklist

- [ ] Redis password set in `/etc/redis/redis.conf`
- [ ] Redis password set in `/var/www/xscosmetic/.env`
- [ ] Redis bound to localhost only (127.0.0.1)
- [ ] Firewall blocks external Redis access (port 6379)
- [ ] File permissions set correctly (755/775)
- [ ] `.env` file not publicly accessible
- [ ] Redis logs monitored regularly
- [ ] Backup strategy in place

---

## 📞 Emergency Contacts

**When things go wrong:**

1. **Check logs first:**
   ```bash
   tail -f /var/www/xscosmetic/storage/logs/laravel.log
   ```

2. **Restart all services:**
   ```bash
   sudo systemctl restart redis-server php7.4-fpm nginx
   ```

3. **Clear all caches:**
   ```bash
   cd /var/www/xscosmetic
   php artisan cache:clear
   redis-cli -a YOUR_PASSWORD FLUSHALL
   php artisan cache:warm-products
   ```

4. **Check system resources:**
   ```bash
   free -h
   df -h
   top
   ```

---

## 📚 Documentation Files

- `UBUNTU_REDIS_DEPLOYMENT_GUIDE.md` - Full deployment guide
- `CACHE_RESYNC_GUIDE.md` - Resync documentation
- `REDIS_CACHE_SETUP.md` - General Redis setup
- `QUICK_REFERENCE.md` - This file

---

## ✅ Daily Checklist

**Morning:**
- [ ] Check Redis status: `sudo systemctl status redis-server`
- [ ] Check cache stats: `php artisan cache:resync-products`
- [ ] Review logs: `tail -100 /var/www/xscosmetic/storage/logs/laravel.log`

**Evening:**
- [ ] Check memory usage: `redis-cli -a YOUR_PASSWORD info memory`
- [ ] Verify cron jobs ran: `tail /var/log/cache-resync.log`
- [ ] Check system resources: `free -h && df -h`

**Weekly:**
- [ ] Review Redis logs: `sudo tail -100 /var/log/redis/redis-server.log`
- [ ] Check cache hit rate: `redis-cli -a YOUR_PASSWORD info stats`
- [ ] Test manual resync: `php artisan cache:resync-products --force`
- [ ] Verify backups are working

---

**Last Updated:** October 23, 2025  
**Application Path:** /var/www/xscosmetic  
**Server:** Ubuntu 20.04 LTS x64
