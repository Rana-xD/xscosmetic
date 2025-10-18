# Fix Supervisor "Can't Find PHP" Error

## Problem

Supervisor shows: `FATAL can't find command 'php'`

This happens because Supervisor doesn't have the correct PATH to find the PHP binary.

## Quick Fix

### Option 1: Re-run the Setup Script

The script has been updated to fix this issue:

```bash
cd ~/Sites/tyche
./setup-performance-optimization.sh
```

When it asks about Supervisor, say "yes" to reconfigure it.

---

### Option 2: Manual Fix

If you want to fix it manually without re-running the script:

#### Step 1: Find Your PHP Path

```bash
which php
```

Example output: `/usr/local/opt/php@7.4/bin/php`

#### Step 2: Edit the Supervisor Config

```bash
sudo nano /usr/local/etc/supervisor.d/laravel-worker.ini
```

Or if using Homebrew on Apple Silicon:

```bash
sudo nano /opt/homebrew/etc/supervisor.d/laravel-worker.ini
```

#### Step 3: Update the Configuration

Find the `environment` line and update it to include your PHP directory:

**Before:**
```ini
environment=PATH="/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"
```

**After (Intel Mac):**
```ini
environment=PATH="/usr/local/opt/php@7.4/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin",HOME="/Users/yourusername"
```

**After (Apple Silicon Mac):**
```ini
environment=PATH="/opt/homebrew/opt/php@7.4/bin:/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin",HOME="/Users/yourusername"
```

Replace `/Users/yourusername` with your actual home directory (run `echo $HOME` to find it).

#### Step 4: Reload Supervisor

```bash
supervisorctl reread
supervisorctl update
supervisorctl restart laravel-worker:*
```

#### Step 5: Check Status

```bash
supervisorctl status
```

You should see:
```
laravel-worker:laravel-worker_00   RUNNING   pid 12345, uptime 0:00:05
laravel-worker:laravel-worker_01   RUNNING   pid 12346, uptime 0:00:05
```

---

## Verify It's Working

### Check Worker Logs

```bash
tail -f ~/Sites/tyche/storage/logs/worker.log
```

You should see queue processing messages.

### Check Supervisor Status

```bash
supervisorctl status laravel-worker:*
```

Should show `RUNNING` for all workers.

### Test Queue

```bash
cd ~/Sites/tyche
php artisan queue:work --once
```

This should process one job successfully.

---

## Troubleshooting

### If Still Getting "Can't Find PHP"

1. **Check PHP is installed:**
   ```bash
   php -v
   ```

2. **Check PHP path:**
   ```bash
   which php
   ls -la $(which php)
   ```

3. **Verify the path in supervisor config:**
   ```bash
   cat /usr/local/etc/supervisor.d/laravel-worker.ini | grep environment
   ```

4. **Restart Supervisor completely:**
   ```bash
   brew services restart supervisor
   sleep 3
   supervisorctl reread
   supervisorctl update
   supervisorctl start laravel-worker:*
   ```

### If Workers Keep Crashing

Check the logs:
```bash
tail -50 ~/Sites/tyche/storage/logs/worker.log
```

Common issues:
- Database connection problems
- Missing Redis connection
- Permission issues with storage directory

### Alternative: Use Absolute PHP Path

Edit the config and use the full PHP path in the command:

```ini
command=/usr/local/opt/php@7.4/bin/php /Users/yourusername/Sites/tyche/artisan queue:work redis --sleep=3 --tries=3 --timeout=60
```

Then reload:
```bash
supervisorctl reread
supervisorctl update
supervisorctl restart laravel-worker:*
```

---

## Complete Working Example

Here's a complete working configuration for reference:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=/usr/local/opt/php@7.4/bin/php /Users/rana/Sites/tyche/artisan queue:work redis --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/Users/rana/Sites/tyche/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=60
directory=/Users/rana/Sites/tyche
environment=PATH="/usr/local/opt/php@7.4/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin",HOME="/Users/rana"
```

**Remember to replace:**
- `/Users/rana` with your actual username
- `/usr/local/opt/php@7.4/bin/php` with your actual PHP path (from `which php`)

---

## Quick Commands Reference

```bash
# Check supervisor status
supervisorctl status

# Restart workers
supervisorctl restart laravel-worker:*

# Stop workers
supervisorctl stop laravel-worker:*

# Start workers
supervisorctl start laravel-worker:*

# View worker logs
tail -f ~/Sites/tyche/storage/logs/worker.log

# Restart supervisor service
brew services restart supervisor

# Check supervisor config
cat /usr/local/etc/supervisor.d/laravel-worker.ini
```

---

## Prevention

The updated `setup-performance-optimization.sh` script now automatically:
- ✅ Detects your PHP path
- ✅ Adds PHP directory to PATH
- ✅ Includes HOME environment variable
- ✅ Uses absolute paths for everything

Just re-run the script and it will fix everything automatically!
