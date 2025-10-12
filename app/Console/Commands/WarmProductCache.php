<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductCacheService;

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
    protected $description = 'Warm up the products cache by loading all products from database';

    protected $cacheService;

    /**
     * Create a new command instance.
     *
     * @param ProductCacheService $cacheService
     * @return void
     */
    public function __construct(ProductCacheService $cacheService)
    {
        parent::__construct();
        $this->cacheService = $cacheService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Warming up products cache...');
        
        $startTime = microtime(true);
        
        $products = $this->cacheService->refreshCache();
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        $this->info("Products cache warmed successfully!");
        $this->info("Loaded {$products->count()} products in {$duration} seconds");
        
        return 0;
    }
}
