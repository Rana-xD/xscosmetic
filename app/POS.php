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
        'order_no',
        'items',
        'cashier',
        'time',
        'payment_type',
        'cash_percentage',
        'aba_percentage',
        'cash_amount',
        'aba_amount',
        'additional_info',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'items' => 'array',
        'additional_info' => 'array'
    ];

    /**
     * Get the total amount of all items.
     *
     * @return float
     */
    public function getTotalAttribute()
    {
        return collect($this->items)->sum(function ($item) {
            // Remove currency symbol and spaces, then convert to float
            return (float) str_replace(['$', ' '], '', $item['total']);
        });
    }
}
