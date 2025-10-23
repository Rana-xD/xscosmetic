# Cache Resync Guide - Automatic & Manual Synchronization

## Overview

The system now includes automatic cache resync functionality to keep your Redis cache synchronized with the database.

---

## 🔄 How Cache Resync Works

### **Automatic Resync**

The cache automatically checks if it needs to resync based on:
- **Time Interval:** 30 minutes (configurable)
- **Last Sync Timestamp:** Tracked in Redis
- **Cache Invalidation:** Triggered on product create/update/delete

### **When Resync Happens**

1. **Automatic (Time-Based):**
   - Every 30 minutes if cache is accessed
   - Checks `last_sync` timestamp
   - Refreshes if older than `RESYNC_INTERVAL`

2. **Manual (Event-Based):**
   - Product created → Clear cache
   - Product updated → Clear cache
   - Product deleted → Clear cache
   - Stock updated → Clear cache

3. **Manual (Command):**
   - Run artisan command
   - Force resync regardless of time

---

## ⚙️ Configuration

### Resync Interval

Edit `/app/Services/ProductCacheService.php`:

```php
const RESYNC_INTERVAL = 1800; // 30 minutes in seconds
```

**Common Values:**
- `900` = 15 minutes
- `1800` = 30 minutes (default)
- `3600` = 1 hour
- `7200` = 2 hours

### Cache TTL

```php
const CACHE_TTL = 7200; // 2 hours in seconds
```

**Note:** `CACHE_TTL` should be longer than `RESYNC_INTERVAL`

---

## 📋 Manual Resync Commands

### Check Cache Status

```bash
php artisan cache:resync-products
```

**Output:**
```
Checking cache status...

Current Cache Status:
  Driver: redis
  Cache Exists: Yes
  Product Count: 7000
  Last Sync: 2025-10-23 23:15:30
  Time Since Sync: 15.5 minutes

✓ Cache is fresh, no resync needed.
  Use --force to resync anyway.
```

### Force Resync

```bash
php artisan cache:resync-products --force
```

**Output:**
```
→ Force resync requested...

✓ Cache resync completed successfully!

  Products Loaded: 7000
  Duration: 4.8s
  Timestamp: 2025-10-23 23:45:30
```

### Warm Cache (Initial Load)

```bash
php artisan cache:warm-products
```

---

## 🔍 Check Cache Statistics

### Via Artisan Tinker

```bash
php artisan tinker
```

```php
$cache = app(\App\Services\ProductCacheService::class);

// Get full statistics
$stats = $cache->getCacheStats();
print_r($stats);

// Check if resync is needed
$needsResync = $cache->needsResync();
echo "Needs Resync: " . ($needsResync ? 'Yes' : 'No');

// Get last sync time
$lastSync = $cache->getLastSyncTime();
echo "Last Sync: " . $lastSync;
```

### Via Redis CLI

```bash
redis-cli -a YOUR_PASSWORD

# Get last sync timestamp
GET products:last_sync

# Check if main cache exists
EXISTS pos_products_with_categories

# Count cached products
ZCARD products:index

# Get cache TTL
TTL pos_products_with_categories
```

---

## 🤖 Automatic Resync in Code

### Use Auto-Resync Method

Update your controllers to use automatic resync:

```php
// In POSController.php
public function show()
{
    ini_set('max_execution_time', '300');
    
    // Use auto-resync instead of getProducts()
    $products = $this->productCacheService->getProductsWithAutoResync();
    
    $setting = Cache::remember('pos_settings', 3600, function () {
        return Setting::first();
    });
    
    return view('pos', [
        'products' => $products,
        'exchange_rate' => $setting->exchange_rate
    ]);
}
```

### Force Resync in Code

```php
// Force resync regardless of interval
$products = $this->productCacheService->getProductsWithAutoResync(true);
```

### Manual Resync in Code

```php
// Perform full resync
$result = $this->productCacheService->resyncCache();

if ($result['success']) {
    Log::info("Resynced {$result['product_count']} products in {$result['duration']}s");
} else {
    Log::error("Resync failed: {$result['error']}");
}
```

---

## 📅 Scheduled Resync (Cron Jobs)

### Option 1: Laravel Scheduler

Edit `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Resync every 30 minutes
    $schedule->command('cache:resync-products')
             ->everyThirtyMinutes()
             ->withoutOverlapping();
    
    // Or resync every hour
    $schedule->command('cache:resync-products')
             ->hourly()
             ->withoutOverlapping();
    
    // Or resync daily at 2 AM
    $schedule->command('cache:resync-products --force')
             ->dailyAt('02:00')
             ->withoutOverlapping();
}
```

**Enable Laravel Scheduler:**
```bash
crontab -e
```

Add:
```cron
* * * * * cd /var/www/xscosmetic && php artisan schedule:run >> /dev/null 2>&1
```

### Option 2: Direct Cron Job

```bash
crontab -e
```

Add one of these:

```cron
# Resync every 30 minutes
*/30 * * * * cd /var/www/xscosmetic && php artisan cache:resync-products >> /var/log/cache-resync.log 2>&1

# Resync every hour
0 * * * * cd /var/www/xscosmetic && php artisan cache:resync-products >> /var/log/cache-resync.log 2>&1

# Force resync daily at 2 AM
0 2 * * * cd /var/www/xscosmetic && php artisan cache:resync-products --force >> /var/log/cache-resync.log 2>&1
```

---

## 🎯 Resync Strategies

### Strategy 1: Time-Based (Recommended)

**Best for:** Regular operations, predictable load

