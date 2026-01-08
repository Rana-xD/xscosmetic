<?php

/**
 * Recalculate Late Time and Overtime for Existing Clock Records
 * 
 * This script recalculates late_minutes and overtime_minutes for all existing
 * clock_in_out records based on the user's current clock-in/out times.
 * 
 * Usage:
 *   php recalculate_clock_times.php                    # Recalculate for all users
 *   php recalculate_clock_times.php --user-id=15       # Recalculate for specific user
 *   php recalculate_clock_times.php --user-id=15 --date-from=2025-12-01  # Specific user and date range
 * 
 * Options:
 *   --user-id=ID           Only recalculate for specific user ID
 *   --date-from=YYYY-MM-DD Only recalculate records from this date onwards
 *   --date-to=YYYY-MM-DD   Only recalculate records up to this date
 *   --dry-run              Show what would be updated without actually updating
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\ClockInOut;
use App\User;
use Carbon\Carbon;

// Parse command line arguments
$options = getopt('', ['user-id:', 'date-from:', 'date-to:', 'dry-run']);

$userId = isset($options['user-id']) ? (int)$options['user-id'] : null;
$dateFrom = isset($options['date-from']) ? $options['date-from'] : null;
$dateTo = isset($options['date-to']) ? $options['date-to'] : null;
$dryRun = isset($options['dry-run']);

echo "==============================================\n";
echo "Clock Time Recalculation Script\n";
echo "==============================================\n\n";

if ($dryRun) {
    echo "*** DRY RUN MODE - No changes will be saved ***\n\n";
}

// Build query
$query = ClockInOut::with('user');

if ($userId) {
    $query->where('user_id', $userId);
    $user = User::find($userId);
    if (!$user) {
        echo "ERROR: User with ID $userId not found.\n";
        exit(1);
    }
    echo "Processing user: {$user->username} (ID: {$userId})\n";
} else {
    echo "Processing all users\n";
}

if ($dateFrom) {
    $query->where('clock_in_time', '>=', $dateFrom);
    echo "Date from: $dateFrom\n";
}

if ($dateTo) {
    $query->where('clock_in_time', '<=', $dateTo . ' 23:59:59');
    echo "Date to: $dateTo\n";
}

echo "\n";

// Get records
$records = $query->orderBy('clock_in_time', 'asc')->get();

if ($records->isEmpty()) {
    echo "No records found matching the criteria.\n";
    exit(0);
}

echo "Found {$records->count()} records to process.\n\n";

// Statistics
$stats = [
    'total' => 0,
    'updated' => 0,
    'unchanged' => 0,
    'skipped_no_user' => 0,
    'skipped_no_checkout' => 0,
];

// Process each record
foreach ($records as $record) {
    $stats['total']++;

    if (!$record->user) {
        $stats['skipped_no_user']++;
        echo "⚠ Skipped Record #{$record->id}: User not found\n";
        continue;
    }

    // Store old values
    $oldLateMinutes = $record->late_minutes;
    $oldOvertimeMinutes = $record->overtime_minutes;

    // Recalculate
    $record->calculateLateAndOvertime();

    // Check if values changed
    $lateChanged = $oldLateMinutes != $record->late_minutes;
    $overtimeChanged = $oldOvertimeMinutes != $record->overtime_minutes;

    if ($lateChanged || $overtimeChanged) {
        $stats['updated']++;

        $dayType = in_array($record->clock_in_time->dayOfWeek, [0, 6]) ? 'Weekend' : 'Weekday';

        echo "✓ Updated Record #{$record->id} - {$record->user->username} - {$record->clock_in_time->format('Y-m-d')} ($dayType)\n";
        echo "  Clock In: {$record->clock_in_time->format('H:i')}\n";

        if ($lateChanged) {
            echo "  Late: {$oldLateMinutes} min → {$record->late_minutes} min";
            if ($record->late_minutes > $oldLateMinutes) {
                echo " (+" . ($record->late_minutes - $oldLateMinutes) . ")";
            } else if ($record->late_minutes < $oldLateMinutes) {
                echo " (" . ($record->late_minutes - $oldLateMinutes) . ")";
            }
            echo "\n";
        }

        if ($record->clock_out_time) {
            echo "  Clock Out: {$record->clock_out_time->format('H:i')}\n";

            if ($overtimeChanged) {
                echo "  Overtime: {$oldOvertimeMinutes} min → {$record->overtime_minutes} min";
                if ($record->overtime_minutes > $oldOvertimeMinutes) {
                    echo " (+" . ($record->overtime_minutes - $oldOvertimeMinutes) . ")";
                } else if ($record->overtime_minutes < $oldOvertimeMinutes) {
                    echo " (" . ($record->overtime_minutes - $oldOvertimeMinutes) . ")";
                }
                echo "\n";
            }
        }

        echo "\n";

        // Save if not dry run
        if (!$dryRun) {
            $record->save();
        }
    } else {
        $stats['unchanged']++;

        if ($stats['unchanged'] <= 5) { // Only show first 5 unchanged
            echo "- No change Record #{$record->id} - {$record->user->username} - {$record->clock_in_time->format('Y-m-d')}\n";
        }
    }
}

// Show summary
echo "\n==============================================\n";
echo "Summary\n";
echo "==============================================\n";
echo "Total records processed: {$stats['total']}\n";
echo "Records updated: {$stats['updated']}\n";
echo "Records unchanged: {$stats['unchanged']}\n";

if ($stats['skipped_no_user'] > 0) {
    echo "Records skipped (no user): {$stats['skipped_no_user']}\n";
}

if ($dryRun) {
    echo "\n*** DRY RUN COMPLETED - No changes were saved ***\n";
    echo "Run without --dry-run to apply changes.\n";
} else {
    echo "\n✓ Recalculation completed successfully!\n";
}

echo "==============================================\n";
