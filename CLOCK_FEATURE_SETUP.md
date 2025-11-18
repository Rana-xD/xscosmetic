# Clock In/Out Feature - Late & Overtime Tracking

## Overview

The clock in/out feature now tracks late arrivals and overtime for each staff member based on their default work schedule.

## New Features

### 1. Default Clock Times

Each staff member can have default clock in and clock out times set in their user profile.

**Database Fields:**

-   `default_clock_in` - Expected start time (e.g., "09:00:00")
-   `default_clock_out` - Expected end time (e.g., "17:00:00")

### 2. Late Tracking

When a staff member clocks in after their `default_clock_in` time, the system automatically calculates how many minutes late they are.

### 3. Overtime Tracking

When a staff member clocks out after their `default_clock_out` time, the system automatically calculates overtime minutes.

### 4. Time Format

All time durations are now displayed in "X hours Y minutes" format instead of decimal hours (e.g., "1 hour 30 minutes" instead of "1.5 hrs").

## Setting Up Default Times for Staff

You can set default times for staff members through the user management interface or directly in the database.

### Option 1: User Management Interface (Recommended)

1. Navigate to the User Management page
2. Click "Edit" (pencil icon) on any user
3. Scroll down to see "Default Clock In Time" and "Default Clock Out Time" fields
4. Enter the expected times (e.g., 09:00 for clock in, 17:00 for clock out)
5. Click "Submit" to save

You can also set these times when creating a new user.

### Option 2: Direct Database Update (SQL)

```sql
-- Set default times for a specific user
UPDATE users
SET default_clock_in = '09:00:00',
    default_clock_out = '17:00:00'
WHERE id = 1;

-- Set default times for all staff members
UPDATE users
SET default_clock_in = '09:00:00',
    default_clock_out = '17:00:00'
WHERE role = 'STAFF';
```

### Option 2: Using Tinker

```bash
php artisan tinker
```

```php
// Set for a specific user
$user = App\User::find(1);
$user->default_clock_in = '09:00:00';
$user->default_clock_out = '17:00:00';
$user->save();

// Set for all staff
App\User::where('role', 'STAFF')->update([
    'default_clock_in' => '09:00:00',
    'default_clock_out' => '17:00:00'
]);
```

## Report Features

### Daily Report

-   Shows each clock in/out record
-   Displays late time and overtime for each record
-   Shows total late time and total overtime for the day

### Monthly Report

-   Calendar view showing all clock records for the month
-   Summary statistics including total late time and overtime

### Statistics Displayed

-   **Total Hours**: Total working hours for the period
-   **Total Late**: Sum of all late minutes in "X hours Y minutes" format
-   **Total Overtime**: Sum of all overtime minutes in "X hours Y minutes" format

## How It Works

1. **Clock In**: When a staff member clocks in, the system compares the actual clock in time with their `default_clock_in` time. If they're late, it calculates the difference.

2. **Clock Out**: When a staff member clocks out, the system:

    - Calculates total working hours
    - Compares clock out time with `default_clock_out` to calculate overtime
    - Saves all data to the database

3. **Reports**: Managers and admins can view reports showing:
    - Individual late and overtime records
    - Aggregate statistics for any time period
    - All times displayed in human-readable format

## Example Scenarios

### Scenario 1: On Time

-   Default: 09:00 - 17:00
-   Actual: 08:55 - 17:00
-   Late: 0 minutes
-   Overtime: 0 minutes

### Scenario 2: Late Arrival

-   Default: 09:00 - 17:00
-   Actual: 09:30 - 17:00
-   Late: 30 minutes
-   Overtime: 0 minutes

### Scenario 3: Overtime

-   Default: 09:00 - 17:00
-   Actual: 09:00 - 18:30
-   Late: 0 minutes
-   Overtime: 1 hour 30 minutes

### Scenario 4: Both Late and Overtime

-   Default: 09:00 - 17:00
-   Actual: 09:15 - 18:00
-   Late: 15 minutes
-   Overtime: 1 hour

## Notes

-   If a user doesn't have default times set, late and overtime will show as 0
-   Times are calculated based on the date of the clock in/out (handles overnight shifts correctly)
-   All calculations are done automatically when clocking in and out
