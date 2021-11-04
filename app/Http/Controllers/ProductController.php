<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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


        if($request->hasFile('photo')){
            $filenameWithExt = $request->file('photo')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            $path = $request->file('photo')->storeAs('public/product_images',$fileNameToStore);
        }else{
            $fileNameToStore = 'default.jpg';
        }
        $data = [
            "name" => $request->name,
            "product_barcode" => $request->product_barcode,
            "category_id" => $request->category_id,
            "unit_id" => $request->unit_id,
            "stock" =>$request->stock,
            "size" =>$request->size,
            "price" =>$request->price,
            "cost" => $request->cost,
            "photo" => $fileNameToStore
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

        $default_img = 'default.jpg';
        $id = $request->id;
        $product = Product::find($id);
        $data = [
            "name" => $request->name,
            "product_barcode" => $request->product_barcode,
            "category_id" => $request->category_id,
            "unit_id" =>$request->unit_id,
            "stock" =>$request->stock,
            "size" =>$request->size,
            "price" =>$request->price,
            "cost" => $request->cost,
        ];

        if($request->hasFile('photo')){
            if($product->photo != $default_img){    
                unlink(storage_path('app/public/product_images/'.$product->photo));
            }
            
            $filenameWithExt = $request->file('photo')->getClientOriginalName();
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            $extension = $request->file('photo')->getClientOriginalExtension();
            $fileNameToStore = $filename.'_'.time().'.'.$extension;
            $path = $request->file('photo')->storeAs('public/product_images',$fileNameToStore);
            $data['photo'] = $fileNameToStore;
        }
        
        
        Product::find($id)->update($data);
        ProductIncome::where('product_id',$id)->update(['product_name' => $request->name]);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

}
