<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\POS;
use Auth;
use DateTime;
use DateTimeZone;
use App\ProductIncome;
use App\Events\NewOrder;
use charlieuki\ReceiptPrinter\ReceiptPrinter as ReceiptPrinter;
use Carbon\Carbon;

class POSController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // $this->middleware('admin');
    }

    public function show(){
        $products = Product::with('unit')->orderBy('created_at','asc')->get();
        return view('pos',[
            'products' => $products
        ]);
    }

    public function store(Request $request){
        $data = [
            "order_no" => $request->invoice_no,
            "items" => $request->data,
            "cashier" => Auth::user()->username,
            "time" => $this->getLocaleTime(),
            "payment_type" => $request->payment_type,
            'created_at' => $this->getLocaleTimestamp(),
            'updated_at' => $this->getLocaleTimestamp()
        ];

        $invoice = $request->invoice;
        $total = $request->total;
        $total_riel = $request->total_riel;
        $order = POS::create($data);

        $this->deductStock($data['items']);        
        $this->updateProductIncome($data['items']);
        // NewOrder::dispatch($order);
        // $this->printInvoice($invoice,$total,$total_riel);
        return response()->json([
            'code' => 200,
            'data' => $invoice
        ]);
    }

    public function getInvoiceNo(){
        $start_time = Carbon::now()->format('Y-m-d').' 00:00:00';
        $end_time = Carbon::now()->format('Y-m-d').' 23:59:59';
        $results = POS::whereBetween('created_at',[$start_time,$end_time])->get()->count();
        $invoice_no = 000000 + $results;
        return response()->json([
            'code' => 200,
            'data' => $invoice_no
        ]);
    }

    private function deductStock($items){

        foreach($items as $item){
            $product = Product::find($item['product_id']);
            $product->stock = $product->stock - (int)$item['quantity'];
            $product->save();
        }
    }

    private function updateProductIncome($items){
        foreach($items as $item){
            $product = Product::find($item['product_id']);

            $total_cost = $product->cost * (int)$item['quantity'];
            $total_price = (float) str_replace('$','',$item['total']);
            $profit = $total_price - $total_cost;
            
            $product_income = ProductIncome::where('product_id',$product->id)->first();

            $product_income->quantity += (int)$item['quantity'];
            $product_income->total_price += $total_price;
            $product_income->total_cost += $total_cost;
            $product_income->profit += $profit;
            $product_income->save();          
        }
    }

    private function getLocaleTime(){
        $timezone = "Asia/Bangkok";
        $date = new DateTime('now', new DateTimeZone($timezone));
        return $date->format('h:i A');
    }

    private function getLocaleTimestamp(){
        $timezone = "Asia/Bangkok";
        return $date = new DateTime('now', new DateTimeZone($timezone));
    }

    private function getLocaleDateTime(){
        $timezone = "Asia/Bangkok";
        $date = new DateTime('now', new DateTimeZone($timezone));
        return $date->format('j F Y h:i A');
    }


    private function printInvoice($invoice,$total,$total_riel){
        $store_name = 'XScosmetic';
        $store_phone = '010883816';

        // Init printer
        $printer = new ReceiptPrinter;
        $printer->init(
            config('receiptprinter.connector_type'),
            config('receiptprinter.connector_descriptor')
        );

        // Set store info
        $printer->setStore($store_name, $store_phone);

        // Add items
        foreach ($invoice as $item) {
            $printer->addItem(
                $item['product_name'],
                $item['quantity'],
                $item['price'],
                $item['discount'],
                $item['total']
            );
        }

        $printer->setTotal($total);
        $printer->setTotalRiel($total_riel);
        $printer->setLocaleDateTime($this->getLocaleDateTime());

        // Print receipt
        $printer->printReceipt();

    }

    public function printTotalInvoiceDaily(){
        $items = [];
        $arrange_items = [];
        $invoice = [];
        $start_time = Carbon::now()->format('Y-m-d').' 00:00:00';
        $end_time = Carbon::now()->format('Y-m-d').' 23:59:59';
        $total = 0;
        $total_riel = 0;

        $results = POS::whereBetween('created_at',[$start_time,$end_time])->pluck('items');
    
        foreach ($results as $result){
            foreach($result as $item){
                $data = [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) str_replace("Can"," ",$item['quantity']),
                    'price' => (float) $item['price'],
                    'discount' => (float) $item['discount'],
                    'total' => (float) str_replace("$"," ",$item['total'])
                ];
                array_push($items,$data);
            }
        }

        foreach($items as $item){
            $index = $this->findExistingValueInArray($arrange_items,$item['product_name']);

            if($index){
                $arrange_items[$index-1]['quantity'] += $item['quantity'];
                // $arrange_items[$index-1]['price'] += $item['price'];
                $arrange_items[$index-1]['discount'] += $item['discount'];
                $arrange_items[$index-1]['total'] += $item['total'];
            }else{
                array_push($arrange_items,$item);
            }
        }

        foreach($arrange_items as $item){

            $data = [
                'product_name' => $item['product_name'],
                'quantity' => strval($item['quantity']),
                'price' => '$'.$item['price'],
                'discount' => '$'.$item['discount'],
                'total' => '$'.$item['total']
            ];
            array_push($invoice,$data);
        }

        foreach($arrange_items as $item){
            $total += $item['total'];
        }

        $total_riel = 'R'.number_format($total * 4200);
        
        // $this->printInvoice($invoice,'$'.$total,$total_riel);
        return response()->json([
            'code' => 200,
        ]);
    }

    private function findExistingValueInArray($arrays, $value){
        foreach($arrays as $key => $array){
            if($array['product_name'] === $value){
                return $key+1;
            }
        }

        return 0;
    }
}
