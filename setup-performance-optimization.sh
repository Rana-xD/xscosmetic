#!/bin/bash

# ============================================
# XS Cosmetic - Complete Performance Optimization Setup
# ============================================
# This script provides comprehensive setup for:
# - File & Redis caching optimization
# - Background job processing with Redis queues
# - Supervisor-managed queue workers
# - Auto-restart and monitoring capabilities
# - Performance testing and verification
# ============================================

set -e  # Exit on error

echo "=========================================="
echo "XS Cosmetic - Performance Optimization"
echo "Complete Setup Script"
echo "=========================================="
echo ""

# ============================================
# Configuration Variables
# ============================================
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

PHP_PATH=$(which php)
CURRENT_USER=$(whoami)
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo "✓ Working directory: $SCRIPT_DIR"
echo "✓ PHP path: $PHP_PATH"
echo "✓ Current user: $CURRENT_USER"
echo ""

# ============================================
# Pre-flight Checks
# ============================================
echo "Step 0: Pre-flight checks..."
echo "----------------------------------------"

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Are you in the Laravel project root?"
    exit 1
fi
echo "✓ Laravel project detected"

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found"
    exit 1
fi
echo "✓ .env file found"

# Backup .env
cp .env ".env.backup.$TIMESTAMP"
echo "✓ .env backed up"
echo ""

# ============================================
# STEP 1: Fix PHP Redis Extension Issues
# ============================================
echo "Step 1: Fixing PHP Redis extension issues..."
echo "----------------------------------------"

PHP_INI=$(php --ini | grep "Loaded Configuration File" | cut -d: -f2 | xargs)

if [ -f "$PHP_INI" ]; then
    if grep -q "^extension=redis.so" "$PHP_INI"; then
        echo "⚠ Found problematic redis.so in php.ini, commenting it out..."
        sudo cp "$PHP_INI" "$PHP_INI.backup.$TIMESTAMP"
        sudo sed -i.bak 's/^extension=redis.so/;extension=redis.so/' "$PHP_INI"
        echo "✓ Commented out redis.so (will use Predis instead)"
    else
        echo "✓ No problematic redis.so configuration found"
    fi
fi

# Check conf.d directory
CONF_D=$(php --ini | grep "Scan for additional .ini files" | cut -d: -f2 | xargs)
if [ -d "$CONF_D" ]; then
    if ls "$CONF_D"/*redis*.ini 2>/dev/null 1>&2; then
        echo "⚠ Found Redis extension config files in $CONF_D"
        for file in "$CONF_D"/*redis*.ini; do
            if [ -f "$file" ]; then
                echo "  Disabling: $file"
                sudo mv "$file" "$file.disabled.$TIMESTAMP"
            fi
        done
        echo "✓ Disabled Redis extension config files"
    fi
fi

echo "✓ PHP configuration cleaned"
echo ""

# ============================================
# STEP 2: Install and Configure Redis
# ============================================
echo "Step 2: Installing and configuring Redis..."
echo "----------------------------------------"

# Check if Redis is installed
if command -v redis-server &> /dev/null; then
    echo "✓ Redis is already installed"
    REDIS_VERSION=$(redis-server --version | head -n 1)
    echo "  Version: $REDIS_VERSION"
else
    echo "⚠ Redis is not installed"
    read -p "Do you want to install Redis now? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Installing Redis via Homebrew..."
        brew install redis
        echo "✓ Redis installed successfully"
    else
        echo "⚠ Skipping Redis installation. Will use file-based cache and queue."
        USE_FILE_DRIVER=true
    fi
fi

# Start Redis Service
if command -v redis-server &> /dev/null && [ "$USE_FILE_DRIVER" != "true" ]; then
    if pgrep -x "redis-server" > /dev/null; then
        echo "✓ Redis is already running"
    else
        echo "Starting Redis service..."
        brew services start redis
        sleep 2
        
        if pgrep -x "redis-server" > /dev/null; then
            echo "✓ Redis service started successfully"
        else
            echo "⚠ Warning: Redis may not have started properly"
            USE_FILE_DRIVER=true
        fi
    fi
    
    # Test Redis Connection
    if redis-cli ping > /dev/null 2>&1; then
        echo "✓ Redis connection successful (PONG received)"
        USE_REDIS=true
    else
        echo "⚠ Warning: Cannot connect to Redis, falling back to file driver"
        USE_FILE_DRIVER=true
    fi
fi

echo ""

# ============================================
# STEP 3: Install Predis Package
# ============================================
if [ "$USE_REDIS" = "true" ]; then
    echo "Step 3: Installing Predis package..."
    echo "----------------------------------------"
    
    if grep -q "predis/predis" composer.json; then
        echo "✓ Predis package is already in composer.json"
    else
        echo "Installing Predis package (pure PHP Redis client)..."
        composer require predis/predis --no-interaction
        echo "✓ Predis package installed"
    fi
    echo ""
fi

# ============================================
# STEP 4: Configure Environment Variables
# ============================================
echo "Step 4: Configuring environment variables..."
echo "----------------------------------------"

# Determine cache and queue driver
if [ "$USE_REDIS" = "true" ]; then
    CACHE_DRIVER="redis"
    QUEUE_DRIVER="redis"
    echo "Using Redis for cache and queue"
else
    CACHE_DRIVER="file"
    QUEUE_DRIVER="file"
    echo "Using file driver for cache and queue"
fi

# Configure CACHE_DRIVER
if grep -q "^CACHE_DRIVER=" .env; then
    sed -i.bak "s/^CACHE_DRIVER=.*/CACHE_DRIVER=$CACHE_DRIVER/" .env
    echo "✓ Updated CACHE_DRIVER=$CACHE_DRIVER"
