<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BatchUpdateProductStock implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $stockUpdates;

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
    public $backoff = [3, 10, 30];

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 120;

    /**
     * Create a new job instance.
     *
     * @param array $stockUpdates Format: [['product_id' => 1, 'quantity' => 5], ...]
     * @return void
     */
    public function __construct(array $stockUpdates)
    {
        $this->stockUpdates = $stockUpdates;
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

            foreach ($this->stockUpdates as $update) {
                $productId = $update['product_id'];
                $quantity = $update['quantity'];

                // Use raw SQL for better performance with remote DB
                DB::table('products')
                    ->where('id', $productId)
                    ->decrement('stock', $quantity);

                // Clear product cache
                Cache::forget("product_{$productId}");
                
                Log::info("Stock updated for product {$productId}: -{$quantity}");
            }

            // Clear main products cache
            Cache::forget('pos_products_with_categories');

            DB::commit();
            Log::info("Batch stock update completed for " . count($this->stockUpdates) . " products");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Batch stock update failed: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Batch stock update job failed: " . $exception->getMessage());
    }
}
