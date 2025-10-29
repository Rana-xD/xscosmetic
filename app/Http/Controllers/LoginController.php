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
        
        // Find user by username
        $user = User::where('username', $credentials['username'])->first();
        
        if ($user) {
            // Check if account is locked
            if ($user->locked_until && now()->lessThan($user->locked_until)) {
                $remainingMinutes = now()->diffInMinutes($user->locked_until) + 1;
                return back()->with('error', "Account is locked. Please try again in {$remainingMinutes} minute(s).");
            }
            
            // Reset lockout if time has passed
            if ($user->locked_until && now()->greaterThanOrEqualTo($user->locked_until)) {
                $user->update([
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'lockout_level' => 0
                ]);
            }
        }
        
        if (Auth::attempt($credentials, $remember)) {
            // Authentication passed - reset failed attempts
            if ($user) {
                $user->update([
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                    'lockout_level' => 0
                ]);
            }
            return Redirect::to('/pos');
        }
        
        // Authentication failed - increment failed attempts
        if ($user) {
            $failedAttempts = $user->failed_login_attempts + 1;
            
            // Progressive lockout logic
            // 3 attempts = 10 min, 6 attempts = 20 min, 9 attempts = 30 min, etc. (max 60 min)
            if ($failedAttempts % 3 == 0) {
                $lockoutLevel = $user->lockout_level + 1;
                $lockoutMinutes = min($lockoutLevel * 10, 60); // Max 60 minutes
                
                $user->update([
                    'failed_login_attempts' => $failedAttempts,
                    'locked_until' => now()->addMinutes($lockoutMinutes),
                    'lockout_level' => $lockoutLevel
                ]);
                
                return back()->with('error', "Too many failed attempts. Account locked for {$lockoutMinutes} minute(s).");
            } else {
                $user->update([
                    'failed_login_attempts' => $failedAttempts
                ]);
                
                $attemptsRemaining = 3 - ($failedAttempts % 3);
                return back()->with('error', "Incorrect credentials. {$attemptsRemaining} attempt(s) remaining before lockout.");
            }
        }
        
        return back()->with('message', 'Incorrect Credentials');
    }

    public function logout(){
        Auth::logout();
        return redirect()->intended('/');
    }
}
