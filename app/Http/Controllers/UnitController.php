<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Unit;

class UnitController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $units = Unit::All();
        return view('unit.view',[
            'units' => $units
        ]);
    }

    public function store(Request $request){
        $data = [
            "name" => $request->name,
        ];

        Unit::create($data);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        Unit::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request){
        $data = [
            "name" => $request->name,
        ];

        $id = $request->id;

        Unit::find($id)->update($data);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);        
    }

}