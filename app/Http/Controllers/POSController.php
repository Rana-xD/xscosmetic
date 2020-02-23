<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Product;
use App\POS;
use App\Events\NewOrder;

class POSController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $products = Product::All();
        return view('pos',[
            'products' => $products
        ]);
    }

    public function store(Request $request){
        $data = [
            "order_no" => strval(rand(1000,9999)),
            "items" => $request->data
        ];
        $order = POS::create($data);
        NewOrder::dispatch($order);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }
}
