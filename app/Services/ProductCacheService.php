<?php

namespace App\Services;

use App\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductCacheService
{
    const CACHE_KEY = 'pos_products_with_categories';
    const CACHE_TTL = 3600; // 1 hour in seconds
    
    /**
     * Get all products with categories from cache or database
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getProducts()
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            Log::info('Loading products from remote database');
            return Product::with('category')->get();
        });
    }
    
    /**
     * Clear the products cache
     * 
     * @return void
     */
    public function clearCache()
    {
        Cache::forget(self::CACHE_KEY);
        Log::info('Products cache cleared');
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
     * Get a single product by ID with caching
     * 
     * @param int $id
     * @return \App\Product|null
     */
    public function getProductById($id)
    {
        $cacheKey = "product_{$id}";
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($id) {
            return Product::find($id);
        });
    }
    
    /**
     * Clear cache for a specific product
     * 
     * @param int $id
     * @return void
     */
    public function clearProductCache($id)
    {
        Cache::forget("product_{$id}");
    }
    
    /**
     * Update product stock in cache and database
     * This is optimized to update cache immediately without full refresh
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
            
            // Clear individual product cache
            $this->clearProductCache($productId);
            
            // Optionally clear main cache to reflect changes
            $this->clearCache();
        }
    }
}