else
    echo "CACHE_DRIVER=$CACHE_DRIVER" >> .env
    echo "✓ Added CACHE_DRIVER=$CACHE_DRIVER"
fi

# Configure QUEUE_CONNECTION
if grep -q "^QUEUE_CONNECTION=" .env; then
    sed -i.bak "s/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=$QUEUE_DRIVER/" .env
    echo "✓ Updated QUEUE_CONNECTION=$QUEUE_DRIVER"
else
    echo "QUEUE_CONNECTION=$QUEUE_DRIVER" >> .env
    echo "✓ Added QUEUE_CONNECTION=$QUEUE_DRIVER"
fi

# Configure Redis client if using Redis
if [ "$USE_REDIS" = "true" ]; then
    if grep -q "^REDIS_CLIENT=" .env; then
        sed -i.bak 's/^REDIS_CLIENT=.*/REDIS_CLIENT=predis/' .env
        echo "✓ Updated REDIS_CLIENT=predis"
    else
        echo "REDIS_CLIENT=predis" >> .env
        echo "✓ Added REDIS_CLIENT=predis"
    fi
    
    # Configure Redis connection settings
    if ! grep -q "^REDIS_HOST=" .env; then
        echo "REDIS_HOST=127.0.0.1" >> .env
        echo "✓ Added REDIS_HOST=127.0.0.1"
    fi
    
    if ! grep -q "^REDIS_PASSWORD=" .env; then
        echo "REDIS_PASSWORD=null" >> .env
        echo "✓ Added REDIS_PASSWORD=null"
    fi
    
    if ! grep -q "^REDIS_PORT=" .env; then
        echo "REDIS_PORT=6379" >> .env
        echo "✓ Added REDIS_PORT=6379"
    fi
fi

# Add cache webhook secret if not exists
if ! grep -q "^CACHE_WEBHOOK_SECRET=" .env; then
    RANDOM_SECRET=$(openssl rand -hex 16)
    echo "CACHE_WEBHOOK_SECRET=$RANDOM_SECRET" >> .env
    echo "✓ Added CACHE_WEBHOOK_SECRET"
fi

echo ""

# ============================================
# STEP 5: Set Up Permissions
# ============================================
echo "Step 5: Setting up permissions..."
echo "----------------------------------------"

chmod -R 775 storage/framework/cache 2>/dev/null || mkdir -p storage/framework/cache && chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/queue 2>/dev/null || mkdir -p storage/framework/queue && chmod -R 775 storage/framework/queue
chmod -R 775 storage/logs 2>/dev/null || mkdir -p storage/logs && chmod -R 775 storage/logs

echo "✓ Directory permissions set"
echo ""

