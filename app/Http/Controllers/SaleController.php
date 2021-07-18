<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\POS;
class SaleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $orders = POS::orderBy('created_at', 'DESC')->paginate(30);
        return view('sale',[
            'orders' => $orders
        ]);
    }

}
