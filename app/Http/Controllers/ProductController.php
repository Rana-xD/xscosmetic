<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Product;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $products = Product::All();
        return view('product.view',[
            'products' => $products
        ]);
    }

    public function store(Request $request){
        $data = [
            "product_no" => $request->product_no,
            "product_name" => $request->name,
            'category_id' => $request->category_id,
            "price" => '0',
            "photo" => "TEXT"
        ];

        Product::create($data);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        Product::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

}
