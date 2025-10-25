<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\ProductCacheService;
use App\Product;
use Illuminate\Support\Facades\Redis;

class DebugCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:debug';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Debug cache data types and structure';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('=== CACHE DEBUG REPORT ===');
        $this->newLine();

        // 1. Cache Configuration
        $this->info('1. Cache Configuration:');
        $this->line('   Driver: ' . config('cache.default'));
        $this->newLine();

        // 2. Cache Status
        $this->info('2. Cache Status:');
        try {
            $cacheExists = Redis::exists('pos_products_with_categories');
            $this->line('   Cache exists: ' . ($cacheExists ? 'YES' : 'NO'));
            
            if ($cacheExists) {
                $ttl = Redis::ttl('pos_products_with_categories');
                $this->line('   Cache TTL: ' . $ttl . ' seconds');
            }
        } catch (\Exception $e) {
            $this->error('   ERROR: ' . $e->getMessage());
        }
        $this->newLine();

        // 3. Test ProductCacheService
        $this->info('3. Testing ProductCacheService:');
        try {
            $cacheService = app(ProductCacheService::class);
            $products = $cacheService->getProducts();
            
            $this->line('   Total products: ' . $products->count());
            
            if ($products->count() > 0) {
                $firstProduct = $products->first();
                
                $this->line('   First product type: ' . gettype($firstProduct));
                $this->line('   Is object: ' . (is_object($firstProduct) ? 'YES' : 'NO'));
                $this->line('   Is array: ' . (is_array($firstProduct) ? 'YES' : 'NO'));
                
                if (is_object($firstProduct)) {
                    $this->line('   Class: ' . get_class($firstProduct));
                    
                    $this->newLine();
                    $this->line('   Trying to access properties:');
                    try {
                        $this->line('   - ID: ' . ($firstProduct->id ?? 'NULL'));
                        $this->line('   - Name: ' . ($firstProduct->name ?? 'NULL'));
                        $this->line('   - Product Code: ' . ($firstProduct->product_code ?? 'NULL'));
                        $this->line('   - Stock: ' . ($firstProduct->stock ?? 'NULL'));
                        
                        if (isset($firstProduct->category)) {
                            $this->line('   - Category Type: ' . gettype($firstProduct->category));
                            if (is_object($firstProduct->category)) {
                                $this->line('   - Category ID: ' . ($firstProduct->category->id ?? 'NULL'));
                            }
                        } else {
                            $this->line('   - Category: NOT SET');
                        }
                    } catch (\Exception $e) {
                        $this->error('   ERROR accessing properties: ' . $e->getMessage());
                    }
                    
                    // Show attributes
                    $this->newLine();
                    $this->line('   Available attributes:');
                    if (method_exists($firstProduct, 'getAttributes')) {
                        $attrs = $firstProduct->getAttributes();
                        foreach (array_keys($attrs) as $key) {
                            $this->line('   - ' . $key);
                        }
                    }
                } else if (is_array($firstProduct)) {
                    $this->line('   Array keys: ' . implode(', ', array_keys($firstProduct)));
                    $this->line('   Has product_code: ' . (isset($firstProduct['product_code']) ? 'YES' : 'NO'));
                }
            }
        } catch (\Exception $e) {
            $this->error('   ERROR: ' . $e->getMessage());
        }
        $this->newLine();

        // 4. Raw Redis Data
        $this->info('4. Raw Redis Data:');
        try {
            $cached = Redis::get('pos_products_with_categories');
            if ($cached) {
                $this->line('   Raw cache size: ' . strlen($cached) . ' bytes');
                
                $decompressed = gzuncompress($cached);
                $this->line('   Decompressed size: ' . strlen($decompressed) . ' bytes');
                
                $data = unserialize($decompressed);
                $this->line('   Unserialized type: ' . gettype($data));
                $this->line('   Is array: ' . (is_array($data) ? 'YES' : 'NO'));
                $this->line('   Count: ' . count($data));
                
                if (count($data) > 0) {
                    $first = $data[0];
                    $this->line('   First item type: ' . gettype($first));
                    
                    if (is_array($first)) {
                        $keys = array_keys($first);
                        $this->line('   First item has ' . count($keys) . ' keys');
                        $this->line('   Sample keys: ' . implode(', ', array_slice($keys, 0, 10)));
                        $this->line('   Has product_code: ' . (isset($first['product_code']) ? 'YES' : 'NO'));
                        
                        if (isset($first['category'])) {
                            $this->line('   Category type: ' . gettype($first['category']));
                        }
                    }
                }
            } else {
                $this->line('   No cache data found');
            }
        } catch (\Exception $e) {
            $this->error('   ERROR: ' . $e->getMessage());
        }
        $this->newLine();

        // 5. Direct Database Query
        $this->info('5. Direct Database Query:');
        try {
            $product = Product::with('category')->first();
            if ($product) {
                $this->line('   Type: ' . gettype($product));
                $this->line('   Class: ' . get_class($product));
                $this->line('   ID: ' . $product->id);
                $this->line('   Product Code: ' . $product->product_code);
                $this->line('   Has category: ' . ($product->category ? 'YES' : 'NO'));
            }
        } catch (\Exception $e) {
            $this->error('   ERROR: ' . $e->getMessage());
        }
        $this->newLine();

        // 6. Version Check
        $this->info('6. Environment:');
        $this->line('   PHP Version: ' . PHP_VERSION);
        $this->line('   Laravel Version: ' . app()->version());
        $this->newLine();

        $this->info('=== END DEBUG REPORT ===');
        
        return 0;
    }
}
