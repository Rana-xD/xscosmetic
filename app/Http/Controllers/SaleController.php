<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\POS;
use Carbon\Carbon;

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

    public function cusomterIncomeReport(Request $request){
        $start_date = date($request->start_date).' 00:00:00';
        $end_date = empty($request->end_date) ? Carbon::now()->format('Y-m-d').' 23:59:59' :  date($request->end_date).' 23:59:59';

        $orders = POS::whereBetween('created_at',[$start_date,$end_date])->orderBy('created_at', 'DESC')->paginate(30);

        // return $orders;
        return view('sale',[
            'orders' => $orders,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);
    }

    public function showInvoice(){
        $orders = POS::orderBy('created_at', 'DESC')->paginate(30);
        return view('invoice',[
            'orders' => $orders
        ]);
    }

    public function showCustomInvoice(Request $request){    
        $start_date = date($request->start_date).' 00:00:00';
        $end_date = empty($request->end_date) ? Carbon::now()->format('Y-m-d').' 23:59:59' :  date($request->end_date).' 23:59:59';

        $orders = POS::whereBetween('created_at',[$start_date,$end_date])->orderBy('created_at', 'DESC')->paginate(30);

        return view('invoice',[
            'orders' => $orders,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date
        ]);
    }


}
