#!/bin/bash

# Queue Worker Setup Script
# Sets up Supervisor to manage Laravel queue worker

echo "=========================================="
echo "Queue Worker Setup"
echo "=========================================="
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

echo "✓ Working directory: $SCRIPT_DIR"
echo ""

# ============================================
# STEP 1: Check if Supervisor is installed
# ============================================
echo "Step 1: Checking Supervisor installation..."
echo ""

if command -v supervisord &> /dev/null; then
    echo "✓ Supervisor is installed"
    supervisord -v
else
    echo "⚠ Supervisor is not installed"
    echo ""
    read -p "Do you want to install Supervisor now? (y/n) " -n 1 -r
    echo ""
    
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo "Installing Supervisor via Homebrew..."
        brew install supervisor
        echo "✓ Supervisor installed"
    else
        echo "❌ Cannot continue without Supervisor"
        exit 1
    fi
fi

echo ""

# ============================================
# STEP 2: Start Supervisor Service
# ============================================
echo "Step 2: Starting Supervisor service..."
echo ""

# Check if Supervisor is running
if pgrep -x "supervisord" > /dev/null; then
    echo "✓ Supervisor is already running"
else
    echo "Starting Supervisor service..."
    brew services start supervisor
    
    # Wait for it to start
    sleep 3
    
    if pgrep -x "supervisord" > /dev/null; then
        echo "✓ Supervisor started successfully"
    else
        echo "⚠ Warning: Supervisor may not have started"
        echo "   Trying to start manually..."
        supervisord -c /usr/local/etc/supervisord.ini &
        sleep 2
    fi
fi

echo ""

# ============================================
# STEP 3: Find Supervisor Config Directory
# ============================================
echo "Step 3: Finding Supervisor config directory..."
echo ""

# Try common locations
if [ -d "/usr/local/etc/supervisor.d" ]; then
    SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
    echo "✓ Found: $SUPERVISOR_DIR"
elif [ -d "/opt/homebrew/etc/supervisor.d" ]; then
    SUPERVISOR_DIR="/opt/homebrew/etc/supervisor.d"
    echo "✓ Found: $SUPERVISOR_DIR"
elif [ -d "/etc/supervisor/conf.d" ]; then
    SUPERVISOR_DIR="/etc/supervisor/conf.d"
    echo "✓ Found: $SUPERVISOR_DIR"
else
    echo "⚠ Config directory not found, creating it..."
    sudo mkdir -p /usr/local/etc/supervisor.d
    SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
    echo "✓ Created: $SUPERVISOR_DIR"
    
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

echo ""

# ============================================
# STEP 4: Create Laravel Worker Config
# ============================================
echo "Step 4: Creating Laravel worker configuration..."
echo ""

WORKER_CONFIG="$SUPERVISOR_DIR/laravel-worker.ini"

if [ -f "$WORKER_CONFIG" ]; then
    echo "⚠ Config already exists at: $WORKER_CONFIG"
    read -p "Do you want to overwrite it? (y/n) " -n 1 -r
    echo ""
    
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "Skipping config creation"
        SKIP_CONFIG=true
    fi
fi

if [ "$SKIP_CONFIG" != "true" ]; then
    sudo tee "$WORKER_CONFIG" > /dev/null <<EOF
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

    echo "✓ Created worker config at: $WORKER_CONFIG"
fi

echo ""

# ============================================
# STEP 5: Reload Supervisor
# ============================================
echo "Step 5: Reloading Supervisor configuration..."
echo ""

supervisorctl reread
supervisorctl update

echo "✓ Supervisor configuration reloaded"
echo ""

# ============================================
# STEP 6: Start Queue Worker
# ============================================
echo "Step 6: Starting queue worker..."
echo ""

# Try to start the worker
supervisorctl start laravel-worker:laravel-worker_00 2>/dev/null || supervisorctl start laravel-worker 2>/dev/null

sleep 2

echo "✓ Queue worker start command sent"
echo ""

# ============================================
# STEP 7: Verify Worker Status
# ============================================
echo "Step 7: Verifying worker status..."
echo ""

STATUS=$(supervisorctl status 2>&1)

if echo "$STATUS" | grep -q "laravel-worker"; then
    echo "✓ Laravel worker found in Supervisor"
    echo ""
    echo "$STATUS" | grep "laravel-worker"
    echo ""
    
    if echo "$STATUS" | grep "laravel-worker" | grep -q "RUNNING"; then
        echo "✅ Queue worker is RUNNING!"
    else
        echo "⚠ Queue worker is not running"
        echo "   Checking logs..."
        if [ -f "$SCRIPT_DIR/storage/logs/worker.log" ]; then
            tail -n 20 "$SCRIPT_DIR/storage/logs/worker.log"
        fi
    fi
else
    echo "⚠ Laravel worker not found in Supervisor status"
    echo ""
    echo "Full Supervisor status:"
    echo "$STATUS"
    echo ""
    echo "Trying alternative start method..."
    
    # Try starting with full name
    supervisorctl start all
    sleep 2
    supervisorctl status
fi

echo ""

# ============================================
# STEP 8: Test Queue
# ============================================
echo "Step 8: Testing queue connection..."
echo ""

# Check if Redis is accessible
if redis-cli ping > /dev/null 2>&1; then
    echo "✓ Redis is accessible"
    
    # Check queue length
    QUEUE_LENGTH=$(redis-cli LLEN queues:default 2>/dev/null || echo "0")
    echo "✓ Jobs in queue: $QUEUE_LENGTH"
else
    echo "⚠ Warning: Cannot connect to Redis"
fi

echo ""

# ============================================
# Setup Complete
# ============================================
echo "=========================================="
echo "✅ Queue Worker Setup Complete!"
echo "=========================================="
echo ""
echo "Configuration:"
echo "  Config file: $WORKER_CONFIG"
echo "  Log file: $SCRIPT_DIR/storage/logs/worker.log"
echo "  Queue: redis"
echo ""
echo "Useful Commands:"
echo "  supervisorctl status              - Check worker status"
echo "  supervisorctl restart laravel-worker:*  - Restart worker"
echo "  supervisorctl stop laravel-worker:*     - Stop worker"
echo "  supervisorctl start laravel-worker:*    - Start worker"
echo "  tail -f storage/logs/worker.log   - View worker logs"
echo "  redis-cli LLEN queues:default     - Check queue length"
echo ""
echo "Next Steps:"
echo "  1. Create a POS order to test"
echo "  2. Monitor: tail -f storage/logs/worker.log"
echo "  3. Check database for new records"
echo ""
echo "If worker is not running, try:"
echo "  supervisorctl restart all"
echo "  supervisorctl status"
echo ""
echo "=========================================="