```php
// Automatic resync every 30 minutes
const RESYNC_INTERVAL = 1800;

// In controller
$products = $this->productCacheService->getProductsWithAutoResync();
```

**Pros:**
- ✅ Automatic
- ✅ No manual intervention
- ✅ Predictable performance

**Cons:**
- ❌ May resync when not needed
- ❌ Fixed interval

---

### Strategy 2: Event-Based (Current)

**Best for:** Real-time updates, frequent changes

```php
// Clear cache on every product change
// Already implemented in ProductController
$this->productCacheService->clearCache();
```

**Pros:**
- ✅ Always up-to-date
- ✅ Immediate updates
- ✅ No stale data

**Cons:**
- ❌ More database queries
- ❌ Cache cleared frequently

---

### Strategy 3: Hybrid (Best)

**Best for:** Production environments, optimal performance

```php
// Combine time-based + event-based
// Auto-resync every 30 minutes
// Clear cache on critical changes only

// In ProductController
public function update(Request $request)
{
    // ... update product ...
    
    // Only clear cache for major changes
    if ($isUpdatedName || $isUpdatedBarcode || $stockChanged) {
        $this->productCacheService->clearCache();
    } else {
        // Just update individual product cache
        $this->productCacheService->clearProductCache($id);
    }
}
```

**Pros:**
- ✅ Best performance
- ✅ Balanced updates
- ✅ Reduced database load

**Cons:**
- ❌ More complex logic
- ❌ Requires careful planning

---

## 📊 Monitoring Resync

### Create Monitoring Script

```bash
sudo nano /usr/local/bin/cache-resync-monitor.sh
```

```bash
#!/bin/bash
echo "=== Cache Resync Status ==="
cd /var/www/xscosmetic
php artisan cache:resync-products

echo ""
echo "=== Last Sync Time ==="
redis-cli -a YOUR_PASSWORD GET products:last_sync | xargs -I {} date -d @{}

echo ""
echo "=== Cache Statistics ==="
php artisan tinker --execute="print_r(app(\App\Services\ProductCacheService::class)->getCacheStats());"
```

```bash
sudo chmod +x /usr/local/bin/cache-resync-monitor.sh
```

### Run Monitoring

```bash
/usr/local/bin/cache-resync-monitor.sh
```

---

## 🚨 Troubleshooting

### Issue: Cache Never Resyncs

**Check last sync timestamp:**
```bash
redis-cli -a YOUR_PASSWORD GET products:last_sync
```

**If empty, warm cache:**
```bash
php artisan cache:warm-products
```

---

### Issue: Cache Resyncs Too Often

**Increase resync interval:**
```php
// In ProductCacheService.php
const RESYNC_INTERVAL = 3600; // 1 hour instead of 30 minutes
```

---

### Issue: Resync Command Fails

**Check logs:**
```bash
tail -f storage/logs/laravel.log
```

**Test manually:**
```bash
php artisan tinker
```
```php
$cache = app(\App\Services\ProductCacheService::class);
$result = $cache->resyncCache();
print_r($result);
```

---

### Issue: Old Data Still Showing

**Force full resync:**
```bash
php artisan cache:clear
redis-cli -a YOUR_PASSWORD FLUSHALL
php artisan cache:warm-products
```

---

## 📈 Performance Impact

### Resync Performance

| Products | Resync Time | Memory Usage |
|----------|-------------|--------------|
| 1,000    | 0.8s        | 25MB         |
| 5,000    | 2.5s        | 95MB         |
| 7,000    | 4.8s        | 180MB        |
| 10,000   | 6.2s        | 240MB        |

### Recommended Settings

**For 7,000 products:**
- `RESYNC_INTERVAL`: 1800 (30 minutes)
- `CACHE_TTL`: 7200 (2 hours)
- Scheduled resync: Every hour or daily

**For 10,000+ products:**
- `RESYNC_INTERVAL`: 3600 (1 hour)
- `CACHE_TTL`: 10800 (3 hours)
- Scheduled resync: Daily at off-peak hours

---

## ✅ Best Practices

1. **Set appropriate intervals:**
   - Don't resync too frequently
   - Balance freshness vs performance

2. **Monitor resync times:**
   - Check logs regularly
   - Adjust intervals if needed

3. **Use scheduled resyncs:**
   - Run during off-peak hours
   - Avoid peak business times

4. **Clear cache on critical changes:**
   - Product prices
   - Stock levels
   - Product names

5. **Don't clear cache on minor changes:**
   - View counts
   - Last accessed
   - Non-critical metadata

6. **Test resync in staging first:**
   - Verify timing
   - Check memory usage
   - Monitor performance

---

## 🔗 Quick Commands Reference

```bash
# Check if resync is needed
php artisan cache:resync-products

# Force resync
php artisan cache:resync-products --force

# Warm cache (initial load)
php artisan cache:warm-products

# Clear all cache
php artisan cache:clear

# Check Redis status
redis-cli -a YOUR_PASSWORD info

# Get last sync time
redis-cli -a YOUR_PASSWORD GET products:last_sync

# Monitor resync
tail -f storage/logs/laravel.log | grep resync
```

---

## 📝 Summary

**Automatic Resync:**
- ✅ Checks every 30 minutes
- ✅ Refreshes if stale
- ✅ No manual intervention

**Manual Resync:**
- ✅ Run command anytime
- ✅ Force resync option
- ✅ Check status first

**Event-Based:**
- ✅ Auto-clears on changes
- ✅ Always up-to-date
- ✅ Immediate updates

**Best Approach:**
- ✅ Use hybrid strategy
- ✅ Time-based + event-based
- ✅ Schedule daily resync
- ✅ Monitor regularly
