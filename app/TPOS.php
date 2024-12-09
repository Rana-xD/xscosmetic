<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class TPOS extends Model
{

    protected $table = 't_p_o_s';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_no', 'items', 'cashier','time','payment_type','created_at','updated_at', 'additional_info'
    ];

    protected $casts = [
        'items' => 'array',
        'additional_info' => 'array'
    ];
}
