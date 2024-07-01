<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Delivery;

class DeliveryController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $deliveries = Delivery::All();
        return view('delivery.view',[
            'deliveries' => $deliveries
        ]);
    }

    public function store(Request $request){
        $data = [
            "name" => $request->name,
        ];

        Delivery::create($data);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        Delivery::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request){
        $data = [
            "name" => $request->name,
        ];

        $id = $request->id;

        Delivery::find($id)->update($data);
        return response()->json([
            'code' => 200,
        ]);        
    }

}