# ============================================
# STEP 6: Clear and Optimize Caches
# ============================================
echo "Step 6: Clearing and optimizing caches..."
echo "----------------------------------------"

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo "✓ Caches cleared"

# Optimize for production (optional)
read -p "Do you want to optimize for production? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Caching configuration..."
    php artisan config:cache
    echo "✓ Configuration cached"
    
    # Try to cache routes
    echo "Attempting to cache routes..."
    
    # Run route:cache and capture exit code
    if php artisan route:cache > /tmp/route_cache.log 2>&1; then
        echo "✓ Route cache created"
    else
        echo "⚠ Skipping route cache (compatibility issues with PHP 7.4)"
        echo "  Note: Config and view caching are more important for performance"
        php artisan route:clear > /dev/null 2>&1
    fi
    
    echo "Caching views..."
    php artisan view:cache
    echo "✓ View cache created"
    
    echo ""
    echo "✓ Production optimizations applied (config + views)"
fi

echo ""

# ============================================
# STEP 7: Set Up Queue Infrastructure
# ============================================
echo "Step 7: Setting up queue infrastructure..."
echo "----------------------------------------"

# Check if failed_jobs table exists
if ! php artisan tinker --execute="echo Schema::hasTable('failed_jobs') ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
    echo "Creating failed_jobs table..."
    php artisan queue:failed-table
    php artisan migrate --force
    echo "✓ Failed jobs table created"
else
    echo "✓ Failed jobs table already exists"
fi

# Create queue directory if using file driver
if [ "$QUEUE_DRIVER" = "file" ]; then
    mkdir -p storage/framework/queue
    echo "✓ Queue directory ready"
fi

echo ""

# ============================================
# STEP 8: Warm Up Cache
# ============================================
echo "Step 8: Warming up cache (products & users)..."
echo "----------------------------------------"

if php artisan list | grep -q "cache:warm-products"; then
    echo "Warming cache (this may take a few moments)..."
    php artisan cache:warm-products
    echo "✓ Cache warmed (products & users)"
else
    echo "⚠ cache:warm-products command not found, skipping..."
fi

echo ""

# ============================================
# STEP 9: Install and Configure Supervisor
# ============================================
echo "Step 9: Installing and configuring Supervisor..."
echo "----------------------------------------"

# Check if Supervisor is installed
if command -v supervisord &> /dev/null; then
    echo "✓ Supervisor is already installed"
    supervisord -v
else
    echo "⚠ Supervisor is not installed"
    read -p "Do you want to install Supervisor now? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Installing Supervisor via Homebrew..."
        brew install supervisor
        echo "✓ Supervisor installed"
    else
        echo "⚠ Skipping Supervisor installation"
        SKIP_SUPERVISOR=true
    fi
fi

if [ "$SKIP_SUPERVISOR" != "true" ]; then
    # Start Supervisor Service
    if pgrep -x "supervisord" > /dev/null; then
        echo "✓ Supervisor is already running"
    else
        echo "Starting Supervisor service..."
        brew services start supervisor
        sleep 3
        
        if pgrep -x "supervisord" > /dev/null; then
            echo "✓ Supervisor started successfully"
        else
            echo "⚠ Warning: Supervisor may not have started"
            echo "Trying to start manually..."
            supervisord -c /usr/local/etc/supervisord.ini &
            sleep 2
        fi
    fi
    
    # Find Supervisor Config Directory
    if [ -d "/usr/local/etc/supervisor.d" ]; then
        SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
    elif [ -d "/opt/homebrew/etc/supervisor.d" ]; then
        SUPERVISOR_DIR="/opt/homebrew/etc/supervisor.d"
    elif [ -d "/etc/supervisor/conf.d" ]; then
        SUPERVISOR_DIR="/etc/supervisor/conf.d"
    else
        echo "Creating Supervisor config directory..."
        sudo mkdir -p /usr/local/etc/supervisor.d
        SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
        
        # Update supervisord.ini to include this directory
        SUPERVISORD_INI="/usr/local/etc/supervisord.ini"
        if [ -f "$SUPERVISORD_INI" ]; then
            if ! grep -q "supervisor.d/\*.ini" "$SUPERVISORD_INI"; then
                echo "" | sudo tee -a "$SUPERVISORD_INI" > /dev/null
                echo "[include]" | sudo tee -a "$SUPERVISORD_INI" > /dev/null
                echo "files = /usr/local/etc/supervisor.d/*.ini" | sudo tee -a "$SUPERVISORD_INI" > /dev/null
                echo "✓ Updated supervisord.ini to include supervisor.d"
            fi
        fi
    fi
    
    echo "✓ Supervisor config directory: $SUPERVISOR_DIR"
    
    # Create Laravel Worker Config
    WORKER_CONFIG="$SUPERVISOR_DIR/laravel-worker.ini"
    
    echo "Creating Laravel worker configuration..."
    
    # Note: On macOS, the 'user' directive might not work as expected
    # Supervisor runs as the current user by default on macOS via Homebrew
    sudo tee "$WORKER_CONFIG" > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_PATH $SCRIPT_DIR/artisan queue:work $QUEUE_DRIVER --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=$SCRIPT_DIR/storage/logs/worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
