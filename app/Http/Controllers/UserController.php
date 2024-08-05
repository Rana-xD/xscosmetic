<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class UserController extends Controller {

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('admin');
    }

    public function show(){
        $users = User::where('role','!=','SUPERADMIN')->get();
        return view('user.view',[
            'users' => $users
        ]);
    }

    public function store(Request $request){
        $username = $request->username;
        $password = $request->password;
        $role = $request->role;
        $data = [
            "username" => $username,
            "password" => Hash::make($password),
            "role" => $role
        ];
        DB::table('users')->insert($data);

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request){
        $id = (int)$request->id;
        User::destroy($id);
        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request){

        
        $id = $request->id;

        $user = User::find($id);
        $user->username = $request->username;
        $user->role = $request->role;
        if(isset($request->password)){
            $user->password = Hash::make($request->password);
        }
        $user->save();
        
        return response()->json([
            'code' => 200
        ]);        
    }

}