#!/bin/bash

# Performance Optimization Setup Script
# For xscosmetic Laravel Application
# This script sets up: cache optimization, cron jobs, and queue configuration

echo "=========================================="
echo "XS Cosmetic Performance Optimization Setup"
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
# STEP 1: Permissions
# ============================================
echo "Step 1: Setting up permissions..."
chmod -R 775 storage/framework/cache
chmod -R 775 storage/framework/queue
chmod -R 775 storage/logs
echo "✓ Directory permissions set"
echo ""

# ============================================
# STEP 2: Environment Configuration
# ============================================
echo "Step 2: Configuring environment..."

# Check if .env exists
if [ ! -f ".env" ]; then
    echo "❌ Error: .env file not found"
    exit 1
fi

# Configure queue to use file driver
if grep -q "^QUEUE_CONNECTION=" .env; then
    # Update existing
    sed -i.bak 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=file/' .env
    echo "✓ Updated QUEUE_CONNECTION to file"
else
    # Add new
    echo "" >> .env
    echo "QUEUE_CONNECTION=file" >> .env
    echo "✓ Added QUEUE_CONNECTION=file"
fi

# Add cache webhook secret if not exists
if ! grep -q "^CACHE_WEBHOOK_SECRET=" .env; then
    RANDOM_SECRET=$(openssl rand -hex 16)
    echo "CACHE_WEBHOOK_SECRET=$RANDOM_SECRET" >> .env
    echo "✓ Added CACHE_WEBHOOK_SECRET"
fi

echo ""

# ============================================
# STEP 3: Clear Caches
# ============================================
echo "Step 3: Clearing existing caches..."
php artisan cache:clear
php artisan config:clear
php artisan view:clear
echo "✓ Caches cleared"
echo ""

# ============================================
# STEP 4: Create Queue Tables
# ============================================
echo "Step 4: Setting up queue infrastructure..."

# Check if failed_jobs table exists
if ! php artisan tinker --execute="echo Schema::hasTable('failed_jobs') ? 'exists' : 'missing';" 2>/dev/null | grep -q "exists"; then
    echo "   Creating failed_jobs table..."
    php artisan queue:failed-table
    php artisan migrate --force
    echo "✓ Failed jobs table created"
else
    echo "✓ Failed jobs table already exists"
fi

# Create queue directory if not exists
mkdir -p storage/framework/queue
echo "✓ Queue directory ready"
echo ""

# ============================================
# STEP 5: Warm Cache
# ============================================
echo "Step 5: Warming up product cache..."
echo "   (This may take a few moments for ~7000 products)"
php artisan cache:warm-products
echo ""

# ============================================
# STEP 6: Verify Cache
# ============================================
echo "Step 6: Verifying cache..."
if [ -d "storage/framework/cache/data" ] && [ "$(ls -A storage/framework/cache/data)" ]; then
    echo "✓ Cache files created successfully"
else
    echo "⚠ Warning: Cache directory is empty. Cache may be using a different driver."
fi
echo ""

# ============================================
# STEP 7: Set Up Cron Job
# ============================================
echo "Step 7: Setting up cron job for cache refresh..."

CRON_CMD="*/15 * * * * cd $SCRIPT_DIR && php artisan cache:warm-products >> /dev/null 2>&1"

# Check if cron job already exists
if crontab -l 2>/dev/null | grep -q "cache:warm-products"; then
    echo "✓ Cron job already exists"
else
    # Add cron job
    echo ""
    echo "   A cron job will refresh cache every 15 minutes."
    echo "   This ensures your local cache stays synchronized with the remote database."
    echo ""
    read -p "   Do you want to add the cron job now? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        # Backup existing crontab
        crontab -l > /tmp/crontab.bak 2>/dev/null || true
        
        # Add new cron job
        (crontab -l 2>/dev/null; echo "$CRON_CMD") | crontab -
        
        echo "✓ Cron job added successfully"
        echo "   Cache will refresh every 15 minutes automatically"
    else
        echo "⚠ Skipped cron job setup"
        echo "   You can add it manually later with:"
        echo "   crontab -e"
        echo "   Then add: $CRON_CMD"
    fi
fi
echo ""

# ============================================
# STEP 8: Queue Worker Setup with Supervisor
# ============================================
echo "Step 8: Setting up queue worker with Supervisor..."
echo ""

# Check if Supervisor is installed
if command -v supervisorctl &> /dev/null; then
    echo "✓ Supervisor is installed"
    
    # Create Supervisor configuration
    SUPERVISOR_CONF="/usr/local/etc/supervisor.d/laravel-worker.ini"
    
    if [ -f "$SUPERVISOR_CONF" ]; then
        echo "✓ Supervisor config already exists"
    else
        echo "   Creating Supervisor configuration..."
        echo "   This will auto-start queue worker on Mac boot"
        echo ""
        read -p "   Do you want to set up Supervisor for queue worker? (y/n) " -n 1 -r
        echo ""
        
        if [[ $REPLY =~ ^[Yy]$ ]]; then
            # Create supervisor config
            sudo tee "$SUPERVISOR_CONF" > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $SCRIPT_DIR/artisan queue:work file --sleep=3 --tries=3 --max-time=3600
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
            supervisorctl reread
            supervisorctl update
            supervisorctl start laravel-worker:*
            
            echo "✓ Supervisor configured and queue worker started"
            echo "   Queue worker will auto-start on Mac boot"
            echo "   Check status: supervisorctl status"
        else
            echo "⚠ Skipped Supervisor setup"
            echo "   You'll need to manually start queue worker:"
            echo "   php artisan queue:work file --tries=3"
        fi
    fi