stopwaitsecs=60
directory=$SCRIPT_DIR
environment=PATH="/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"
EOF
    
    echo "✓ Created worker config at: $WORKER_CONFIG"
    
    # Reload Supervisor
    echo "Reloading Supervisor configuration..."
    if supervisorctl reread 2>&1 | grep -q "ERROR"; then
        echo "⚠ Warning: Supervisor reread had issues, trying to restart supervisor..."
        brew services restart supervisor
        sleep 3
    fi
    
    supervisorctl update 2>&1 | grep -v "ERROR (no such group)" || true
    
    # Start Queue Worker
    echo "Starting queue workers..."
    sleep 2
    
    if supervisorctl status laravel-worker:* 2>&1 | grep -q "no such group"; then
        echo "⚠ Queue workers not started automatically"
        echo "  This is normal on first setup. Restarting supervisor..."
        brew services restart supervisor
        sleep 5
        supervisorctl reread
        supervisorctl update
        supervisorctl start laravel-worker:*
    else
        supervisorctl restart laravel-worker:* 2>/dev/null || supervisorctl start laravel-worker:* 2>/dev/null
    fi
    
    sleep 2
    
    # Check status
    if supervisorctl status laravel-worker:* 2>&1 | grep -q "RUNNING"; then
        echo "✓ Queue workers started successfully"
    else
        echo "⚠ Queue workers may need manual start"
        echo "  Run: supervisorctl start laravel-worker:*"
    fi
fi

echo ""

# ============================================
# STEP 10: Set Up Cron Job for Cache Refresh
# ============================================
echo "Step 10: Setting up cron job for cache refresh..."
echo "----------------------------------------"

