#!/bin/bash

# Redis Setup Script for XS Cosmetic
# This script installs and configures Redis for queue and cache

echo "=========================================="
echo "Redis Setup for XS Cosmetic"
echo "=========================================="
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "✓ Working directory: $SCRIPT_DIR"
echo ""

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo "❌ Error: artisan file not found. Are you in the Laravel project root?"
    exit 1
fi

# ============================================
# STEP 1: Check if Redis is installed
# ============================================
echo "Step 1: Checking Redis installation..."
echo ""

if command -v redis-server &> /dev/null; then
    echo "✓ Redis is already installed"
    REDIS_VERSION=$(redis-server --version | head -n 1)
    echo "  Version: $REDIS_VERSION"
else
    echo "⚠ Redis is not installed"
    echo ""
    read -p "Do you want to install Redis now? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Installing Redis via Homebrew..."
        brew install redis
        echo "✓ Redis installed successfully"
    else
        echo "❌ Redis installation skipped. Cannot continue without Redis."
        exit 1
    fi
fi

echo ""

# ============================================
# STEP 2: Start Redis Service
# ============================================
echo "Step 2: Starting Redis service..."
echo ""

# Check if Redis is already running
if pgrep -x "redis-server" > /dev/null; then
    echo "✓ Redis is already running"
else
    echo "Starting Redis service..."
    brew services start redis
    
    # Wait a moment for Redis to start
    sleep 2
    
    # Verify Redis is running
    if pgrep -x "redis-server" > /dev/null; then
        echo "✓ Redis service started successfully"
    else
        echo "⚠ Warning: Redis may not have started properly"
    fi
fi

echo ""

# ============================================
# STEP 3: Test Redis Connection
# ============================================
echo "Step 3: Testing Redis connection..."
echo ""

if redis-cli ping > /dev/null 2>&1; then
    echo "✓ Redis connection successful (PONG received)"
else
    echo "❌ Error: Cannot connect to Redis"
    echo "   Try: brew services restart redis"
    exit 1
fi

echo ""

# ============================================
# STEP 4: Install Predis Package (Recommended)
# ============================================
echo "Step 4: Installing Predis package..."
echo ""

if grep -q "predis/predis" composer.json; then
    echo "✓ Predis package is already in composer.json"
else
    echo "Installing Predis package (pure PHP Redis client)..."
    composer require predis/predis
    echo "✓ Predis package installed"
fi

echo ""

# ============================================
# STEP 5: Configure Redis Client
# ============================================
echo "Step 5: Configuring Redis client..."
echo ""

# Set Redis client to predis
if grep -q "^REDIS_CLIENT=" .env; then
    sed -i.bak 's/^REDIS_CLIENT=.*/REDIS_CLIENT=predis/' .env
    echo "✓ Updated REDIS_CLIENT=predis"
else
    echo "REDIS_CLIENT=predis" >> .env
    echo "✓ Added REDIS_CLIENT=predis"
fi

echo ""

# ============================================
# STEP 6: Configure .env for Redis
# ============================================
echo "Step 6: Configuring .env for Redis..."
echo ""

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found"
    exit 1
fi

# Backup .env
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)
echo "✓ Created .env backup"

# Configure QUEUE_CONNECTION
if grep -q "^QUEUE_CONNECTION=" .env; then
    sed -i.bak 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' .env
    echo "✓ Updated QUEUE_CONNECTION=redis"
else
    echo "" >> .env
    echo "QUEUE_CONNECTION=redis" >> .env
    echo "✓ Added QUEUE_CONNECTION=redis"
fi

# Configure CACHE_DRIVER
if grep -q "^CACHE_DRIVER=" .env; then
    sed -i.bak 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' .env
    echo "✓ Updated CACHE_DRIVER=redis"
else
    echo "CACHE_DRIVER=redis" >> .env
    echo "✓ Added CACHE_DRIVER=redis"
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

echo ""

# ============================================
# STEP 7: Clear Laravel Caches
# ============================================
echo "Step 7: Clearing Laravel caches..."
echo ""

php artisan config:clear
php artisan cache:clear
php artisan view:clear

echo "✓ Caches cleared"
echo ""

# ============================================
# STEP 8: Test Laravel Redis Connection
# ============================================
echo "Step 8: Testing Laravel Redis connection..."
echo ""

# Test Redis connection via Laravel
TEST_RESULT=$(php artisan tinker --execute="try { Redis::connection()->ping(); echo 'SUCCESS'; } catch (\Exception \$e) { echo 'FAILED: ' . \$e->getMessage(); }" 2>&1)

