#!/bin/bash

# Diagnose Queue Worker Issues

echo "=========================================="
echo "Queue Worker Diagnostics"
echo "=========================================="
echo ""

cd /Users/rana/Desktop/Rana/xscosmetic

# Check 1: Redis
echo "1. Checking Redis..."
if redis-cli ping > /dev/null 2>&1; then
    echo "   ✓ Redis is running"
else
    echo "   ❌ Redis is NOT running"
    echo "   Fix: brew services start redis"
fi
echo ""

# Check 2: PHP
echo "2. Checking PHP..."
if php -v > /dev/null 2>&1; then
    echo "   ✓ PHP is working"
    php -v | head -n 1
else
    echo "   ❌ PHP has errors"
    php -v
fi
echo ""

# Check 3: Laravel
echo "3. Checking Laravel..."
if php artisan --version > /dev/null 2>&1; then
    echo "   ✓ Laravel is working"
    php artisan --version
else
    echo "   ❌ Laravel has errors"
    php artisan --version
fi
echo ""

# Check 4: Queue Configuration
echo "4. Checking Queue Configuration..."
QUEUE_CONN=$(php artisan tinker --execute="echo config('queue.default');" 2>&1 | tail -n 1)
echo "   Queue Connection: $QUEUE_CONN"

if [ "$QUEUE_CONN" = "redis" ]; then
    echo "   ✓ Queue configured for Redis"
else
    echo "   ⚠ Queue is NOT configured for Redis"
fi
echo ""

# Check 5: Redis Connection from Laravel
echo "5. Testing Laravel Redis Connection..."
REDIS_TEST=$(php artisan tinker --execute="try { Redis::connection()->ping(); echo 'SUCCESS'; } catch (\Exception \$e) { echo 'FAILED: ' . \$e->getMessage(); }" 2>&1 | grep -E "SUCCESS|FAILED")
echo "   Result: $REDIS_TEST"
echo ""

# Check 6: Permissions
echo "6. Checking Permissions..."
if [ -w "storage/logs" ]; then
    echo "   ✓ storage/logs is writable"
else
    echo "   ❌ storage/logs is NOT writable"
    echo "   Fix: chmod -R 775 storage/logs"
fi
echo ""

# Check 7: Worker Logs
echo "7. Recent Worker Logs:"
echo "   ----------------------------------------"
if [ -f "storage/logs/worker.log" ]; then
    tail -n 20 storage/logs/worker.log
else
    echo "   No worker.log file found"
fi
echo "   ----------------------------------------"
echo ""

# Check 8: Supervisor Error
echo "8. Supervisor Error Output:"
echo "   ----------------------------------------"
supervisorctl tail laravel-worker stderr 2>&1 | tail -n 20
echo "   ----------------------------------------"
echo ""

# Check 9: Test Queue Command
echo "9. Testing Queue Command Manually..."
echo "   Running: php artisan queue:work redis --once"
echo "   ----------------------------------------"
timeout 10 php artisan queue:work redis --once 2>&1 || echo "   (Timed out or no jobs)"
echo "   ----------------------------------------"
echo ""

# Summary
echo "=========================================="
echo "Diagnostic Summary"
echo "=========================================="
echo ""
echo "Next Steps:"
echo "1. Check the errors above"
echo "2. Fix any issues found"
echo "3. Restart worker: supervisorctl restart laravel-worker:*"
echo ""
echo "Common Fixes:"
echo "  - Redis not running: brew services start redis"
echo "  - Permissions: chmod -R 775 storage/logs"
echo "  - Clear config: php artisan config:clear"
echo ""
