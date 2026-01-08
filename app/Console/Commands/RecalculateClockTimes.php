<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\ClockInOut;
use App\User;
use Carbon\Carbon;

class RecalculateClockTimes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clock:recalculate 
                            {--user-id= : Only recalculate for specific user ID}
                            {--date-from= : Only recalculate records from this date (YYYY-MM-DD)}
                            {--date-to= : Only recalculate records up to this date (YYYY-MM-DD)}
                            {--dry-run : Show what would be updated without actually updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate late time and overtime for existing clock records based on current user clock-in/out times';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('==============================================');
        $this->info('Clock Time Recalculation Command');
        $this->info('==============================================');
        $this->line('');

        $userId = $this->option('user-id');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('*** DRY RUN MODE - No changes will be saved ***');
            $this->line('');
        }

        // Build query
        $query = ClockInOut::with('user');

        if ($userId) {
            $query->where('user_id', $userId);
            $user = User::find($userId);
            if (!$user) {
                $this->error("User with ID $userId not found.");
                return 1;
            }
            $this->info("Processing user: {$user->username} (ID: {$userId})");

            // Show user's current schedule
            $this->line('');
            $this->info("Current Schedule for {$user->username}:");
            if ($user->weekday_clock_in && $user->weekday_clock_out) {
                $this->line("  Weekday: {$user->weekday_clock_in} - {$user->weekday_clock_out}");
            }
            if ($user->weekend_clock_in && $user->weekend_clock_out) {
                $this->line("  Weekend: {$user->weekend_clock_in} - {$user->weekend_clock_out}");
            }
            if ($user->default_clock_in && $user->default_clock_out) {
                $this->line("  Default: {$user->default_clock_in} - {$user->default_clock_out}");
            }
        } else {
            $this->info('Processing all users');
        }

        if ($dateFrom) {
            $query->where('clock_in_time', '>=', $dateFrom);
            $this->line("Date from: $dateFrom");
        }

        if ($dateTo) {
            $query->where('clock_in_time', '<=', $dateTo . ' 23:59:59');
            $this->line("Date to: $dateTo");
        }

        $this->line('');

        // Get records
        $records = $query->orderBy('clock_in_time', 'asc')->get();

        if ($records->isEmpty()) {
            $this->warn('No records found matching the criteria.');
            return 0;
        }

        $this->info("Found {$records->count()} records to process.");
        $this->line('');

        // Confirm before proceeding (unless dry-run)
        if (!$dryRun && !$this->confirm('Do you want to proceed with recalculation?', true)) {
            $this->warn('Operation cancelled.');
            return 0;
        }

        // Create progress bar
        $progressBar = $this->output->createProgressBar($records->count());
        $progressBar->setFormat('verbose');
        $progressBar->start();

        // Statistics
        $stats = [
            'total' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'skipped_no_user' => 0,
        ];

        $updates = [];

        // Process each record
        foreach ($records as $record) {
            $stats['total']++;

            if (!$record->user) {
                $stats['skipped_no_user']++;
                $progressBar->advance();
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

                $updates[] = [
                    'id' => $record->id,
                    'user' => $record->user->username,
                    'date' => $record->clock_in_time->format('Y-m-d'),
                    'day_type' => $dayType,
                    'old_late' => $oldLateMinutes,
                    'new_late' => $record->late_minutes,
                    'old_overtime' => $oldOvertimeMinutes,
                    'new_overtime' => $record->overtime_minutes,
                ];

                // Save if not dry run
                if (!$dryRun) {
                    $record->save();
                }
            } else {
                $stats['unchanged']++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->line('');
        $this->line('');

        // Show detailed updates
        if (!empty($updates)) {
            $this->info('Updated Records:');
            $this->line('');

            $tableData = [];
            foreach ($updates as $update) {
                $lateChange = $update['new_late'] - $update['old_late'];
                $overtimeChange = $update['new_overtime'] - $update['old_overtime'];

                $tableData[] = [
                    $update['id'],
                    $update['user'],
                    $update['date'],
                    $update['day_type'],
                    $update['old_late'] . ' → ' . $update['new_late'] . ' (' . ($lateChange >= 0 ? '+' : '') . $lateChange . ')',
                    $update['old_overtime'] . ' → ' . $update['new_overtime'] . ' (' . ($overtimeChange >= 0 ? '+' : '') . $overtimeChange . ')',
                ];
            }

            $this->table(
                ['ID', 'User', 'Date', 'Day Type', 'Late (min)', 'Overtime (min)'],
                $tableData
            );
        }

        // Show summary
        $this->line('');
        $this->info('==============================================');
        $this->info('Summary');
        $this->info('==============================================');
        $this->line("Total records processed: {$stats['total']}");
        $this->line("<fg=green>Records updated: {$stats['updated']}</>");
        $this->line("Records unchanged: {$stats['unchanged']}");

        if ($stats['skipped_no_user'] > 0) {
            $this->line("<fg=yellow>Records skipped (no user): {$stats['skipped_no_user']}</>");
        }

        if ($dryRun) {
            $this->line('');
            $this->warn('*** DRY RUN COMPLETED - No changes were saved ***');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->line('');
            $this->info('✓ Recalculation completed successfully!');
        }

        $this->info('==============================================');

        return 0;
    }
}
