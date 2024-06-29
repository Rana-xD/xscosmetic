<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Setting;


class SettingController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $setting = Setting::first();
        return view('setting.view',[
            'setting' => $setting
        ]);
    }

    public function update(Request $request){
        $data = [
            "exchange_rate" => $request->exchange_rate,
        ];

        Setting::find(1)->update($data);
        return response()->json([
            'code' => 200
        ]);        
    }

}