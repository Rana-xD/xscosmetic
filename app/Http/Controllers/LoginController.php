<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\User;
use Redirect;
class LoginController extends Controller
{
    public function showLogin(){
        return view('auth.login');
    }

    public function login(Request $request){
        $credentials = $request->only('username', 'password');
        $remember = true;
        if (Auth::attempt($credentials,$remember)) {
            // Authentication passed...
            if(Auth::user()->isAdmin()){
                return Redirect::to('/pos');
            }
            else{
                return Redirect::to('/order');
            }
            
            
        }
        return "NO";
    }

    public function logout(){
        Auth::logout();
        return redirect()->intended('/');
    }
}
