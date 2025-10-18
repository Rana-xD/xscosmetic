#!/bin/bash

# ============================================
# Quick Fix for Supervisor PHP Path Issue
# ============================================

echo "=========================================="
echo "Supervisor PHP Path Fix"
echo "=========================================="
echo ""

# Get current directory
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Get PHP path
PHP_PATH=$(which php)
PHP_DIR=$(dirname "$PHP_PATH")

echo "Detected PHP: $PHP_PATH"
echo "PHP Directory: $PHP_DIR"
echo ""

# Find supervisor config directory
if [ -d "/usr/local/etc/supervisor.d" ]; then
    SUPERVISOR_DIR="/usr/local/etc/supervisor.d"
elif [ -d "/opt/homebrew/etc/supervisor.d" ]; then
    SUPERVISOR_DIR="/opt/homebrew/etc/supervisor.d"
else
    echo "❌ Error: Cannot find supervisor config directory"
    exit 1
fi

WORKER_CONFIG="$SUPERVISOR_DIR/laravel-worker.ini"

echo "Supervisor config: $WORKER_CONFIG"
echo ""

# Check if config exists
if [ ! -f "$WORKER_CONFIG" ]; then
    echo "❌ Error: Laravel worker config not found"
    echo "Please run setup-performance-optimization.sh first"
    exit 1
fi

# Backup existing config
echo "Backing up existing config..."
sudo cp "$WORKER_CONFIG" "$WORKER_CONFIG.backup.$(date +%Y%m%d_%H%M%S)"
echo "✓ Backup created"
echo ""

# Create new config with correct paths
echo "Creating updated configuration..."

QUEUE_DRIVER=$(grep "^QUEUE_CONNECTION=" .env | cut -d '=' -f2)
if [ -z "$QUEUE_DRIVER" ]; then
    QUEUE_DRIVER="redis"
fi

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
environment=PATH="$PHP_DIR:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:/opt/homebrew/bin",HOME="$HOME"
EOF

echo "✓ Configuration updated"
echo ""

# Reload supervisor
echo "Reloading Supervisor..."
supervisorctl reread
supervisorctl update

echo ""
echo "Restarting workers..."
supervisorctl restart laravel-worker:* 2>/dev/null || supervisorctl start laravel-worker:*

sleep 3

echo ""
echo "Checking status..."
supervisorctl status laravel-worker:*

echo ""
echo "=========================================="
echo "Fix Complete!"
echo "=========================================="
echo ""
echo "Check worker logs:"
echo "  tail -f storage/logs/worker.log"
echo ""
echo "Check supervisor status:"
echo "  supervisorctl status"
echo ""
