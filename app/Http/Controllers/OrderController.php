<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\POS;

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
}
