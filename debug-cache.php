<?php

/**
 * Debug script to diagnose cache data type issues
 * Run: php debug-cache.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CACHE DEBUG REPORT ===\n\n";

// 1. Check cache driver
echo "1. Cache Configuration:\n";
echo "   Driver: " . config('cache.default') . "\n";
echo "   Redis Host: " . config('database.redis.default.host') . "\n\n";

// 2. Check if cache exists
echo "2. Cache Status:\n";
try {
    $cacheExists = \Illuminate\Support\Facades\Redis::exists('pos_products_with_categories');
    echo "   Cache exists: " . ($cacheExists ? 'YES' : 'NO') . "\n";
    
    if ($cacheExists) {
        $ttl = \Illuminate\Support\Facades\Redis::ttl('pos_products_with_categories');
        echo "   Cache TTL: " . $ttl . " seconds\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 3. Get products using cache service
echo "3. Testing ProductCacheService:\n";
try {
    $cacheService = app(\App\Services\ProductCacheService::class);
    $products = $cacheService->getProducts();
    
    echo "   Total products: " . $products->count() . "\n";
    
    if ($products->count() > 0) {
        $firstProduct = $products->first();
        
        echo "   First product type: " . gettype($firstProduct) . "\n";
        echo "   Is object: " . (is_object($firstProduct) ? 'YES' : 'NO') . "\n";
        echo "   Is array: " . (is_array($firstProduct) ? 'YES' : 'NO') . "\n";
        
        if (is_object($firstProduct)) {
            echo "   Class: " . get_class($firstProduct) . "\n";
            echo "   Has product_code property: " . (property_exists($firstProduct, 'product_code') ? 'YES' : 'NO') . "\n";
            echo "   Has category relation: " . (method_exists($firstProduct, 'category') ? 'YES' : 'NO') . "\n";
            
            // Try to access properties
            echo "\n   Trying to access properties:\n";
            try {
                echo "   - ID: " . ($firstProduct->id ?? 'NULL') . "\n";
                echo "   - Name: " . ($firstProduct->name ?? 'NULL') . "\n";
                echo "   - Product Code: " . ($firstProduct->product_code ?? 'NULL') . "\n";
                echo "   - Stock: " . ($firstProduct->stock ?? 'NULL') . "\n";
                
                // Check category
                if (isset($firstProduct->category)) {
                    echo "   - Category Type: " . gettype($firstProduct->category) . "\n";
                    if (is_object($firstProduct->category)) {
                        echo "   - Category ID: " . ($firstProduct->category->id ?? 'NULL') . "\n";
                    }
                } else {
                    echo "   - Category: NOT SET\n";
                }
            } catch (\Exception $e) {
                echo "   ERROR accessing properties: " . $e->getMessage() . "\n";
            }
            
            // Show all available properties/attributes
            echo "\n   Available attributes:\n";
            if (method_exists($firstProduct, 'getAttributes')) {
                $attrs = $firstProduct->getAttributes();
                foreach (array_keys($attrs) as $key) {
                    echo "   - {$key}\n";
                }
            }
        } else if (is_array($firstProduct)) {
            echo "   Array keys: " . implode(', ', array_keys($firstProduct)) . "\n";
            echo "   Has product_code key: " . (isset($firstProduct['product_code']) ? 'YES' : 'NO') . "\n";
        }
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
    echo "   Stack trace:\n";
    echo "   " . $e->getTraceAsString() . "\n";
}
echo "\n";

// 4. Check raw Redis data
echo "4. Raw Redis Data:\n";
try {
    $cached = \Illuminate\Support\Facades\Redis::get('pos_products_with_categories');
    if ($cached) {
        echo "   Raw cache size: " . strlen($cached) . " bytes\n";
        
        // Try to decompress and unserialize
        $decompressed = gzuncompress($cached);
        echo "   Decompressed size: " . strlen($decompressed) . " bytes\n";
        
        $data = unserialize($decompressed);
        echo "   Unserialized type: " . gettype($data) . "\n";
        echo "   Is array: " . (is_array($data) ? 'YES' : 'NO') . "\n";
        echo "   Count: " . count($data) . "\n";
        
        if (count($data) > 0) {
            $first = $data[0];
            echo "   First item type: " . gettype($first) . "\n";
            
            if (is_array($first)) {
                echo "   First item keys: " . implode(', ', array_keys($first)) . "\n";
                echo "   Has product_code: " . (isset($first['product_code']) ? 'YES (' . $first['product_code'] . ')' : 'NO') . "\n";
            }
        }
    } else {
        echo "   No cache data found\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 5. Test direct database query
echo "5. Direct Database Query:\n";
try {
    $product = \App\Product::with('category')->first();
    if ($product) {
        echo "   Type: " . gettype($product) . "\n";
        echo "   Class: " . get_class($product) . "\n";
        echo "   ID: " . $product->id . "\n";
        echo "   Name: " . $product->name . "\n";
        echo "   Product Code: " . $product->product_code . "\n";
        echo "   Category: " . ($product->category ? $product->category->id : 'NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

// 6. Check last sync time
echo "6. Cache Sync Status:\n";
try {
    $lastSync = \Illuminate\Support\Facades\Redis::get('products:last_sync');
    if ($lastSync) {
        echo "   Last sync: " . date('Y-m-d H:i:s', $lastSync) . "\n";
        echo "   Time since sync: " . (time() - $lastSync) . " seconds\n";
    } else {
        echo "   Never synced\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}
echo "\n";

echo "=== END DEBUG REPORT ===\n";
