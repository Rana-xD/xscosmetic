<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IncomingShipment extends Model
{
    protected $fillable = [
        'reference_no',
        'status',
        'notes',
        'created_by',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(IncomingShipmentItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
