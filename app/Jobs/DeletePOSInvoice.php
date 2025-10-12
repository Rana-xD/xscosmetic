<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Product;
use App\POS;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Services\ProductCacheService;

class DeletePOSInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $invoiceId;

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
    public $timeout = 600; // 10 minutes

    /**
     * Create a new job instance.
     *
     * @param int $invoiceId
     * @return void
     */
    public function __construct($invoiceId)
    {
        $this->invoiceId = $invoiceId;
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

            // Find the invoice by ID
            $invoice = POS::find($this->invoiceId);
            if (!$invoice) {
                Log::warning("Invoice not found for deletion: {$this->invoiceId}");
                return;
            }

            $invoice_no = $invoice->order_no;
            Log::info("Starting deletion of invoice: {$invoice_no}");

            // Delete the invoice (no stock restoration)
            $invoice->delete();
            Log::info("Invoice deleted: {$invoice_no}");

            // Resequence invoice numbers for all invoices after this one
            $laterInvoices = POS::where('order_no', '>', $invoice_no)
                ->orderBy('order_no', 'asc')
                ->get();

            foreach ($laterInvoices as $laterInvoice) {
                $currentNumber = intval(ltrim($laterInvoice->order_no, '0'));
                $newNumber = str_pad($currentNumber - 1, 6, '0', STR_PAD_LEFT);
                
                // Use update query for better performance
                DB::table('pos')
                    ->where('id', $laterInvoice->id)
                    ->update(['order_no' => $newNumber]);
                
                Log::info("Updated invoice number: {$laterInvoice->order_no} -> {$newNumber}");
            }

            DB::commit();
            Log::info("Invoice deletion completed successfully: {$invoice_no}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to delete invoice {$this->invoiceId}: " . $e->getMessage());
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
        Log::error("Invoice deletion job failed permanently for invoice {$this->invoiceId}: " . $exception->getMessage());
        // You can add notification logic here to alert admins
    }
}
