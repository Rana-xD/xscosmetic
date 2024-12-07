<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\POS;
use charlieuki\ReceiptPrinter\ReceiptPrinter as ReceiptPrinter;

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

        $printer = new ReceiptPrinter;
        $printer->init(
            config('receiptprinter.connector_type'),
            config('receiptprinter.connector_descriptor')
        );

        $additional_info = $order->additional_info;

        // Set store info
        $printer->setStore('cosmetic');
        $printer->setCashier($order->cashier);
        $printer->setTotal($additional_info['total']);
        $printer->setTotalRiel($additional_info['total_riel']);
        $printer->setTotalDiscount($additional_info['total_discount']);
        $printer->setReceivedInUsd($additional_info['received_in_usd']);
        $printer->setReceivedInRiel($additional_info['received_in_riel']);
        $printer->setChangeInUsd($additional_info['change_in_usd']);
        $printer->setChangeInRiel($additional_info['change_in_riel']);
        $printer->setLogo(public_path('img/logo.png'));
        $printer->setNote(public_path('img/invoice_image.png'));

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
}
