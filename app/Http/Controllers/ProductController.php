<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Product;
use App\ProductIncome;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $products = Product::with('category','unit')->get();

        return view('product.view',[
            'products' => $products
        ]);
    }

    public function store(Request $request){
        $data = [
            "name" => $request->name,
            "category_id" => $request->category_id,
            "unit_id" => $request->unit_id,
            "stock" =>$request->stock,
            "size" =>$request->size,
            "price" =>$request->price,
            "cost" => $request->cost,
            "photo" => "TEXT"
        ];

        $result = Product::create($data);

        $init_data = [
            'product_id' => $result->id,
            'unit_id' => $request->unit_id,
            'product_name' => $request->name,
        ];

        ProductIncome::create($init_data);

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

    public function update(Request $request){
        $data = [
            "name" => $request->name,
            "category_id" => $request->category_id,
            "unit_id" =>$request->unit_id,
            "stock" =>$request->stock,
            "size" =>$request->size,
            "price" =>$request->price,
            "cost" => $request->cost,
            "photo" => "TEXT"
        ];
        $id = $request->id;
        Product::find($id)->update($data);
        ProductIncome::where('product_id',$id)->update(['product_name' => $request->name]);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

}
