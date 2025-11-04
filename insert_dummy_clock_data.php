<?php

/**
 * Script to insert dummy clock in/out data for staff users
 * Date range: October 1, 2025 to Today
 * Clock In: Around 8:00 AM (random minutes)
 * Clock Out: Around 6:00 PM (random minutes)
 * 
 * Usage: php insert_dummy_clock_data.php
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\User;
use App\ClockInOut;
use Carbon\Carbon;

echo "Starting to insert dummy clock in/out data...\n\n";

// Get all staff users
$staffUsers = User::where('role', 'STAFF')->get();

if ($staffUsers->isEmpty()) {
    echo "No staff users found in the database.\n";
    exit;
}

echo "Found " . $staffUsers->count() . " staff user(s):\n";
foreach ($staffUsers as $user) {
    echo "  - {$user->username} (ID: {$user->id})\n";
}
echo "\n";

// Date range: October 1, 2025 to Today
$startDate = Carbon::create(2025, 10, 1);
$endDate = Carbon::today();

echo "Inserting data from {$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}\n\n";

$totalRecords = 0;

// Loop through each staff user
foreach ($staffUsers as $user) {
    echo "Processing user: {$user->username}\n";
    
    $currentDate = $startDate->copy();
    
    // Loop through each day
    while ($currentDate->lte($endDate)) {
        // Skip weekends (optional - remove this if you want to include weekends)
        if ($currentDate->isWeekend()) {
            $currentDate->addDay();
            continue;
        }
        
        // Random clock in time around 8:00 AM (between 7:45 AM and 8:15 AM)
        $clockInMinutes = rand(-15, 15); // Random minutes between -15 and +15
        $clockInTime = $currentDate->copy()->setTime(8, 0)->addMinutes($clockInMinutes);
        
        // Random clock out time around 6:00 PM (between 5:45 PM and 6:15 PM)
        $clockOutMinutes = rand(-15, 15); // Random minutes between -15 and +15
        $clockOutTime = $currentDate->copy()->setTime(18, 0)->addMinutes($clockOutMinutes);
        
        // Calculate total hours
        $totalHours = $clockInTime->diffInMinutes($clockOutTime) / 60;
        
        // Check if record already exists for this user on this date
        $existingRecord = ClockInOut::where('user_id', $user->id)
            ->whereDate('clock_in_time', $currentDate->format('Y-m-d'))
            ->first();
        
        if (!$existingRecord) {
            // Create clock in/out record
            ClockInOut::create([
                'user_id' => $user->id,
                'clock_in_time' => $clockInTime,
                'clock_out_time' => $clockOutTime,
                'total_hours' => round($totalHours, 2),
                'status' => 'completed',
                'notes' => 'Auto-generated dummy data'
            ]);
            
            $totalRecords++;
            echo "  ✓ {$currentDate->format('Y-m-d')}: {$clockInTime->format('h:i A')} - {$clockOutTime->format('h:i A')} ({$totalHours} hrs)\n";
        } else {
            echo "  - {$currentDate->format('Y-m-d')}: Already exists, skipping\n";
        }
        
        $currentDate->addDay();
    }
    
    echo "\n";
}

echo "========================================\n";
echo "Completed! Total records inserted: {$totalRecords}\n";
echo "========================================\n";
