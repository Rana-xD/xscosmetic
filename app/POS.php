<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class POS extends Model
{

    protected $table = 'p_o_s_s';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_no', 'items', 'cashier','time','created_at','updated_at'
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
