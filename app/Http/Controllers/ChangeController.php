<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Change;
use Carbon\Carbon;

class ChangeController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('adminormanager');
    }

    public function show(){
        $change_lists = Change::All();
        return view('change.view',[
            'change_lists' => $change_lists
        ]);
    }

    public function store(Request $request){
        $data = [
            "usd" => $request->usd,
            "riel" => $request->riel,
            "date" => Carbon::now()
        ];

        $exist = Change::where('date', Carbon::now()->format('Y-m-d'))->first();

        if($exist){
            return response()->json([
                'code' => 400
            ]);
        }

        Change::create($data);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        Change::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request){
        $data = [
            "usd" => $request->usd,
            "riel" => $request->riel
        ];

        $id = $request->id;

        Change::find($id)->update($data);
        return response()->json([
            'code' => 200,
            'data' => $data
        ]);        
    }

}