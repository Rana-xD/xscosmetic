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
            "order_no" => date('Ymd').strval(rand(1000,9999)),
            "items" => $request->data,
            "cashier" => Auth::user()->username,
            "time" => $this->getLocaleTime()
        ];

        $invoice = $request->invocie;
        $total = $request->total;
        $total_riel = $request->total_riel;
        $order = POS::create($data);

        $this->deductStock($data['items']);        
        $this->updateProductIncome($data['items']);
        // NewOrder::dispatch($order);
        $this->printInvoice($invoice,$total,$total_riel);
        return response()->json([
            'code' => 200,
            'data' => $invoice
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

        // Print receipt
        $printer->printReceipt();

    }
}
