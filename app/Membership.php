<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Membership extends Model
{
    protected $fillable = [
        'phone',
        'name',
        'rank',
        'discount_percent',
        'membership_years',
        'expired_at',
    ];

    protected $casts = [
        'expired_at' => 'date',
    ];

    public function getStatusAttribute()
    {
        return $this->isExpired() ? 'expired' : 'active';
    }

    public function isExpired()
    {
        return $this->expired_at instanceof Carbon
            ? $this->expired_at->lt(Carbon::today())
            : Carbon::parse($this->expired_at)->lt(Carbon::today());
    }
}
