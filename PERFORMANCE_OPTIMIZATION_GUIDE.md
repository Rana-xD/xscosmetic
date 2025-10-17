# Performance Optimization Guide

## Overview

The `setup-performance-optimization.sh` script is a comprehensive, all-in-one solution for optimizing your XS Cosmetic Laravel application. It combines the best practices from all existing setup scripts and provides:

- **File & Redis Caching** - Intelligent caching with automatic fallback
- **Background Job Processing** - Asynchronous task handling with Redis queues
- **Supervisor Worker Management** - Auto-restart and monitoring of queue workers
- **Performance Testing** - Built-in benchmarking tools
- **Auto-recovery** - Handles errors gracefully with fallback options

## Features

### 1. Intelligent Cache Management
- Automatically detects and configures Redis or file-based caching
- Warms up product cache for instant loading
- Sets up automatic cache refresh every 15 minutes via cron
- Handles ~7000 products efficiently

### 2. Background Job Processing
- Configures Redis or file-based queue system
- Processes orders asynchronously for better performance
- Prevents UI blocking during heavy operations
- Automatic retry mechanism for failed jobs

### 3. Supervisor-Managed Workers
- Installs and configures Supervisor automatically
- Manages 2 concurrent queue workers
- Auto-restart on failure
- Persistent across system reboots
- Comprehensive logging

### 4. Performance Optimization
- Clears and optimizes Laravel caches
- Optional production optimizations (config, route, view caching)
- Fixes common PHP Redis extension issues
- Sets proper file permissions

### 5. Monitoring & Diagnostics
- Built-in performance benchmarking
- Real-time worker status monitoring
- Comprehensive logging system
- Easy troubleshooting commands

## Quick Start

### Basic Usage

```bash
# Run the complete setup
./setup-performance-optimization.sh
```

The script will guide you through the setup process with interactive prompts.

### What It Does

1. **Pre-flight Checks** - Validates Laravel project and environment
2. **PHP Configuration** - Fixes Redis extension conflicts
3. **Redis Setup** - Installs and configures Redis (optional)
4. **Predis Installation** - Installs pure PHP Redis client
5. **Environment Configuration** - Updates .env with optimal settings
6. **Permissions** - Sets correct directory permissions
7. **Cache Optimization** - Clears and warms up caches
8. **Queue Infrastructure** - Creates necessary database tables
9. **Supervisor Setup** - Configures worker management
10. **Cron Jobs** - Sets up automatic cache refresh
11. **Verification** - Tests all components
12. **Performance Benchmark** - Measures improvement

## Configuration Options

### Redis vs File Driver

The script automatically detects if Redis is available and falls back to file driver if not:

**Redis (Recommended for Production)**
- Faster performance
- Better concurrency handling
- Persistent across restarts
- Supports distributed systems

**File Driver (Good for Development)**
- No external dependencies
- Simpler setup
- Works offline
- Good for single-server setups

### Worker Configuration

By default, the script configures 2 concurrent workers:

```ini
[program:laravel-worker]
numprocs=2                    # Number of worker processes
command=php artisan queue:work redis --sleep=3 --tries=3 --timeout=60
autostart=true                # Start on boot
autorestart=true              # Restart on failure
```

You can adjust the number of workers by editing:
```bash
sudo nano /usr/local/etc/supervisor.d/laravel-worker.ini
```

Then reload:
```bash
supervisorctl reread
supervisorctl update
supervisorctl restart laravel-worker:*
```

## Environment Variables

The script configures these .env variables:

```env
# Cache Configuration
CACHE_DRIVER=redis              # or 'file'

# Queue Configuration
QUEUE_CONNECTION=redis          # or 'file'

# Redis Configuration (if using Redis)
REDIS_CLIENT=predis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache Security
CACHE_WEBHOOK_SECRET=<random-hex>
```

## Monitoring & Management

### Check System Status

