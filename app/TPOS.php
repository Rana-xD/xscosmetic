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
        'order_no',
        'items',
        'cashier',
        'time',
        'payment_type',
        'received_in_usd',
        'received_in_riel',
        'change_in_usd',
        'change_in_riel',
        'cash_percentage',
        'aba_percentage',
        'cash_amount',
        'aba_amount',
        'created_at',
        'updated_at',
        'additional_info'
    ];

    protected $casts = [
        'items' => 'array',
        'additional_info' => 'array'
    ];
}
