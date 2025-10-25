<?php

/**
 * Check queue status and pending jobs
 * Run: php check-queue.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== QUEUE STATUS CHECK ===\n\n";

// 1. Check queue configuration
echo "1. Queue Configuration:\n";
echo "   Driver: " . config('queue.default') . "\n";
echo "   Connection: " . config('queue.connections.' . config('queue.default') . '.connection') . "\n\n";

// 2. Check Redis connection
echo "2. Redis Connection:\n";
try {
    $redis = \Illuminate\Support\Facades\Redis::connection();
    $redis->ping();
    echo "   Status: CONNECTED ✓\n";
} catch (\Exception $e) {
    echo "   Status: FAILED ✗\n";
    echo "   Error: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Check pending jobs in queue
echo "3. Pending Jobs:\n";
try {
    $queueDriver = config('queue.default');
    
    if ($queueDriver === 'redis') {
        // Check Redis queues
        $defaultQueue = config('queue.connections.redis.queue', 'default');
        
        // Check default queue
        $queueKey = 'queues:' . $defaultQueue;
        $pendingJobs = \Illuminate\Support\Facades\Redis::llen($queueKey);
        echo "   Queue '{$defaultQueue}': {$pendingJobs} pending jobs\n";
        
        // Check delayed queue
        $delayedKey = 'queues:' . $defaultQueue . ':delayed';
        $delayedJobs = \Illuminate\Support\Facades\Redis::zcard($delayedKey);
        echo "   Delayed jobs: {$delayedJobs}\n";
        
        // Check reserved queue
        $reservedKey = 'queues:' . $defaultQueue . ':reserved';
        $reservedJobs = \Illuminate\Support\Facades\Redis::zcard($reservedKey);
        echo "   Reserved jobs: {$reservedJobs}\n";
        
        if ($pendingJobs > 0) {
            echo "\n   ⚠️  WARNING: {$pendingJobs} jobs are waiting to be processed!\n";
            echo "   Queue worker may not be running.\n";
        }
    } else if ($queueDriver === 'database') {
        $pendingJobs = DB::table('jobs')->count();
        echo "   Pending jobs: {$pendingJobs}\n";
        
        if ($pendingJobs > 0) {
            echo "\n   ⚠️  WARNING: {$pendingJobs} jobs are waiting to be processed!\n";
        }
    } else if ($queueDriver === 'sync') {
        echo "   Driver: sync (jobs run immediately, no queue)\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 4. Check failed jobs
echo "4. Failed Jobs:\n";
try {
    $failedJobs = DB::table('failed_jobs')->count();
    echo "   Total failed: {$failedJobs}\n";
    
    if ($failedJobs > 0) {
        echo "   Recent failures:\n";
        $recent = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get(['id', 'queue', 'exception', 'failed_at']);
        
        foreach ($recent as $job) {
            echo "   - ID {$job->id}: {$job->queue} at {$job->failed_at}\n";
            $exceptionPreview = substr($job->exception, 0, 100);
            echo "     Error: {$exceptionPreview}...\n";
        }
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Check if queue worker is running
echo "5. Queue Worker Process:\n";
$workerProcesses = shell_exec("ps aux | grep 'queue:work' | grep -v grep");
if ($workerProcesses) {
    echo "   Status: RUNNING ✓\n";
    echo "   Processes:\n";
    $lines = explode("\n", trim($workerProcesses));
    foreach ($lines as $line) {
        if (!empty($line)) {
            echo "   " . $line . "\n";
        }
    }
} else {
    echo "   Status: NOT RUNNING ✗\n";
    echo "   ⚠️  Queue worker is not running!\n";
    echo "   POS orders will not be saved until worker is started.\n";
}
echo "\n";

// 6. Recent POS orders
echo "6. Recent POS Orders:\n";
try {
    $recentOrders = DB::table('pos')
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get(['id', 'order_no', 'cashier', 'created_at']);
    
    if ($recentOrders->count() > 0) {
        echo "   Last 5 orders:\n";
        foreach ($recentOrders as $order) {
            echo "   - Order #{$order->order_no} by {$order->cashier} at {$order->created_at}\n";
        }
    } else {
        echo "   No orders found\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== RECOMMENDATIONS ===\n\n";

if (!$workerProcesses) {
    echo "❌ Queue worker is NOT running!\n\n";
    echo "To fix this issue, run ONE of these commands:\n\n";
    echo "Option 1: Start queue worker (recommended for production):\n";
    echo "  sudo supervisorctl start laravel-worker\n\n";
    echo "Option 2: Run queue worker manually (for testing):\n";
    echo "  php artisan queue:work --tries=3 --timeout=600\n\n";
    echo "Option 3: Process queue once (temporary fix):\n";
    echo "  php artisan queue:work --once\n\n";
} else {
    echo "✅ Queue worker is running\n\n";
}

echo "=== END REPORT ===\n";
