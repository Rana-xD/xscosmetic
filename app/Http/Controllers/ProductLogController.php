<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductLog;
use Carbon\Carbon;


class ProductLogController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
    }


    public function show()
    {
        $today = Carbon::now()->format('Y-m-d');
        $product_log = ProductLog::where('date', $today)->first();
        return view('product_log', [
            'date' => $today,
            'product_log' => $product_log,
        ]);
    }

    public function showCustomProductLog(Request $request)
    {
        $date = $request->date;
        $product_log = ProductLog::where('date', $date)->first();

        return view('product_log', [
            'date' => $date,
            'product_log' => $product_log
        ]);
    }

}
