<?php

namespace App\Http\Controllers;

use App\POS;
use Illuminate\Http\Request;
use charlieuki\ReceiptPrinter\ReceiptPrinter;

class OrderController extends Controller
{
    
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function show(){
        $orders = POS::orderBy('created_at', 'DESC')->where('is_done',0)->get();
        return view('order',[
            'orders' => $orders
        ]);
    }

    public function update(Request $request){
        $id = $request->id;
        $order = POS::find($id);
        $order->is_done = true;
        $order->save();
        return response()->json([
            'code' => 200
        ]);
    }

    public function printInvoice(Request $request) {
        $order = POS::find($request->id);
        if (!$order || $order->additional_info == null) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $printer = $this->initializePrinter($order->additional_info, $order->cashier);

        // Add items
        foreach ($order->items as $item) {
            $printer->addItem(
                $item['product_name'],
                $item['price'],
                $item['quantity'],
                $item['discount'],
                $item['total']
            );
        }

        $printer->printXscometicReceipt();

        return response()->json(['success' => true]);
    }

    /**
     * Process and combine order items from multiple orders
     * 
     * @param Collection $orders Collection of POS orders
     * @return array Combined and processed items with quantities and totals
     */
    private function processOrderItems($orders) {
        // Initialize arrays to store temporary and final items
        $items = [];
        $arrange_items = [];

        // First loop: Extract items from each order and standardize their format
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can", " ", $item['quantity']), // Convert quantity to integer, remove 'Can' text
                    'price' => number_format((float) $item['price'], 2, '.', ''),
                    'discount' => 0,
                    'total' => number_format((float) str_replace("$", " ", $item['total']), 2, '.', '') // Convert total to float with 2 decimals
                ];
                array_push($items, $data);
            }
        }

        // Second loop: Combine items with same product name
        foreach ($items as $item) {
            // Check if product already exists in arranged items
            $index = $this->findExistingValueInArray($arrange_items, $item['product_name']);

            if ($index) {
                // If product exists, add quantities and totals
                $arrange_items[$index - 1]['quantity'] += $item['quantity'];
                $arrange_items[$index - 1]['total'] = number_format($arrange_items[$index - 1]['total'] + $item['total'], 2, '.', '');
            } else {
                // If product is new, add it to arranged items
                array_push($arrange_items, $item);
            }
        }

        return $arrange_items;
    }

    /**
     * Calculate total amounts in USD and Riel from orders' additional info
     * 
     * @param Collection $orders Collection of POS orders
     * @return array Associative array with total and total_riel
     */
    private function calculateDailyTotals($orders) {
        $total = 0;
        $total_riel = 0;

        // Sum up totals from additional_info of each order
        foreach ($orders as $order) {
            if ($order->additional_info) {
                $additional_info = $order->additional_info;
                $total += floatval($additional_info['total']);
                $total_riel += floatval($additional_info['total_riel']);
            }
        }

        return [
            'total' => $total,
            'total_riel' => $total_riel
        ];
    }

    /**
     * Initialize and configure the receipt printer
     * 
     * @param array $totals Array containing total and total_riel
     * @param string $date Date of the daily summary
     * @return ReceiptPrinter Configured printer instance
     */
    private function initializePrinter($totals, $date) {
        // Create new printer instance
        $printer = new ReceiptPrinter;
        $printer->init(
            config('receiptprinter.connector_type'),
            config('receiptprinter.connector_descriptor')
        );

        // Configure printer settings
        $printer->setStore('cosmetic');
        $printer->setCashier("Daily Sale ($date)");
        $printer->setTotal($totals['total']);
        $printer->setTotalRiel($totals['total_riel']);
        
        // Set monetary values to zero as per requirement
        $printer->setTotalDiscount(0);
        $printer->setReceivedInUsd(0);
        $printer->setReceivedInRiel(0);
        $printer->setChangeInUsd(0);
        $printer->setChangeInRiel(0);
        
        // Set logo and note images
        $printer->setLogo(public_path('img/logo.png'));
        $printer->setNote(public_path('img/invoice_image.png'));

        return $printer;
    }

    /**
     * Main method to handle daily invoice printing
     * 
     * @param Request $request HTTP request containing date
     * @return JsonResponse Success status and message
     */
    public function printDailyInvoice(Request $request) {
        try {
            // Get orders for the specified date
            $date = $request->date;
            $orders = POS::whereDate('created_at', $date)->get();
            
            // Check if orders exist for the date
            if ($orders->isEmpty()) {
                return response()->json([
                    'success' => false, 
                    'message' => __('messages.no_invoices_found')
                ], 404);
            }

            // Process items and calculate totals
            $arrange_items = $this->processOrderItems($orders);
            $totals = $this->calculateDailyTotals($orders);

            // Initialize and configure printer
            $printer = $this->initializePrinter($totals, $date);

            // Add each item to the printer
            foreach ($arrange_items as $item) {
                $printer->addItem(
                    $item['product_name'],
                    $item['price'],
                    $item['quantity'],
                    0,  // Zero discount for each item
                    $item['total']
                );
            }

            // Print the receipt
            $printer->printXscometicReceipt();

            return response()->json([
                'success' => true,
                'message' => __('messages.daily_summary_printed_successfully')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => __('messages.error_printing_daily_summary') . ': ' . $e->getMessage()
            ], 500);
        }
    }

    private function findExistingValueInArray($arrays, $value)
    {
        foreach ($arrays as $key => $array) {
            if ($array['product_name'] === $value) {
                return $key + 1;
            }
        }
        return 0;
    }
}
