<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductCacheService;
use App\Services\UserCacheService;

class WarmProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:warm-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Warm up the products and users cache by loading all data from database';

    protected $productCacheService;
    protected $userCacheService;

    /**
     * Create a new command instance.
     *
     * @param ProductCacheService $productCacheService
     * @param UserCacheService $userCacheService
     * @return void
     */
    public function __construct(ProductCacheService $productCacheService, UserCacheService $userCacheService)
    {
        parent::__construct();
        $this->productCacheService = $productCacheService;
        $this->userCacheService = $userCacheService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Warming up cache...');
        $this->line('');
        
        $totalStartTime = microtime(true);
        
        // Warm products cache
        $this->info('→ Loading products...');
        $productStartTime = microtime(true);
        $products = $this->productCacheService->refreshCache();
        $productDuration = round(microtime(true) - $productStartTime, 2);
        $this->info("  ✓ Loaded {$products->count()} products in {$productDuration}s");
        
        // Warm users cache
        $this->info('→ Loading users...');
        $userStartTime = microtime(true);
        $users = $this->userCacheService->refreshCache();
        $userDuration = round(microtime(true) - $userStartTime, 2);
        $this->info("  ✓ Loaded {$users->count()} users in {$userDuration}s");
        
        $totalDuration = round(microtime(true) - $totalStartTime, 2);
        
        $this->line('');
        $this->info("Cache warmed successfully in {$totalDuration}s!");
        
        return 0;
    }
}
