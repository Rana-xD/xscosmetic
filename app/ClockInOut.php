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
            $this->save();
        }
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