```bash
# Check all workers
supervisorctl status

# Check Redis
redis-cli ping

# Check queue length
redis-cli LLEN queues:default

# View worker logs
tail -f storage/logs/worker.log

# View application logs
tail -f storage/logs/laravel.log
```

### Manage Workers

```bash
# Restart all workers
supervisorctl restart laravel-worker:*

# Stop workers
supervisorctl stop laravel-worker:*

# Start workers
supervisorctl start laravel-worker:*

# View specific worker
supervisorctl tail laravel-worker:laravel-worker_00
```

### Manage Cache

```bash
# Clear all caches
php artisan cache:clear

# Warm product cache
php artisan cache:warm-products

# Clear specific cache
php artisan cache:forget 'products_all'

# View cache stats (Redis)
redis-cli INFO stats
```

### Manage Queue

```bash
# View failed jobs
php artisan queue:failed

# Retry all failed jobs
php artisan queue:retry all

# Retry specific job
php artisan queue:retry <job-id>

# Delete all failed jobs
php artisan queue:flush

# Monitor queue in real-time
redis-cli monitor
```

## Performance Benchmarking

The script includes a built-in performance test:

```bash
# Run during setup (prompted)
# Or run manually:
./test-performance.sh
```

Expected results:
- **First Load (Database)**: 2-5 seconds
- **Second Load (Cache)**: 0.1-0.5 seconds
- **Improvement**: 80-95%

## Troubleshooting

### Workers Not Running

**Symptom**: `supervisorctl status` shows workers as STOPPED or FATAL

**Solutions**:
```bash
# Check worker logs
tail -f storage/logs/worker.log

# Check Supervisor error log
supervisorctl tail laravel-worker stderr

# Verify PHP path
which php

# Update worker config with correct PHP path
sudo nano /usr/local/etc/supervisor.d/laravel-worker.ini

# Restart
supervisorctl restart laravel-worker:*
```

### Redis Connection Failed

**Symptom**: Cannot connect to Redis

**Solutions**:
```bash
# Check if Redis is running
redis-cli ping

# Start Redis
brew services start redis

# Check Redis logs
brew services list
tail -f /usr/local/var/log/redis.log

# Restart Redis
brew services restart redis

# Clear Laravel config cache
php artisan config:clear
```

### Cache Not Working

**Symptom**: Slow page loads, no performance improvement

**Solutions**:
```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# Warm cache
php artisan cache:warm-products

# Check cache driver
php artisan tinker
>>> config('cache.default')

# Verify cache files (file driver)
ls -la storage/framework/cache/data/

# Verify Redis cache (Redis driver)
redis-cli KEYS "*products*"
```

### Jobs Not Processing

**Symptom**: Jobs stuck in queue, not being processed

**Solutions**:
```bash
# Check queue length
redis-cli LLEN queues:default

# Check worker status
supervisorctl status

# Restart workers
supervisorctl restart laravel-worker:*

# Process one job manually
php artisan queue:work --once

# Check for failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all
```

### Permission Denied Errors

**Symptom**: Cannot write to logs or cache

**Solutions**:
```bash
# Fix permissions
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Fix ownership
chown -R $(whoami):staff storage
chown -R $(whoami):staff bootstrap/cache

# Verify
ls -la storage/logs
```

## Advanced Configuration

### Increase Worker Count

For high-traffic applications, increase the number of workers:

```bash
# Edit Supervisor config
sudo nano /usr/local/etc/supervisor.d/laravel-worker.ini

# Change numprocs
numprocs=4  # Increase from 2 to 4

# Reload
supervisorctl reread
supervisorctl update
```

### Optimize for Production

```bash
# Run production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Enable OPcache in php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
```

### Configure Multiple Queues

```bash
# Create queue-specific workers
sudo nano /usr/local/etc/supervisor.d/laravel-worker-high.ini

[program:laravel-worker-high]
command=php artisan queue:work redis --queue=high --sleep=1 --tries=3
numprocs=2

[program:laravel-worker-default]
command=php artisan queue:work redis --queue=default --sleep=3 --tries=3
numprocs=2
```

