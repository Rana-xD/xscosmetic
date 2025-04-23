<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\POS;
use App\TPOS;
use App\Setting;
use Auth;
use DateTime;
use DateTimeZone;
use App\ProductIncome;
use App\Events\NewOrder;
use charlieuki\ReceiptPrinter\ReceiptPrinter as ReceiptPrinter;
use Carbon\Carbon;
use DB;

class POSController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('admin');
    }

    public function show()
    {
        ini_set('max_execution_time', '300');
        $products = Product::with('category')->get();
        $setting = Setting::first();
        return view('pos', [
            'products' => $products,
            'exchange_rate' => $setting->exchange_rate
        ]);
    }

    public function store(Request $request)
    {
        $additional_info = $request->additional_info;
        
        $data = [
            "order_no" => $request->invoice_no,
            "items" => $request->data,
            "cashier" => Auth::user()->username,
            "time" => $this->getLocaleTime(),
            "payment_type" => $request->payment_type,
            "additional_info" => $additional_info,
            'created_at' => $this->getLocaleTimestamp(),
            'updated_at' => $this->getLocaleTimestamp()
        ];
        
        // Add received and change amounts for cash payments
        if ($request->payment_type === 'cash') {
            // Clean and convert received values
            $receivedUsd = isset($additional_info['received_in_usd']) ? $additional_info['received_in_usd'] : 0;
            $receivedUsd = is_string($receivedUsd) ? (float) preg_replace('/[^0-9.]/', '', $receivedUsd) : (float) $receivedUsd;
            
            $receivedRiel = isset($additional_info['received_in_riel']) ? $additional_info['received_in_riel'] : 0;
            $receivedRiel = is_string($receivedRiel) ? (int) preg_replace('/[^0-9]/', '', $receivedRiel) : (int) $receivedRiel;
            
            $data['received_in_usd'] = $receivedUsd;
            $data['received_in_riel'] = $receivedRiel;
            
            // Clean and convert change values
            $changeUsd = isset($additional_info['change_in_usd']) ? $additional_info['change_in_usd'] : 0;
            $changeUsd = is_string($changeUsd) ? (float) preg_replace('/[^0-9.-]/', '', $changeUsd) : (float) $changeUsd;
            
            $changeRiel = isset($additional_info['change_in_riel']) ? $additional_info['change_in_riel'] : 0;
            $changeRiel = is_string($changeRiel) ? (int) preg_replace('/[^0-9]/', '', $changeRiel) : (int) $changeRiel;
            
            // Store change based on which currency was selected for display
            if (isset($additional_info['selected_change_currency'])) {
                if ($additional_info['selected_change_currency'] === 'usd') {
                    $data['change_in_usd'] = $changeUsd;
                    $data['change_in_riel'] = 0;
                } else {
                    $data['change_in_usd'] = 0;
                    $data['change_in_riel'] = $changeRiel;
                }
            } else {
                // Default behavior if no currency is selected
                $data['change_in_usd'] = $changeUsd;
                $data['change_in_riel'] = $changeRiel;
            }
        }

        // Add custom split payment data if available
        if ($request->payment_type === 'custom') {
            $data['cash_percentage'] = $request->cashPercentage;
            $data['aba_percentage'] = $request->abaPercentage;
            $data['cash_amount'] = $request->cashAmount;
            $data['aba_amount'] = $request->abaAmount;
        }

        $temp_data = [
            "order_no" => $request->invoice_no,
            "items" => $request->temp_data,
            "cashier" => Auth::user()->username,
            "time" => $this->getLocaleTime(),
            "payment_type" => $request->payment_type,
            'created_at' => $this->getLocaleTimestamp(),
            'updated_at' => $this->getLocaleTimestamp()
        ];

        $invoice = $request->invoice;

        if (Auth::user()->role !== "SUPERADMIN") {
            $this->printInvoice(
                $invoice,
                Auth::user()->username,
                $additional_info['total'],
                $additional_info['total_riel'],
                $additional_info['total_discount'],
                $additional_info['received_in_usd'],
                $additional_info['received_in_riel'],
                $additional_info['change_in_usd'],
                $additional_info['change_in_riel']
            );
        }

        $order = POS::create($data);

        if ($this->isAddToTPosValid()) {
            TPOS::create($temp_data);
        }

        $this->deductStock($data['items']);

        return response()->json([
            'code' => 200,
            'data' => $invoice
        ]);
    }

    public function getInvoiceNo()
    {
        $start_time = Carbon::now()->format('Y-m-d') . ' 00:00:00';
        $end_time = Carbon::now()->format('Y-m-d') . ' 23:59:59';
        $results = POS::whereBetween('created_at', [$start_time, $end_time])->get()->count();
        return response()->json([
            'code' => 200,
            'data' => $results
        ]);
    }

    private function deductStock($items)
    {

        foreach ($items as $item) {
            $product = Product::find($item['product_id']);
            $product->stock = $product->stock - (int)$item['quantity'];
            $product->save();
        }
    }


    private function getLocaleTime()
    {
        $timezone = "Asia/Bangkok";
        $date = new DateTime('now', new DateTimeZone($timezone));
        return $date->format('h:i A');
    }

    private function getLocaleTimestamp()
    {
        $timezone = "Asia/Bangkok";
        return $date = new DateTime('now', new DateTimeZone($timezone));
    }

    private function getLocaleDateTime()
    {
        $timezone = "Asia/Bangkok";
        $date = new DateTime('now', new DateTimeZone($timezone));
        return $date->format('j F Y h:i A');
    }


    private function printInvoice($invoice, $cashier, $total, $total_riel, $total_discount, $received_in_usd, $received_in_riel, $change_in_usd, $change_in_riel)
    {
        $store_name = 'cosmetic';

        // Init printer
        $printer = new ReceiptPrinter;
        $printer->init(
            config('receiptprinter.connector_type'),
            config('receiptprinter.connector_descriptor')
        );

        // Set store info
        $printer->setStore($store_name);
        $printer->setCashier($cashier);
        $printer->setTotal($total);
        $printer->setTotalRiel($total_riel);
        $printer->setTotalDiscount($total_discount);
        $printer->setReceivedInUsd($received_in_usd);
        $printer->setReceivedInRiel($received_in_riel);
        $printer->setChangeInUsd($change_in_usd);
        $printer->setChangeInRiel($change_in_riel);
        // $printer->setLogo(asset('img/invoice_image.png'));
        $printer->setLogo(public_path('img/logo.png'));
        $printer->setNote(public_path('img/invoice_image.png'));
        // Add items
        foreach ($invoice as $item) {
            $printer->addItem(
                $item['product_name'],
                $item['price'],
                $item['quantity'],
                $item['discount'],
                $item['total']
            );
        }

        $printer->printXscometicReceipt();
    }

    public function printTotalInvoiceDaily()
    {
        $items = [];
        $arrange_items = [];
        $invoice = [];
        $start_time = Carbon::now()->format('Y-m-d') . ' 00:00:00';
        $end_time = Carbon::now()->format('Y-m-d') . ' 23:59:59';
        $total = 0;
        $total_riel = 0;

        $results = POS::whereBetween('created_at', [$start_time, $end_time])->pluck('items');

        foreach ($results as $result) {
            foreach ($result as $item) {
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can", " ", $item['quantity']),
                    'price' => (float) $item['price'],
                    'discount' => (float) $item['discount'],
                    'total' => (float) str_replace("$", " ", $item['total'])
                ];
                array_push($items, $data);
            }
        }

        foreach ($items as $item) {
            $index = $this->findExistingValueInArray($arrange_items, $item['product_name']);

            if ($index) {
                $arrange_items[$index - 1]['quantity'] += $item['quantity'];
                // $arrange_items[$index-1]['price'] += $item['price'];
                $arrange_items[$index - 1]['discount'] += $item['discount'];
                $arrange_items[$index - 1]['total'] += $item['total'];
            } else {
                array_push($arrange_items, $item);
            }
        }

        foreach ($arrange_items as $item) {

            $data = [
                'product_name' => $item['product_name'],
                'quantity' => strval($item['quantity']),
                'price' => '$' . $item['price'],
                'discount' => '$' . $item['discount'],
                'total' => '$' . $item['total']
            ];
            array_push($invoice, $data);
        }

        foreach ($arrange_items as $item) {
            $total += $item['total'];
        }

        $total_riel = 'R' . number_format($total * 4200);

        // $this->printInvoice($invoice,'$'.$total,$total_riel);
        return response()->json([
            'code' => 200,
        ]);
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

    private function isAddToTPosValid()
    {
        $start_date = Carbon::now()->format('Y-m-d') . ' 00:00:00';
        $end_date = Carbon::now()->format('Y-m-d') . ' 23:59:59';

        $orders = TPOS::whereBetween('created_at', [$start_date, $end_date])->get()->count();
        if ($orders > 3) {
            return false;
        }
        return true;
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            // Find the invoice by ID
            $invoice = POS::find($id);
            if (!$invoice) {
                return response()->json(['success' => false, 'message' => 'Invoice not found']);
            }

            $invoice_no = $invoice->order_no;

            // Update product stock
            foreach ($invoice->items as $item) {
                $product = Product::find($item['product_id']);
                if ($product) {
                    $product->stock += intval($item['quantity']);
                    $product->save();
                }
            }

            // Delete the invoice
            $invoice->delete();

            // Reset invoice numbers for all invoices after this one
            $laterInvoices = POS::where('order_no', '>', $invoice_no)
                ->orderBy('order_no', 'asc')
                ->get();

            foreach ($laterInvoices as $laterInvoice) {
                $currentNumber = intval(ltrim($laterInvoice->order_no, '0'));
                $newNumber = str_pad($currentNumber - 1, 6, '0', STR_PAD_LEFT);
                $laterInvoice->order_no = $newNumber;
                $laterInvoice->save();
            }

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
