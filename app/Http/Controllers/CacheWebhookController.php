<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ProductCacheService;
use Illuminate\Support\Facades\Log;

class CacheWebhookController extends Controller
{
    protected $cacheService;

    public function __construct(ProductCacheService $cacheService)
    {
        $this->cacheService = $cacheService;
    }

    /**
     * Clear product cache when notified by remote system
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearProducts(Request $request)
    {
        // Verify secret to prevent unauthorized cache clearing
        $secret = $request->input('secret');
        $expectedSecret = env('CACHE_WEBHOOK_SECRET', 'your-secret-key');
        
        if ($secret !== $expectedSecret) {
            Log::warning('Unauthorized cache clear attempt');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Clear the cache
        $this->cacheService->clearCache();
        Log::info('Product cache cleared via webhook');
        
        return response()->json([
            'success' => true,
            'message' => 'Product cache cleared successfully'
        ]);
    }
    
    /**
     * Refresh product cache when notified by remote system
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function refreshProducts(Request $request)
    {
        // Verify secret
        $secret = $request->input('secret');
        $expectedSecret = env('CACHE_WEBHOOK_SECRET', 'your-secret-key');
        
        if ($secret !== $expectedSecret) {
            Log::warning('Unauthorized cache refresh attempt');
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Refresh the cache
        $products = $this->cacheService->refreshCache();
        Log::info('Product cache refreshed via webhook', ['count' => $products->count()]);
        
        return response()->json([
            'success' => true,
            'message' => 'Product cache refreshed successfully',
            'count' => $products->count()
        ]);
    }
}
