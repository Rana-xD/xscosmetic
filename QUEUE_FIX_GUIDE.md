# Queue Worker Fix Guide - POS Orders Not Saving

## Problem
POS orders are not being saved because the **queue worker is not running** on the server.

## Diagnosis

Run this command on your server:
```bash
cd /var/www/xscosmetic
php check-queue.php
```

This will show:
- Queue configuration
- Pending jobs count
- Worker status
- Recent orders

---

## Quick Fix (Temporary)

Process pending jobs immediately:
```bash
cd /var/www/xscosmetic
php artisan queue:work --once
```

This will process ONE batch of jobs. Run multiple times if needed:
```bash
# Process all pending jobs
while php artisan queue:work --once; do sleep 1; done
```

---

## Permanent Fix - Setup Supervisor

### Step 1: Install Supervisor
```bash
sudo apt-get update
sudo apt-get install supervisor
```

### Step 2: Create Supervisor Config
```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

Paste this configuration:
```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/xscosmetic/artisan queue:work redis --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/xscosmetic/storage/logs/worker.log
stopwaitsecs=3600
```

### Step 3: Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Step 4: Check Status
```bash
sudo supervisorctl status laravel-worker:*
```

Expected output:
```
laravel-worker:laravel-worker_00   RUNNING   pid 12345, uptime 0:00:05
laravel-worker:laravel-worker_01   RUNNING   pid 12346, uptime 0:00:05
```

---

## Alternative - Use Sync Driver (No Queue)

If you don't want to use queues, change to sync driver:

### Edit `.env`:
```bash
sudo nano /var/www/xscosmetic/.env
```

Change:
```
QUEUE_CONNECTION=redis
```

To:
```
QUEUE_CONNECTION=sync
```

### Clear config cache:
```bash
php artisan config:clear
```

**Note:** Sync driver processes jobs immediately (no background processing), which may slow down POS responses.

---

## Supervisor Management Commands

```bash
# Start worker
sudo supervisorctl start laravel-worker:*

# Stop worker
sudo supervisorctl stop laravel-worker:*

# Restart worker
sudo supervisorctl restart laravel-worker:*

# Check status
sudo supervisorctl status

# View logs
tail -f /var/www/xscosmetic/storage/logs/worker.log
```

---

## Check Queue Status Anytime

```bash
cd /var/www/xscosmetic

# Check pending jobs
php artisan queue:work --once

# Check failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Clear failed jobs
php artisan queue:flush
```

---

## Troubleshooting

### Issue: Worker keeps stopping
**Solution:** Check logs
```bash
tail -f /var/www/xscosmetic/storage/logs/worker.log
tail -f /var/www/xscosmetic/storage/logs/laravel.log
```

### Issue: Jobs fail with memory error
**Solution:** Increase memory limit in supervisor config
```ini
command=php -d memory_limit=512M /var/www/xscosmetic/artisan queue:work redis --sleep=3 --tries=3 --timeout=600
```

### Issue: Old jobs stuck in queue
**Solution:** Clear and restart
```bash
php artisan queue:clear redis
sudo supervisorctl restart laravel-worker:*
```

---

## Verify Fix

1. **Check worker is running:**
   ```bash
   sudo supervisorctl status laravel-worker:*
   ```

2. **Create test POS order** via `/pos` page

3. **Check order appears in `/invoice`**

4. **Check logs:**
   ```bash
   tail -f /var/www/xscosmetic/storage/logs/laravel.log | grep "POS Order"
   ```

You should see:
```
POS Order created: 001
TPOS created for order: 001
Stock deducted for product 123: 2 units
POS Order processed successfully: 001
```

---

## Summary

**Root Cause:** Queue worker not running on server

**Quick Fix:** `php artisan queue:work --once`

**Permanent Fix:** Setup Supervisor to auto-start queue worker

**Alternative:** Use `QUEUE_CONNECTION=sync` in `.env`
