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
        $location = $request->location ?: 'Phnom Penh';
        // Use the cost from the request if provided, otherwise use default based on location
        $cost = $request->has('cost') && is_numeric($request->cost) 
            ? (float) $request->cost 
            : (($location === 'Phnom Penh') ? 1.5 : 2.0);
        
        $data = [
            "name" => $request->name,
            "location" => $location,
            "cost" => $cost
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
        $location = $request->location ?: 'Phnom Penh';
        // Use the cost from the request if provided, otherwise use default based on location
        $cost = $request->has('cost') && is_numeric($request->cost) 
            ? (float) $request->cost 
            : (($location === 'Phnom Penh') ? 1.5 : 2.0);
        
        $data = [
            "name" => $request->name,
            "location" => $location,
            "cost" => $cost
        ];

        $id = $request->id;

        Delivery::find($id)->update($data);
        return response()->json([
            'code' => 200,
        ]);        
    }

}