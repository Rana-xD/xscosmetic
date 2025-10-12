<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Product;
use App\POS;
use App\TPOS;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ProductCacheService;

class ProcessPOSOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderData;
    protected $tempData;
    protected $isAddToTPosValid;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array
     */
    public $backoff = [3, 10, 30]; // Wait 3s, then 10s, then 30s

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param array $orderData
     * @param array $tempData
     * @param bool $isAddToTPosValid
     * @return void
     */
    public function __construct($orderData, $tempData, $isAddToTPosValid)
    {
        $this->orderData = $orderData;
        $this->tempData = $tempData;
        $this->isAddToTPosValid = $isAddToTPosValid;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            DB::beginTransaction();

            // Create POS order
            $order = POS::create($this->orderData);
            Log::info("POS Order created: {$order->order_no}");

            // Create TPOS if valid
            if ($this->isAddToTPosValid) {
                TPOS::create($this->tempData);
                Log::info("TPOS created for order: {$order->order_no}");
            }

            // Deduct stock for each item
            $this->deductStock($this->orderData['items']);

            DB::commit();
            Log::info("POS Order processed successfully: {$order->order_no}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to process POS order: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Deduct stock for items using optimized batch operations
     *
     * @param array $items
     * @return void
     */
    private function deductStock($items)
    {
        foreach ($items as $item) {
            // Use atomic decrement operation for better performance
            DB::table('products')
                ->where('id', $item['product_id'])
                ->decrement('stock', (int)$item['quantity']);
            
            // Clear individual product cache
            Cache::forget("product_{$item['product_id']}");
            
            Log::info("Stock deducted for product {$item['product_id']}: {$item['quantity']} units");
        }
        
        // Clear main products cache to reflect changes
        $cacheService = app(ProductCacheService::class);
        $cacheService->clearCache();
        
        Log::info("Product cache cleared after stock update");
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("POS Order job failed: " . $exception->getMessage());
        // You can add notification logic here to alert admins
    }
}
