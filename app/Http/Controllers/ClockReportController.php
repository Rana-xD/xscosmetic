<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\ClockInOut;
use App\User;
use Carbon\Carbon;

class ClockReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        
        // Only MANAGER, ADMIN, and SUPERADMIN can view reports
        if (!$user->isManager() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            return redirect()->back()->with('error', 'Access denied. Managers and Admins only.');
        }

        $reportType = request('report_type', 'daily');
        
        // Debug: Log what we're receiving
        \Log::info('Request user_id:', [
            'raw' => request()->input('user_id'),
            'query' => request()->query('user_id'),
            'all_inputs' => request()->all()
        ]);
        
        $userId = request('user_id');
        
        // Ensure userId defaults to 'all' if not provided or empty
        // Keep it as string to properly compare with 'all'
        if (empty($userId) || $userId === null) {
            $userId = 'all';
        } else {
            $userId = (string) $userId;
        }
        
        // Handle date/month input based on report type
        if ($reportType === 'monthly') {
            $month = request('month', Carbon::today()->format('Y-m'));
            $date = $month . '-01'; // Convert to first day of month
        } else {
            $date = request('date', Carbon::today()->format('Y-m-d'));
            $month = Carbon::parse($date)->format('Y-m');
        }

        $query = ClockInOut::with('user');

        if ($userId !== 'all') {
            $query->where('user_id', $userId);
        }

        switch ($reportType) {
            case 'daily':
                $query->whereDate('clock_in_time', $date);
                $title = "Daily Clock In/Out Report - " . Carbon::parse($date)->format('F j, Y');
                break;
                
            case 'monthly':
                $monthStart = Carbon::parse($date)->startOfMonth();
                $monthEnd = Carbon::parse($date)->endOfMonth();
                $query->whereBetween('clock_in_time', [$monthStart, $monthEnd]);
                $title = "Monthly Clock In/Out Report - " . $monthStart->format('F Y');
                break;
                
            default:
                $query->whereDate('clock_in_time', $date);
                $title = "Daily Clock In/Out Report - " . Carbon::parse($date)->format('F j, Y');
        }

        $records = $query->orderBy('clock_in_time', 'desc')->get();
        
        // Calculate statistics
        $stats = [
            'total_employees' => $records->pluck('user_id')->unique()->count(),
            'total_hours' => $records->sum('total_hours'),
            'average_hours_per_employee' => 0,
            'clock_ins' => $records->count(),
            'active_sessions' => $records->where('status', 'active')->count()
        ];

        if ($stats['total_employees'] > 0) {
            $stats['average_hours_per_employee'] = round($stats['total_hours'] / $stats['total_employees'], 2);
        }

        // Get staff users for filter dropdown
        $staffUsers = User::where('role', User::STAFF)->orderBy('username')->get();

        // Prepare monthly report data if needed
        $monthlyData = [];
        $datesInMonth = [];
        if ($reportType === 'monthly') {
            $monthStart = Carbon::parse($date)->startOfMonth();
            $monthEnd = Carbon::parse($date)->endOfMonth();
            
            // Generate all dates in the month
            $currentDate = $monthStart->copy();
            while ($currentDate <= $monthEnd) {
                $datesInMonth[] = $currentDate->copy();
                $currentDate->addDay();
            }
            
            // Group records by user and date
            $userRecords = [];
            foreach ($records as $record) {
                $userId = $record->user_id;
                $dateKey = $record->clock_in_time->format('Y-m-d');
                
                if (!isset($userRecords[$userId])) {
                    $userRecords[$userId] = [
                        'user' => $record->user,
                        'dates' => []
                    ];
                }
                
                if (!isset($userRecords[$userId]['dates'][$dateKey])) {
                    $userRecords[$userId]['dates'][$dateKey] = [];
                }
                
                $userRecords[$userId]['dates'][$dateKey][] = $record;
            }
            
            $monthlyData = $userRecords;
        }

        return view('clockinout.report', compact('records', 'title', 'stats', 'reportType', 'date', 'userId', 'staffUsers', 'monthlyData', 'datesInMonth'));
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isManager() && !$user->isAdmin() && !$user->isSuperAdmin()) {
            return response()->json(['success' => false, 'message' => 'Access denied.']);
        }

        $reportType = $request->report_type;
        $date = $request->date;
        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $userId = $request->user_id;

        $query = ClockInOut::with('user');

        if ($userId !== 'all') {
            $query->where('user_id', $userId);
        }

        switch ($reportType) {
            case 'daily':
                $query->whereDate('clock_in_time', $date);
                $filename = "daily_clock_report_" . $date . ".csv";
                break;
            case 'weekly':
                $weekStart = Carbon::parse($date)->startOfWeek();
                $weekEnd = Carbon::parse($date)->endOfWeek();
                $query->whereBetween('clock_in_time', [$weekStart, $weekEnd]);
                $filename = "weekly_clock_report_" . $date . ".csv";
                break;
            case 'monthly':
                $monthStart = Carbon::parse($date)->startOfMonth();
                $monthEnd = Carbon::parse($date)->endOfMonth();
                $query->whereBetween('clock_in_time', [$monthStart, $monthEnd]);
                $filename = "monthly_clock_report_" . $date . ".csv";
                break;
            case 'custom':
                $query->whereBetween('clock_in_time', [$startDate, $endDate]);
                $filename = "custom_clock_report_" . $startDate . "_to_" . $endDate . ".csv";
                break;
        }

        $records = $query->orderBy('clock_in_time', 'desc')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($records) {
            $file = fopen('php://output', 'w');
            
            // CSV header
            fputcsv($file, [
                'Employee Name',
                'Clock In Time',
                'Clock Out Time',
                'Total Hours',
                'Status',
                'Notes'
            ]);
            
            // CSV data
            foreach ($records as $record) {
                fputcsv($file, [
                    $record->user->username,
                    $record->clock_in_time->format('Y-m-d H:i:s'),
                    $record->clock_out_time ? $record->clock_out_time->format('Y-m-d H:i:s') : 'N/A',
                    $record->total_hours ?: 'N/A',
                    $record->status,
                    $record->notes ?: ''
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