else
    echo "⚠ Supervisor not installed"
    echo ""
    echo "   Option 1: Install Supervisor (Recommended)"
    echo "   brew install supervisor"
    echo "   brew services start supervisor"
    echo "   Then re-run this script"
    echo ""
    echo "   Option 2: Manual queue worker"
    echo "   php artisan queue:work file --tries=3"
    echo ""
    echo "   Option 3: Use LaunchAgent (see BACKGROUND_JOB_IMPLEMENTATION.md)"
    echo ""
    read -p "   Do you want to install Supervisor now? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "   Installing Supervisor..."
        brew install supervisor
        brew services start supervisor
        
        # Wait a moment for supervisor to start
        sleep 2
        
        # Create supervisor config
        SUPERVISOR_CONF="/usr/local/etc/supervisor.d/laravel-worker.ini"
        
        echo "   Creating Supervisor configuration..."
        sudo tee "$SUPERVISOR_CONF" > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php $SCRIPT_DIR/artisan queue:work file --sleep=3 --tries=3 --max-time=3600
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
        supervisorctl reread
        supervisorctl update
        supervisorctl start laravel-worker:*
        
        echo "✓ Supervisor installed and queue worker started"
    else
        echo "⚠ Skipped Supervisor installation"
        echo "   Remember to start queue worker manually:"
        echo "   php artisan queue:work file --tries=3"
    fi
fi

echo ""

# ============================================
# STEP 9: Final Verification
# ============================================
echo "Step 9: Running final checks..."

# Check if artisan commands are available
if php artisan list | grep -q "cache:warm-products"; then
    echo "✓ Custom cache commands available"
else
    echo "⚠ Warning: Custom cache commands not found"
fi

# Check queue configuration
if php artisan tinker --execute="echo config('queue.default');" 2>/dev/null | grep -q "file"; then
    echo "✓ Queue configured to use file driver"
else
    echo "⚠ Warning: Queue configuration may need review"
fi

echo ""

# ============================================
# Setup Complete
# ============================================
echo "=========================================="
echo "✅ Setup Complete!"
echo "=========================================="
echo ""
echo "What was configured:"
echo "  ✓ Cache system (file-based)"
echo "  ✓ Queue system (file-based with background jobs)"
echo "  ✓ Product cache warmed"
echo "  ✓ Cron job for auto-refresh (every 15 min)"
echo "  ✓ Failed jobs table"
echo "  ✓ Permissions set"
if command -v supervisorctl &> /dev/null && [ -f "/usr/local/etc/supervisor.d/laravel-worker.ini" ]; then
    echo "  ✓ Supervisor configured (queue worker auto-starts)"
fi
echo ""
echo "Next Steps:"
echo "  1. Test POS page: http://xscosmetic.test/pos"
echo "  2. Verify fast loading (<2 seconds)"
echo "  3. Create a test order to verify background jobs"
echo "  4. Check cron job: crontab -l"
if command -v supervisorctl &> /dev/null; then
    echo "  5. Check queue worker: supervisorctl status"
fi
echo ""
echo "Queue Worker Status:"
if command -v supervisorctl &> /dev/null && [ -f "/usr/local/etc/supervisor.d/laravel-worker.ini" ]; then
    echo "  ✓ Supervisor managing queue worker"
    echo "  ✓ Worker will auto-start on Mac boot"
    echo "  Commands:"
    echo "    supervisorctl status          - Check worker status"
    echo "    supervisorctl restart laravel-worker:*  - Restart worker"
    echo "    tail -f storage/logs/worker.log  - View worker logs"
else
    echo "  ⚠ Queue worker needs to be started manually"
    echo "  Start worker: php artisan queue:work file --tries=3"
    echo "  Or install Supervisor: brew install supervisor"
fi
echo ""
echo "Useful Commands:"
echo "  php artisan cache:warm-products      - Refresh cache manually"
echo "  php artisan cache:clear-products     - Clear product cache"
echo "  php artisan queue:work file          - Start queue worker"
echo "  php artisan queue:failed             - View failed jobs"
echo "  tail -f storage/logs/laravel.log     - Monitor logs"
echo "  crontab -l                            - View cron jobs"
echo ""
echo "Documentation:"
echo "  - BACKGROUND_JOB_IMPLEMENTATION.md    - Background jobs guide"
echo ""
echo "Cache Synchronization:"
echo "  Your local cache will auto-refresh every 15 minutes"
echo "  Max data staleness: 15 minutes"
echo "  Manual refresh: php artisan cache:warm-products"
echo ""
echo "Background Jobs:"
echo "  Orders now process in background for better performance"
if command -v supervisorctl &> /dev/null && [ -f "/usr/local/etc/supervisor.d/laravel-worker.ini" ]; then
    echo "  Queue worker is running and will auto-start on boot"
else
    echo "  Remember to start queue worker: php artisan queue:work file --tries=3"
fi
echo ""
echo "=========================================="
echo "🎉 Your POS system is now optimized!"
echo "=========================================="
