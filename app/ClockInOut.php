<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ClockInOut extends Model
{
    protected $table = 'clock_in_out';

    protected $fillable = [
        'user_id',
        'clock_in_time',
        'clock_out_time',
        'notes',
        'total_hours',
        'late_minutes',
        'overtime_minutes',
        'status'
    ];

    protected $casts = [
        'clock_in_time' => 'datetime',
        'clock_out_time' => 'datetime',
        'total_hours' => 'decimal:2'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function calculateTotalHours()
    {
        if ($this->clock_out_time && $this->clock_in_time) {
            $this->total_hours = $this->clock_in_time->diffInMinutes($this->clock_out_time) / 60;
            $this->calculateLateAndOvertime();
            $this->save();
        }
    }

    public function calculateLateAndOvertime()
    {
        $user = $this->user;

        if (!$user || !$user->default_clock_in || !$user->default_clock_out) {
            $this->late_minutes = 0;
            $this->overtime_minutes = 0;
            return;
        }

        // Calculate late minutes (clock in after default time)
        $defaultClockIn = \Carbon\Carbon::parse($this->clock_in_time->format('Y-m-d') . ' ' . $user->default_clock_in);
        if ($this->clock_in_time->gt($defaultClockIn)) {
            $this->late_minutes = $defaultClockIn->diffInMinutes($this->clock_in_time);
        } else {
            $this->late_minutes = 0;
        }

        // Calculate overtime minutes (clock out after default time)
        if ($this->clock_out_time) {
            $defaultClockOut = \Carbon\Carbon::parse($this->clock_out_time->format('Y-m-d') . ' ' . $user->default_clock_out);
            if ($this->clock_out_time->gt($defaultClockOut)) {
                $this->overtime_minutes = $defaultClockOut->diffInMinutes($this->clock_out_time);
            } else {
                $this->overtime_minutes = 0;
            }
        }
    }

    public function formatMinutesToHoursMinutes($minutes)
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;

        if ($hours > 0 && $mins > 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ' . $mins . ' minute' . ($mins > 1 ? 's' : '');
        } elseif ($hours > 0) {
            return $hours . ' hour' . ($hours > 1 ? 's' : '');
        } elseif ($mins > 0) {
            return $mins . ' minute' . ($mins > 1 ? 's' : '');
        }
        return '0 minutes';
    }

    public function getLateTimeAttribute()
    {
        return $this->formatMinutesToHoursMinutes($this->late_minutes);
    }

    public function getOvertimeAttribute()
    {
        return $this->formatMinutesToHoursMinutes($this->overtime_minutes);
    }

    public function getTotalHoursFormattedAttribute()
    {
        $totalMinutes = round($this->total_hours * 60);
        return $this->formatMinutesToHoursMinutes($totalMinutes);
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('clock_in_time', [$startDate, $endDate]);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