if echo "$TEST_RESULT" | grep -q "SUCCESS"; then
    echo "✓ Laravel can connect to Redis successfully"
else
    echo "⚠ Warning: Laravel Redis connection test failed"
    echo "   $TEST_RESULT"
fi

echo ""

# ============================================
# STEP 9: Update Supervisor Configuration (if exists)
# ============================================
echo "Step 9: Updating Supervisor configuration..."
echo ""

SUPERVISOR_CONF="/usr/local/etc/supervisor.d/laravel-worker.ini"

if [ -f "$SUPERVISOR_CONF" ]; then
    echo "Updating Supervisor config to use Redis..."
    
    sudo tee "$SUPERVISOR_CONF" > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $SCRIPT_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$(whoami)
numprocs=1
redirect_stderr=true
stdout_logfile=$SCRIPT_DIR/storage/logs/worker.log
stopwaitsecs=3600
EOF
    
    # Reload supervisor
    if command -v supervisorctl &> /dev/null; then
        supervisorctl reread
        supervisorctl update
        supervisorctl restart laravel-worker:*
        echo "✓ Supervisor configuration updated and worker restarted"
    fi
else
    echo "⚠ Supervisor not configured"
    echo "   To start queue worker manually:"
    echo "   php artisan queue:work redis --tries=3"
fi

echo ""

# ============================================
# STEP 10: Verification
# ============================================
echo "Step 10: Final verification..."
echo ""

# Check Redis is running
if pgrep -x "redis-server" > /dev/null; then
    echo "✓ Redis service is running"
else
    echo "❌ Redis service is not running"
fi

# Check queue configuration
QUEUE_CONFIG=$(php artisan tinker --execute="echo config('queue.default');" 2>&1 | tail -n 1)
if echo "$QUEUE_CONFIG" | grep -q "redis"; then
    echo "✓ Queue configured to use Redis"
else
    echo "⚠ Queue configuration: $QUEUE_CONFIG"
fi

# Check cache configuration
CACHE_CONFIG=$(php artisan tinker --execute="echo config('cache.default');" 2>&1 | tail -n 1)
if echo "$CACHE_CONFIG" | grep -q "redis"; then
    echo "✓ Cache configured to use Redis"
else
    echo "⚠ Cache configuration: $CACHE_CONFIG"
fi

echo ""

# ============================================
# Setup Complete
# ============================================
echo "=========================================="
echo "✅ Redis Setup Complete!"
echo "=========================================="
echo ""
echo "What was configured:"
echo "  ✓ Redis installed and running"
echo "  ✓ PHP Redis extension (or Predis package)"
echo "  ✓ QUEUE_CONNECTION=redis"
echo "  ✓ CACHE_DRIVER=redis"
echo "  ✓ Redis connection settings"
echo "  ✓ Laravel caches cleared"
if [ -f "$SUPERVISOR_CONF" ]; then
    echo "  ✓ Supervisor updated for Redis queue"
fi
echo ""
echo "Redis Information:"
echo "  Host: 127.0.0.1"
echo "  Port: 6379"
echo "  Status: $(redis-cli ping 2>/dev/null || echo 'Not responding')"
echo ""
echo "Next Steps:"
echo "  1. Test queue: Create/delete an order"
echo "  2. Monitor Redis: redis-cli monitor"
echo "  3. Check queue: redis-cli LLEN queues:default"
echo "  4. View logs: tail -f storage/logs/laravel.log"
echo ""
echo "Useful Commands:"
echo "  redis-cli ping                    - Test Redis connection"
echo "  redis-cli monitor                 - Monitor Redis commands"
echo "  redis-cli LLEN queues:default     - Check queue length"
echo "  redis-cli FLUSHALL                - Clear all Redis data"
echo "  brew services restart redis       - Restart Redis service"
echo "  supervisorctl status              - Check queue worker status"
echo ""
echo "Queue Worker:"
if command -v supervisorctl &> /dev/null && [ -f "$SUPERVISOR_CONF" ]; then
    echo "  ✓ Managed by Supervisor (auto-starts on boot)"
    echo "  Status: $(supervisorctl status laravel-worker 2>/dev/null | awk '{print $2}')"
else
    echo "  Start manually: php artisan queue:work redis --tries=3"
fi
echo ""
echo "=========================================="
echo "🎉 Redis is ready to use!"
echo "=========================================="
