<?php

namespace App\Services;

use App\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class ProductCacheService
{
    const CACHE_KEY = 'pos_products_with_categories';
    const CACHE_KEY_HASH = 'products:hash'; // Redis hash for individual products
    const CACHE_KEY_INDEX = 'products:index'; // Redis sorted set for quick lookups
    const CACHE_KEY_LAST_SYNC = 'products:last_sync'; // Last sync timestamp
    const CACHE_TTL = 7200; // 2 hours in seconds
    const CACHE_TAG = 'products';
    const RESYNC_INTERVAL = 1800; // 30 minutes - auto resync if older than this
    
    /**
     * Get all products with categories from Redis cache or database
     * Uses Redis for faster access with 7000+ products
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProducts()
    {
        try {
            // Try to get from Redis first
            $cacheDriver = config('cache.default');
            
            if ($cacheDriver === 'redis') {
                return $this->getProductsFromRedis();
            }
            
            // Fallback to standard cache
            return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                Log::info('Loading products from database (file cache)');
                return Product::with('category')->get();
            });
        } catch (\Exception $e) {
            Log::error('Cache error, loading from database: ' . $e->getMessage());
            return Product::with('category')->get();
        }
    }
    
    /**
     * Get products from Redis with optimized structure
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getProductsFromRedis()
    {
        $cached = Redis::get(self::CACHE_KEY);
        
        if ($cached) {
            Log::info('Products loaded from Redis cache');
            // Decompress and unserialize
            $data = unserialize(gzuncompress($cached));
            
            // Convert arrays back to Product model instances
            $products = collect($data)->map(function ($item) {
                $product = new Product((array) $item);
                $product->exists = true; // Mark as existing record
                
                // Restore category relationship if exists
                if (isset($item['category']) && is_array($item['category'])) {
                    $category = new \App\Category((array) $item['category']);
                    $category->exists = true;
                    $product->setRelation('category', $category);
                }
                
                return $product;
            });
            
            return $products;
        }
        
        Log::info('Loading products from database and caching to Redis');
        $products = Product::with('category')->get();
        
        // Compress and cache to Redis
        $compressed = gzcompress(serialize($products->toArray()), 6);
        Redis::setex(self::CACHE_KEY, self::CACHE_TTL, $compressed);
        
        // Also store in hash for individual access
        $this->storeProductsInHash($products);
        
        // Update last sync timestamp
        $this->updateLastSyncTime();
        
        return $products;
    }
    
    /**
     * Store products in Redis hash for individual access
     * 
     * @param \Illuminate\Database\Eloquent\Collection $products
     * @return void
     */
    protected function storeProductsInHash($products)
    {
        $pipeline = Redis::pipeline();
        
        foreach ($products as $product) {
            $key = "product:{$product->id}";
            $pipeline->setex($key, self::CACHE_TTL, serialize($product->toArray()));
            
            // Add to sorted set for quick lookups by name
            $pipeline->zadd(self::CACHE_KEY_INDEX, 0, $product->id);
        }
        
        $pipeline->execute();
    }
    
    /**
     * Clear the products cache from Redis and file cache
     * 
     * @return void
     */
    public function clearCache()
    {
        try {
            // Clear from Redis
            Redis::del(self::CACHE_KEY);
            Redis::del(self::CACHE_KEY_INDEX);
            
            // Clear individual product keys
            $keys = Redis::keys('product:*');
            if (!empty($keys)) {
                Redis::del($keys);
            }
            
            // Also clear from file cache as fallback
            Cache::forget(self::CACHE_KEY);
            
            Log::info('Products cache cleared from Redis and file cache');
        } catch (\Exception $e) {
            Log::error('Error clearing cache: ' . $e->getMessage());
            Cache::forget(self::CACHE_KEY);
        }
    }
    
    /**
     * Refresh the products cache
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function refreshCache()
    {
        $this->clearCache();
        return $this->getProducts();
    }
    
    /**
     * Get a single product by ID with Redis caching
     * 
     * @param int $id
     * @return \App\Product|null
     */
    public function getProductById($id)
    {
        try {
            $cacheDriver = config('cache.default');
            
            if ($cacheDriver === 'redis') {
                $key = "product:{$id}";
                $cached = Redis::get($key);
                
                if ($cached) {
                    $data = unserialize($cached);
                    $product = new Product((array) $data);
                    $product->exists = true;
                    
                    // Restore category relationship if exists
                    if (isset($data['category']) && is_array($data['category'])) {
                        $category = new \App\Category((array) $data['category']);
                        $category->exists = true;
                        $product->setRelation('category', $category);
                    }
                    
                    return $product;
                }
                
                $product = Product::with('category')->find($id);
                if ($product) {
                    Redis::setex($key, self::CACHE_TTL, serialize($product->toArray()));
                }
                
                return $product;
            }
            
            // Fallback to file cache
            $cacheKey = "product_{$id}";
            return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
                return Product::with('category')->find($id);
            });
        } catch (\Exception $e) {
            Log::error('Error getting product from cache: ' . $e->getMessage());
            return Product::with('category')->find($id);
        }
    }
    
    /**
     * Clear cache for a specific product from Redis
     * 
     * @param int $id
     * @return void
     */
    public function clearProductCache($id)
    {
        try {
            // Clear from Redis
            Redis::del("product:{$id}");
            
            // Also clear from file cache
            Cache::forget("product_{$id}");
            
            Log::info("Cleared cache for product ID: {$id}");
        } catch (\Exception $e) {
            Log::error('Error clearing product cache: ' . $e->getMessage());
            Cache::forget("product_{$id}");
        }
    }
    
    /**
     * Update product stock in cache and database
     * Optimized to update Redis cache immediately without full refresh
     * 
     * @param int $productId
     * @param int $newStock
     * @return void
     */
    public function updateProductStock($productId, $newStock)
    {
        // Update in database
        $product = Product::find($productId);
        if ($product) {
            $product->stock = $newStock;
            $product->save();
            
            // Update in Redis immediately
            try {
                $key = "product:{$productId}";
                Redis::setex($key, self::CACHE_TTL, serialize($product->toArray()));
                
                // Clear main cache to force refresh
                $this->clearCache();
                
                Log::info("Updated stock for product ID {$productId} in Redis");
            } catch (\Exception $e) {
                Log::error('Error updating product stock in cache: ' . $e->getMessage());
                $this->clearProductCache($productId);
                $this->clearCache();
            }
        }
    }
    
    /**
     * Warm up the cache by pre-loading products
     * Useful after cache clear or system restart
     * 
     * @return bool
     */
    public function warmCache()
    {
        try {
            Log::info('Warming up products cache...');
            $this->getProducts();
            Log::info('Products cache warmed successfully');
            return true;
        } catch (\Exception $e) {
            Log::error('Error warming cache: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array
     */
    public function getCacheStats()
    {
        try {
            $lastSync = Redis::get(self::CACHE_KEY_LAST_SYNC);
            $lastSyncTime = $lastSync ? date('Y-m-d H:i:s', $lastSync) : 'Never';
            $timeSinceSync = $lastSync ? (time() - $lastSync) : null;
            $needsResync = $timeSinceSync ? ($timeSinceSync > self::RESYNC_INTERVAL) : true;
            
            $stats = [
                'driver' => config('cache.default'),
                'main_cache_exists' => Redis::exists(self::CACHE_KEY) > 0,
                'product_count' => Redis::zcard(self::CACHE_KEY_INDEX),
                'ttl' => Redis::ttl(self::CACHE_KEY),
                'last_sync' => $lastSyncTime,
                'seconds_since_sync' => $timeSinceSync,
                'needs_resync' => $needsResync,
                'resync_interval' => self::RESYNC_INTERVAL,
            ];
            
            return $stats;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
    
    /**
     * Check if cache needs resync based on time interval
     * Compatible with PHP 7.4 and Laravel 7
     * 
     * @return bool
     */
    public function needsResync()
    {
        try {
            $lastSync = Redis::get(self::CACHE_KEY_LAST_SYNC);
            
            if (!$lastSync) {
                return true; // Never synced
            }
            
            $timeSinceSync = time() - $lastSync;
            return $timeSinceSync > self::RESYNC_INTERVAL;
        } catch (\Exception $e) {
            Log::error('Error checking resync status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update last sync timestamp
     * 
     * @return void
     */
    protected function updateLastSyncTime()
    {
        try {
            Redis::set(self::CACHE_KEY_LAST_SYNC, time());
            Log::info('Updated last sync timestamp');
        } catch (\Exception $e) {
            Log::error('Error updating sync timestamp: ' . $e->getMessage());
        }
    }
    
    /**
     * Get products with automatic resync if needed
     * This checks if cache is stale and refreshes automatically
     * Compatible with PHP 7.4 and Laravel 7
     * 
     * @param bool $forceResync Force resync regardless of interval
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProductsWithAutoResync($forceResync = false)
    {
        try {
            if ($forceResync || $this->needsResync()) {
                Log::info('Cache is stale or force resync requested, refreshing...');
                return $this->refreshCache();
            }
            
            return $this->getProducts();
        } catch (\Exception $e) {
            Log::error('Error in auto resync: ' . $e->getMessage());
            return $this->getProducts();
        }
    }
    
    /**
     * Resync cache from database
     * This is a full refresh that updates all cached data
     * 
     * @return array Status information
     */
    public function resyncCache()
    {
        try {
            Log::info('Starting manual cache resync...');
            $startTime = microtime(true);
            
            // Clear old cache
            $this->clearCache();
            
            // Load fresh data
            $products = $this->getProducts();
            
            $duration = round(microtime(true) - $startTime, 2);
            
            $result = [
                'success' => true,
                'product_count' => $products->count(),
                'duration' => $duration,
                'timestamp' => date('Y-m-d H:i:s'),
            ];
            
            Log::info('Cache resync completed', $result);
            
            return $result;
        } catch (\Exception $e) {
            Log::error('Error during cache resync: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Get last sync time
     * 
     * @return string|null
     */
    public function getLastSyncTime()
    {
        try {
            $lastSync = Redis::get(self::CACHE_KEY_LAST_SYNC);
            return $lastSync ? date('Y-m-d H:i:s', $lastSync) : null;
        } catch (\Exception $e) {
            Log::error('Error getting last sync time: ' . $e->getMessage());
            return null;
        }
    }
}