### Custom Cache Refresh Interval

```bash
# Edit crontab
crontab -e

# Change from every 15 minutes to every 5 minutes
*/5 * * * * cd /path/to/project && php artisan cache:warm-products
```

## Comparison with Other Scripts

### vs setup-optimization.sh
- ✅ Includes Redis support (not just file driver)
- ✅ Better error handling and fallback options
- ✅ More comprehensive verification
- ✅ Built-in performance testing
- ✅ Multiple worker processes

### vs setup-redis.sh
- ✅ Includes complete system setup (not just Redis)
- ✅ Automatic fallback to file driver
- ✅ Integrated with Supervisor setup
- ✅ Includes cache warming and cron setup

### vs setup-queue-worker.sh
- ✅ Includes Redis installation and configuration
- ✅ Fixes PHP extension issues automatically
- ✅ Better worker configuration (2 processes, timeout handling)
- ✅ Comprehensive monitoring setup

### All-in-One Benefits
- Single command setup
- Consistent configuration
- Better error recovery
- Complete verification
- Production-ready defaults

## Best Practices

### Development Environment
```bash
# Use file driver for simplicity
CACHE_DRIVER=file
QUEUE_CONNECTION=file

# Single worker is sufficient
numprocs=1
```

### Production Environment
```bash
# Use Redis for performance
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Multiple workers for concurrency
numprocs=4

# Enable production optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Staging Environment
```bash
# Use Redis but with fewer resources
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis

# Moderate worker count
numprocs=2

# Test with production-like settings
```

## Maintenance

### Daily Tasks
```bash
# Check worker status
supervisorctl status

# Check for failed jobs
php artisan queue:failed
```

### Weekly Tasks
```bash
# Review worker logs
tail -n 100 storage/logs/worker.log

# Check Redis memory usage
redis-cli INFO memory

# Clear old failed jobs
php artisan queue:flush
```

### Monthly Tasks
```bash
# Rotate logs
supervisorctl restart laravel-worker:*

# Update dependencies
composer update

# Re-run optimizations
php artisan optimize
```

## Security Considerations

1. **Cache Webhook Secret** - Randomly generated for API security
2. **Redis Password** - Consider setting for production
3. **File Permissions** - Properly configured (775 for storage)
4. **Worker User** - Runs as current user, not root
5. **Log Rotation** - Configured to prevent disk fill

## Performance Metrics

### Expected Performance
- **Page Load**: < 2 seconds (with cache)
- **Order Processing**: < 500ms (background)
- **Cache Hit Rate**: > 95%
- **Worker Processing**: 10-50 jobs/second

### Monitoring Metrics
```bash
# Cache hit rate (Redis)
redis-cli INFO stats | grep keyspace_hits

# Queue throughput
redis-cli LLEN queues:default

# Worker uptime
supervisorctl status | grep RUNNING
```

## Support & Resources

### Log Files
- Application: `storage/logs/laravel.log`
- Workers: `storage/logs/worker.log`
- Supervisor: `/usr/local/var/log/supervisor/`
- Redis: `/usr/local/var/log/redis.log`

### Configuration Files
- Environment: `.env`
- Supervisor: `/usr/local/etc/supervisor.d/laravel-worker.ini`
- Cron: `crontab -l`
- Redis: `/usr/local/etc/redis.conf`

### Useful Links
- [Laravel Queue Documentation](https://laravel.com/docs/queues)
- [Laravel Cache Documentation](https://laravel.com/docs/cache)
- [Supervisor Documentation](http://supervisord.org/)
- [Redis Documentation](https://redis.io/documentation)

## Changelog

### Version 1.0.0 (Current)
- Initial release
- Combined all setup scripts into one
- Added intelligent Redis/file driver detection
- Implemented 2 concurrent workers
- Added performance benchmarking
- Comprehensive error handling
- Production-ready defaults

## License

This script is part of the XS Cosmetic project and follows the same license.
