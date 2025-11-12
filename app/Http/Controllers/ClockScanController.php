<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\ClockInOut;
use App\User;
use Carbon\Carbon;

class ClockScanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Only MANAGER and ADMIN can access scanning
        if (!$user->isManager() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Access denied. Managers and Admins only.');
        }

        // Get today's clock records
        $todayRecords = ClockInOut::with('user')
            ->whereDate('clock_in_time', Carbon::today())
            ->orderBy('clock_in_time', 'desc')
            ->get();

        return view('clockinout.scan', compact('todayRecords'));
    }

    public function processScan(Request $request)
    {
        $user = Auth::user();
        
        // Only MANAGER and ADMIN can process scans
        if (!$user->isManager() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied.']);
        }

        $request->validate([
            'barcode' => 'required|string'
        ]);

        $barcode = $request->barcode;

        // Find user by barcode - optimized with indexed query
        $staff = User::where('barcode', $barcode)->first();

        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid barcode. Staff not found.'
            ]);
        }

        // Check if staff has an active clock-in session
        $activeClock = ClockInOut::where('user_id', $staff->id)
            ->where('status', 'active')
            ->first();

        if ($activeClock) {
            // Clock Out
            $clockOutTime = Carbon::now();
            $activeClock->clock_out_time = $clockOutTime;
            $activeClock->status = 'completed';
            $activeClock->calculateTotalHours();

            return response()->json([
                'success' => true,
                'action' => 'clock_out',
                'staff_name' => $staff->username,
                'time' => $clockOutTime->format('h:i A'),
                'total_hours' => $activeClock->total_hours,
                'message' => $staff->username . ' clocked out at ' . $clockOutTime->format('h:i A')
            ]);
        } else {
            // Check if staff already has a completed session today (prevent multiple clock-ins per day)
            $todayCompletedSession = ClockInOut::where('user_id', $staff->id)
                ->whereDate('clock_in_time', Carbon::today())
                ->where('status', 'completed')
                ->first();

            if ($todayCompletedSession) {
                return response()->json([
                    'success' => false,
                    'message' => $staff->username . ' has already completed a clock session today. Only one clock in/out per day is allowed.'
                ]);
            }

            // Clock In
            $clockInTime = Carbon::now();
            $clockIn = ClockInOut::create([
                'user_id' => $staff->id,
                'clock_in_time' => $clockInTime,
                'status' => 'active'
            ]);

            return response()->json([
                'success' => true,
                'action' => 'clock_in',
                'staff_name' => $staff->username,
                'time' => $clockInTime->format('h:i A'),
                'message' => $staff->username . ' clocked in at ' . $clockInTime->format('h:i A')
            ]);
        }
    }

    public function getTodayRecords()
    {
        $user = Auth::user();
        
        if (!$user->isManager() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied.']);
        }

        $todayRecords = ClockInOut::with('user')
            ->whereDate('clock_in_time', Carbon::today())
            ->orderBy('clock_in_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'records' => $todayRecords
        ]);
    }
}
