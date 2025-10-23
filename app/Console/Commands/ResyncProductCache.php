<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductCacheService;

class ResyncProductCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:resync-products {--force : Force resync even if cache is fresh}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Resync products cache from database (checks if needed or use --force)';

    protected $productCacheService;

    /**
     * Create a new command instance.
     *
     * @param ProductCacheService $productCacheService
     * @return void
     */
    public function __construct(ProductCacheService $productCacheService)
    {
        parent::__construct();
        $this->productCacheService = $productCacheService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('Checking cache status...');
        $this->line('');
        
        // Get current stats
        $stats = $this->productCacheService->getCacheStats();
        
        if (isset($stats['error'])) {
            $this->error('Error getting cache stats: ' . $stats['error']);
            return 1;
        }
        
        // Display current status
        $this->info('Current Cache Status:');
        $this->line("  Driver: {$stats['driver']}");
        $this->line("  Cache Exists: " . ($stats['main_cache_exists'] ? 'Yes' : 'No'));
        $this->line("  Product Count: {$stats['product_count']}");
        $this->line("  Last Sync: {$stats['last_sync']}");
        
        if ($stats['seconds_since_sync']) {
            $minutes = round($stats['seconds_since_sync'] / 60, 1);
            $this->line("  Time Since Sync: {$minutes} minutes");
        }
        
        $this->line('');
        
        // Check if resync is needed
        if (!$force && !$stats['needs_resync']) {
            $this->info('✓ Cache is fresh, no resync needed.');
            $this->line('  Use --force to resync anyway.');
            return 0;
        }
        
        if ($force) {
            $this->warn('→ Force resync requested...');
        } else {
            $this->warn('→ Cache is stale, resyncing...');
        }
        
        $this->line('');
        
        // Perform resync
        $result = $this->productCacheService->resyncCache();
        
        if ($result['success']) {
            $this->info('✓ Cache resync completed successfully!');
            $this->line('');
            $this->line("  Products Loaded: {$result['product_count']}");
            $this->line("  Duration: {$result['duration']}s");
            $this->line("  Timestamp: {$result['timestamp']}");
            return 0;
        } else {
            $this->error('✗ Cache resync failed!');
            $this->line('  Error: ' . $result['error']);
            return 1;
        }
    }
}