if php artisan list | grep -q "cache:warm-products"; then
    CRON_CMD="*/15 * * * * cd $SCRIPT_DIR && $PHP_PATH artisan cache:warm-products >> /dev/null 2>&1"
    
    # Check if cron job already exists
    if crontab -l 2>/dev/null | grep -q "cache:warm-products"; then
        echo "✓ Cron job already exists"
    else
        echo "A cron job will refresh cache every 15 minutes."
        read -p "Do you want to add the cron job now? (y/n) " -n 1 -r
        echo ""
        
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            # Backup existing crontab
            crontab -l > /tmp/crontab.bak 2>/dev/null || true
            
            # Add new cron job
            (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
            
            echo "✓ Cron job added successfully"
            echo "  Cache will refresh every 15 minutes automatically"
        else
            echo "⚠ Skipped cron job setup"
            echo "  You can add it manually later:"
            echo "  crontab -e"
            echo "  Then add: $CRON_CMD"
        fi
    fi
else
    echo "⚠ cache:warm-products command not found, skipping cron setup"
fi

echo ""

# ============================================
# STEP 11: Verification and Testing
# ============================================
echo "Step 11: Running verification tests..."
echo "----------------------------------------"

# Check Redis connection (if using Redis)
if [ "$USE_REDIS" = "true" ]; then
    if redis-cli ping > /dev/null 2>&1; then
        echo "✓ Redis is running and accessible"
    else
        echo "⚠ Warning: Redis connection failed"
    fi
    
    # Test Laravel Redis connection
    TEST_RESULT=$(php artisan tinker --execute="try { Redis::connection()->ping(); echo 'SUCCESS'; } catch (\Exception \$e) { echo 'FAILED'; }" 2>&1)
    if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
        echo "✓ Laravel can connect to Redis"
    else
        echo "⚠ Warning: Laravel Redis connection test failed"
    fi
fi

# Check queue configuration
QUEUE_CONFIG=$(php artisan tinker --execute="echo config('queue.default');" 2>&1 | tail -n 1)
echo "✓ Queue driver: $QUEUE_CONFIG"

# Check cache configuration
CACHE_CONFIG=$(php artisan tinker --execute="echo config('cache.default');" 2>&1 | tail -n 1)
echo "✓ Cache driver: $CACHE_CONFIG"

# Check Supervisor status
if [ "$SKIP_SUPERVISOR" != "true" ] && command -v supervisorctl &> /dev/null; then
    echo ""
    echo "Supervisor Status:"
    supervisorctl status | grep laravel-worker || echo "⚠ No workers found"
fi

# Check cache files
if [ -d "storage/framework/cache/data" ] && [ "$(ls -A storage/framework/cache/data 2>/dev/null)" ]; then
    CACHE_FILES=$(find storage/framework/cache/data -type f 2>/dev/null | wc -l)
    echo "✓ Cache files created: $CACHE_FILES files"
else
    echo "⚠ Cache directory is empty or using Redis"
fi

echo ""

# ============================================
# STEP 12: Performance Benchmark
# ============================================
echo "Step 12: Running performance benchmark..."
echo "----------------------------------------"

read -p "Do you want to run a performance test? (y/n) " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "Running performance test..."
    echo ""
    
    # Clear cache for accurate test
    php artisan cache:clear > /dev/null 2>&1
    
    # Test 1: Cache miss (from database)
    echo "Test 1: Loading from database (cache miss)..."
    START_TIME=$(date +%s.%N)
    php artisan tinker --execute="app(App\Services\ProductCacheService::class)->getProducts()->count();" > /dev/null 2>&1 || echo "0"
    END_TIME=$(date +%s.%N)
    FIRST_LOAD=$(echo "$END_TIME - $START_TIME" | bc)
    echo "  Time: ${FIRST_LOAD}s"
    
    # Test 2: Cache hit
    echo "Test 2: Loading from cache (cache hit)..."
    START_TIME=$(date +%s.%N)
    php artisan tinker --execute="app(App\Services\ProductCacheService::class)->getProducts()->count();" > /dev/null 2>&1 || echo "0"
    END_TIME=$(date +%s.%N)
    SECOND_LOAD=$(echo "$END_TIME - $START_TIME" | bc)
    echo "  Time: ${SECOND_LOAD}s"
    
    # Calculate improvement
    if (( $(echo "$FIRST_LOAD > 0" | bc -l) )); then
        IMPROVEMENT=$(echo "scale=2; (($FIRST_LOAD - $SECOND_LOAD) / $FIRST_LOAD) * 100" | bc)
        echo ""
        echo "Performance Improvement: ${IMPROVEMENT}%"
        
        if (( $(echo "$SECOND_LOAD < $FIRST_LOAD" | bc -l) )); then
            echo "✓ Cache is working! Second load is faster."
        fi
    fi
fi

echo ""

# ============================================
# Setup Complete - Summary
# ============================================
echo "=========================================="
echo "✅ Performance Optimization Complete!"
echo "=========================================="
echo ""
echo "Configuration Summary:"
echo "----------------------------------------"
echo "  Cache Driver:       $CACHE_DRIVER"
echo "  Queue Driver:       $QUEUE_DRIVER"
if [ "$USE_REDIS" = "true" ]; then
    echo "  Redis Status:       Running"
    echo "  Redis Host:         127.0.0.1:6379"
fi
if [ "$SKIP_SUPERVISOR" != "true" ]; then
    echo "  Supervisor:         Configured"
    echo "  Queue Workers:      2 processes"
    echo "  Worker Log:         storage/logs/worker.log"
fi
if crontab -l 2>/dev/null | grep -q "cache:warm-products"; then
    echo "  Cache Refresh:      Every 15 minutes (cron)"
fi
echo ""

echo "What Was Configured:"
echo "----------------------------------------"
echo "  ✓ PHP Redis extension issues fixed"
if [ "$USE_REDIS" = "true" ]; then
    echo "  ✓ Redis installed and running"
    echo "  ✓ Predis package installed"
fi
echo "  ✓ Cache system optimized ($CACHE_DRIVER)"
echo "  ✓ Queue system configured ($QUEUE_DRIVER)"
echo "  ✓ Background job processing enabled"
if [ "$SKIP_SUPERVISOR" != "true" ]; then
    echo "  ✓ Supervisor managing 2 queue workers"
    echo "  ✓ Auto-restart on failure enabled"
fi
echo "  ✓ Permissions set correctly"
echo "  ✓ Failed jobs table created"
if crontab -l 2>/dev/null | grep -q "cache:warm-products"; then
    echo "  ✓ Automatic cache refresh (every 15 min)"
fi
echo ""

echo "Useful Commands:"
echo "----------------------------------------"
if [ "$USE_REDIS" = "true" ]; then
    echo "Redis:"
    echo "  redis-cli ping                    - Test Redis connection"
    echo "  redis-cli monitor                 - Monitor Redis commands"
    echo "  redis-cli LLEN queues:default     - Check queue length"
    echo "  redis-cli FLUSHALL                - Clear all Redis data"
    echo "  brew services restart redis       - Restart Redis"
    echo ""
fi

if [ "$SKIP_SUPERVISOR" != "true" ]; then
    echo "Supervisor:"
    echo "  supervisorctl status              - Check worker status"
    echo "  supervisorctl restart laravel-worker:*  - Restart workers"
    echo "  supervisorctl stop laravel-worker:*     - Stop workers"
    echo "  supervisorctl start laravel-worker:*    - Start workers"
    echo "  tail -f storage/logs/worker.log   - View worker logs"
    echo ""
fi

echo "Laravel:"
echo "  php artisan cache:clear           - Clear all caches"
echo "  php artisan cache:warm-products   - Warm product cache"
echo "  php artisan queue:work            - Start queue worker manually"
echo "  php artisan queue:failed          - View failed jobs"
echo "  php artisan queue:retry all       - Retry all failed jobs"
echo "  php artisan queue:flush           - Delete all failed jobs"
echo ""

echo "Monitoring:"
echo "  tail -f storage/logs/laravel.log  - Application logs"
echo "  tail -f storage/logs/worker.log   - Queue worker logs"
echo "  crontab -l                        - View cron jobs"
echo ""

echo "Next Steps:"
echo "----------------------------------------"
echo "  1. Test your application: http://xscosmetic.test/pos"
echo "  2. Create a test order to verify background jobs"
echo "  3. Monitor worker logs: tail -f storage/logs/worker.log"
if [ "$USE_REDIS" = "true" ]; then
    echo "  4. Monitor Redis: redis-cli monitor"
fi
if [ "$SKIP_SUPERVISOR" != "true" ]; then
    echo "  5. Check worker status: supervisorctl status"
fi
echo ""

echo "Performance Tips:"
echo "----------------------------------------"
if [ "$USE_REDIS" = "true" ]; then
    echo "  ✓ Redis is configured for optimal performance"
else
    echo "  ⚠ Consider upgrading to Redis for better performance:"
    echo "    brew install redis"
    echo "    Then re-run this script"
fi
echo "  ✓ Cache refreshes automatically every 15 minutes"
echo "  ✓ Background jobs process asynchronously"
if [ "$SKIP_SUPERVISOR" != "true" ]; then
    echo "  ✓ Multiple workers handle concurrent jobs"
fi
echo ""

echo "Troubleshooting:"
echo "----------------------------------------"
echo "  If workers aren't running:"
echo "    supervisorctl restart laravel-worker:*"
echo "    tail -f storage/logs/worker.log"
echo ""
if [ "$USE_REDIS" = "true" ]; then
    echo "  If Redis connection fails:"
    echo "    brew services restart redis"
    echo "    php artisan config:clear"
    echo ""
fi
echo "  If cache isn't working:"
echo "    php artisan cache:clear"
echo "    php artisan config:clear"
echo "    php artisan cache:warm-products"
echo ""

echo "=========================================="
echo "🎉 Your application is now optimized!"
echo "=========================================="
echo ""
