<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\UserCacheService;


class UserController extends Controller
{

    protected $userCacheService;

    public function __construct(UserCacheService $userCacheService)
    {
        $this->middleware('auth');
        $this->middleware('admin');
        $this->userCacheService = $userCacheService;
    }

    public function show()
    {
        // Use cached users instead of querying database every time
        $users = $this->userCacheService->getUsers();
        return view('user.view', [
            'users' => $users
        ]);
    }

    public function store(Request $request)
    {
        $username = $request->username;
        $password = $request->password;
        $role = $request->role;
        $barcode = $request->barcode;
        $data = [
            "username" => $username,
            "password" => Hash::make($password),
            "role" => $role,
            "barcode" => $barcode,
            "default_clock_in" => $request->default_clock_in,
            "default_clock_out" => $request->default_clock_out
        ];
        DB::table('users')->insert($data);

        // Clear cache after creating new user
        $this->userCacheService->clearCache();

        return response()->json([
            'code' => 200,
            'data' => $data
        ]);
    }

    public function destroy(Request $request)
    {
        $id = (int)$request->id;

        // Use cache service to delete user and clear cache
        $this->userCacheService->deleteUser($id);

        return response()->json([
            'code' => 200
        ]);
    }

    public function update(Request $request)
    {
        $id = $request->id;

        $user = User::find($id);
        $user->username = $request->username;
        $user->role = $request->role;
        $user->barcode = $request->barcode;
        $user->default_clock_in = $request->default_clock_in;
        $user->default_clock_out = $request->default_clock_out;
        if (isset($request->password) && !empty($request->password)) {
            $user->password = Hash::make($request->password);
        }
        $user->save();

        // Clear cache after updating user
        $this->userCacheService->clearUserCache($id);
        $this->userCacheService->clearCache();

        return response()->json([
            'code' => 200
        ]);
    }

    public function resetLockout(Request $request)
    {
        // Only SUPERADMIN can reset lockout
        if (!auth()->user()->isSuperAdmin()) {
            return response()->json([
                'code' => 403,
                'message' => 'Unauthorized. Only SUPERADMIN can reset lockout.'
            ], 403);
        }

        $id = $request->id;
        $user = User::find($id);

        if (!$user) {
            return response()->json([
                'code' => 404,
                'message' => 'User not found.'
            ], 404);
        }

        // Reset lockout fields
        $user->update([
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'lockout_level' => 0
        ]);

        // Clear cache after resetting lockout
        $this->userCacheService->clearUserCache($id);
        $this->userCacheService->clearCache();

        return response()->json([
            'code' => 200,
            'message' => 'Lockout reset successfully.'
        ]);
    }
}
