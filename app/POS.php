<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class POS extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_no', 'items', 'cashier','time'
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
