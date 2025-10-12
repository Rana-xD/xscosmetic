<?php

namespace App\Observers;

use App\Product;
use App\Services\ProductCacheService;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    protected $cacheService;

    public function __construct(ProductCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Handle the Product "created" event.
     *
     * @param  \App\Product  $product
     * @return void
     */
    public function created(Product $product)
    {
        $this->cacheService->clearCache();
        Log::info("Product cache cleared after creating product: {$product->id}");
    }

    /**
     * Handle the Product "updated" event.
     *
     * @param  \App\Product  $product
     * @return void
     */
    public function updated(Product $product)
    {
        $this->cacheService->clearProductCache($product->id);
        $this->cacheService->clearCache();
        Log::info("Product cache cleared after updating product: {$product->id}");
    }

    /**
     * Handle the Product "deleted" event.
     *
     * @param  \App\Product  $product
     * @return void
     */
    public function deleted(Product $product)
    {
        $this->cacheService->clearProductCache($product->id);
        $this->cacheService->clearCache();
        Log::info("Product cache cleared after deleting product: {$product->id}");
    }

    /**
     * Handle the Product "restored" event.
     *
     * @param  \App\Product  $product
     * @return void
     */
    public function restored(Product $product)
    {
        $this->cacheService->clearCache();
        Log::info("Product cache cleared after restoring product: {$product->id}");
    }

    /**
     * Handle the Product "force deleted" event.
     *
     * @param  \App\Product  $product
     * @return void
     */
    public function forceDeleted(Product $product)
    {
        $this->cacheService->clearProductCache($product->id);
        $this->cacheService->clearCache();
        Log::info("Product cache cleared after force deleting product: {$product->id}");
    }
}
