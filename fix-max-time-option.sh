#!/bin/bash

# Fix --max-time option issue
# Remove --max-time from Supervisor config

echo "=========================================="
echo "Fix --max-time Option Issue"
echo "=========================================="
echo ""

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$SCRIPT_DIR"

# Get PHP path
PHP_PATH=$(which php)
echo "PHP: $PHP_PATH"
echo ""

# Find supervisor config
SUPERVISOR_CONF=""

if [ -f "/usr/local/etc/supervisor.d/laravel-worker.ini" ]; then
    SUPERVISOR_CONF="/usr/local/etc/supervisor.d/laravel-worker.ini"
elif [ -f "/opt/homebrew/etc/supervisor.d/laravel-worker.ini" ]; then
    SUPERVISOR_CONF="/opt/homebrew/etc/supervisor.d/laravel-worker.ini"
elif [ -f "/etc/supervisor/conf.d/laravel-worker.ini" ]; then
    SUPERVISOR_CONF="/etc/supervisor/conf.d/laravel-worker.ini"
else
    # Create in default location
    sudo mkdir -p /usr/local/etc/supervisor.d
    SUPERVISOR_CONF="/usr/local/etc/supervisor.d/laravel-worker.ini"
fi

echo "Config: $SUPERVISOR_CONF"
echo ""

# Create config WITHOUT --max-time option
echo "Creating fixed configuration..."

sudo tee "$SUPERVISOR_CONF" > /dev/null <<EOF
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=$PHP_PATH $SCRIPT_DIR/artisan queue:work redis --sleep=3 --tries=3
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

echo "✓ Configuration updated (removed --max-time)"
echo ""

# Reload Supervisor
echo "Reloading Supervisor..."
supervisorctl reread
supervisorctl update
echo ""

# Stop existing worker
echo "Stopping existing worker..."
supervisorctl stop laravel-worker:* 2>/dev/null || true
sleep 1

# Start worker
echo "Starting queue worker..."
supervisorctl start laravel-worker:laravel-worker_00 2>/dev/null || supervisorctl start laravel-worker:* 2>/dev/null
sleep 3

# Check status
echo ""
echo "=========================================="
echo "Worker Status:"
echo "=========================================="
supervisorctl status

echo ""
echo "✅ Fix complete!"
echo ""
echo "Worker should be RUNNING now."
echo "Monitor: tail -f storage/logs/worker.log"
echo ""
