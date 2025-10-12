<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductCacheService;

class ClearProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:clear-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the products cache';

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
        $this->info('Clearing products cache...');
        
        $this->cacheService->clearCache();
        
        $this->info('Products cache cleared successfully!');
        
        return 0;
    }
}
