<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Product;
use App\ProductLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('adminormanager');
    }

    public function show(){
        $products = Product::with('category')->get();

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
            "product_barcode" => $request->product_barcode === '' ? null : $request->product_barcode,
            "category_id" => $request->category_id,
            // "unit_id" => $request->unit_id,
            "stock" =>$request->stock,
            "expire_date" =>$request->expire_date,
            "price" =>$request->price === 0 ? 0 : $request->price,
            "cost" => $request->cost === 0 ? 0 : $request->cost,
            "cost_group" => $request->cost === 0 ? [] : [$request->cost],
            "photo" => $fileNameToStore
        ];

        $product_exist = Product::where('product_barcode',$request->product_barcode)->where('name',$request->name)->first();

        if($product_exist){
            return response()->json([
                'code' => 404
            ]);
        }

        $result = Product::create($data);

        $time = Carbon::now()->format('h:i A');
        $this->createProductLog($result, 'CREATE', Auth::user()->username, $time);

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
        $stock = $request->stock + intval($request->new_stock);
        $data = [
            "name" => $request->name,
            "product_barcode" => $request->product_barcode === '' ? null : $request->product_barcode,
            "category_id" => $request->category_id,
            // "unit_id" =>$request->unit_id,
            "stock" => $stock,
            "expire_date" =>$request->expire_date,
            "price" =>$request->price === 0 ? 0 : $request->price,
        ];
        $costGroupData = explode(', ', $request->cost);
        $data['cost_group'] = $costGroupData;
        if($request->new_cost != 0){
            $data['cost'] = $request->new_cost;
            $costGroupData = array_filter(explode(', ', $request->cost), function($value) {
                return $value !== '0';
            });
            array_push($costGroupData, $request->new_cost);
            $data['cost_group'] = $costGroupData;
        }

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
        // ProductIncome::where('product_id',$id)->update(['product_name' => $request->name]);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    private function createProductLog($product,$action,$creator, $time){
        $today = Carbon::now()->format('Y-m-d');
        $product_log = ProductLog::where('date', $today)->first();
        if(empty($product_log)){
            $item = [
                'id' => $product->id,
                'name' => $product->name,
                'action' => $action,
                'creator' => $creator,
                'time' => $time
            ];
            $data = [
                'date' => $today,
                'items' => [$item]
            ];
            ProductLog::create($data);
        }else{
            
            $item = [
                'id' => $product->id,
                'name' => $product->name,
                'action' => $action,
                'creator' => $creator,
                'time' => $time
            ];
            $product_log->items = array_merge($product_log->items, [$item]);
            $product_log->save();
        }
        
    }

}
