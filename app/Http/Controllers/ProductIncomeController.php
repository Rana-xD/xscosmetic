<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\ProductIncome;

class ProductIncomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $products = ProductIncome::all();
        return view('income_report.product_income',[
            'products' => $products
        ]);
    }
}
