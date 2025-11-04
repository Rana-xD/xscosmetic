<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\ClockInOut;
use Carbon\Carbon;

class ClockInOutController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Only staff can access clock in/out functionality
        if (!$user->isStaff()) {
            return redirect()->back()->with('error', 'Access denied. Staff only.');
        }

        $activeClock = ClockInOut::forUser($user->id)
            ->active()
            ->first();

        $todayClocks = ClockInOut::forUser($user->id)
            ->whereDate('clock_in_time', Carbon::today())
            ->completed()
            ->orderBy('clock_in_time', 'desc')
            ->get();

        $weekClocks = ClockInOut::forUser($user->id)
            ->whereBetween('clock_in_time', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->completed()
            ->orderBy('clock_in_time', 'desc')
            ->get();

        $totalHoursThisWeek = $weekClocks->sum('total_hours');

        return view('clockinout.index', compact('activeClock', 'todayClocks', 'weekClocks', 'totalHoursThisWeek'));
    }

    public function clockIn(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Access denied. Staff only.']);
        }

        // Check if user already has an active clock-in
        $activeClock = ClockInOut::where('user_id', $user->id)
            ->where('status', 'active')
            ->first();
            
        if ($activeClock) {
            return response()->json([
                'success' => false,
                'message' => 'You are already clocked in. Please clock out first.'
            ]);
        }
        
        // Check if user already clocked in today (completed session)
        $todayClockIn = ClockInOut::where('user_id', $user->id)
            ->whereDate('clock_in_time', today())
            ->where('status', 'completed')
            ->first();
            
        if ($todayClockIn) {
            return response()->json([
                'success' => false,
                'message' => 'You have already clocked in and out today. Only one session per day is allowed.'
            ]);
        }
        
        // Create new clock-in record
        $clockIn = ClockInOut::create([
            'user_id' => $user->id,
            'clock_in_time' => now(),
            'notes' => $request->notes,
            'status' => 'active'
        ]);
        
        return response()->json([
            'success' => true,
            'message' => 'Successfully clocked in at ' . $clockIn->clock_in_time->format('h:i A')
        ]);
    }

    public function clockOut(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Access denied. Staff only.']);
        }

        $activeClock = ClockInOut::forUser($user->id)
            ->active()
            ->first();

        if (!$activeClock) {
            return response()->json(['success' => false, 'message' => 'No active clock in session found.']);
        }

        $clockOutTime = Carbon::now();
        $activeClock->clock_out_time = $clockOutTime;
        $activeClock->notes = $request->notes ?: $activeClock->notes;
        $activeClock->status = 'completed';
        $activeClock->calculateTotalHours();

        return response()->json([
            'success' => true, 
            'message' => 'Clocked out successfully at ' . $clockOutTime->format('h:i A'),
            'total_hours' => $activeClock->total_hours,
            'clock_out_time' => $clockOutTime->format('Y-m-d H:i:s')
        ]);
    }

    public function getStatus()
    {
        $user = Auth::user();
        
        if (!$user->isStaff()) {
            return response()->json(['success' => false, 'message' => 'Access denied.']);
        }

        $activeClock = ClockInOut::forUser($user->id)
            ->active()
            ->first();

        $status = [
            'is_clocked_in' => $activeClock ? true : false,
            'clock_in_time' => $activeClock ? $activeClock->clock_in_time->format('Y-m-d H:i:s') : null,
            'current_hours' => $activeClock ? 
                round($activeClock->clock_in_time->diffInMinutes(Carbon::now()) / 60, 2) : 0
        ];

        return response()->json(['success' => true, 'status' => $status]);
    }
}
