#!/bin/bash

# Fix Supervisor PHP Path Issue
# Updates the laravel-worker config to use absolute PHP path

echo "=========================================="
echo "Fix Supervisor PHP Path"
echo "=========================================="
echo ""

# Get the script directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Get full path to PHP
PHP_PATH=$(which php)
echo "PHP location: $PHP_PATH"
echo ""

# Find the supervisor config
SUPERVISOR_CONF=""

if [ -f "/usr/local/etc/supervisor.d/laravel-worker.ini" ]; then
    SUPERVISOR_CONF="/usr/local/etc/supervisor.d/laravel-worker.ini"
elif [ -f "/opt/homebrew/etc/supervisor.d/laravel-worker.ini" ]; then
    SUPERVISOR_CONF="/opt/homebrew/etc/supervisor.d/laravel-worker.ini"
elif [ -f "/etc/supervisor/conf.d/laravel-worker.ini" ]; then
    SUPERVISOR_CONF="/etc/supervisor/conf.d/laravel-worker.ini"
fi

if [ -z "$SUPERVISOR_CONF" ]; then
    echo "❌ Supervisor config not found!"
    echo ""
    echo "Creating new config..."
    
    # Determine directory
    if [ -d "/usr/local/etc/supervisor.d" ]; then
        SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
    elif [ -d "/opt/homebrew/etc/supervisor.d" ]; then
        SUPERVISOR_DIR="/opt/homebrew/etc/supervisor.d"
    else
        sudo mkdir -p /usr/local/etc/supervisor.d
        SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
    fi
    
    SUPERVISOR_CONF="$SUPERVISOR_DIR/laravel-worker.ini"
fi

echo "Config file: $SUPERVISOR_CONF"
echo ""

# Create/update the config with absolute PHP path
echo "Creating updated configuration..."

sudo tee "$SUPERVISOR_CONF" > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_PATH $SCRIPT_DIR/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=$(whoami)
numprocs=1
redirect_stderr=true
stdout_logfile=$SCRIPT_DIR/storage/logs/worker.log
stopwaitsecs=3600
environment=PATH="/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"
EOF

echo "✓ Configuration updated with absolute PHP path"
echo ""

# Reload Supervisor
echo "Reloading Supervisor..."
supervisorctl reread
supervisorctl update
echo "✓ Supervisor reloaded"
echo ""

# Stop any existing worker
echo "Stopping existing worker (if any)..."
supervisorctl stop laravel-worker:* 2>/dev/null || true
sleep 1

# Start the worker
echo "Starting queue worker..."
supervisorctl start laravel-worker:laravel-worker_00 2>/dev/null || supervisorctl start laravel-worker 2>/dev/null || supervisorctl start all
sleep 2

# Check status
echo ""
echo "=========================================="
echo "Worker Status:"
echo "=========================================="
supervisorctl status

echo ""
echo "✅ Fix complete!"
echo ""
echo "If worker is running, you're all set!"
echo "If not, check logs: tail -f storage/logs/worker.log"
echo ""
