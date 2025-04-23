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
        'received_in_usd',
        'received_in_riel',
        'change_in_usd',
        'change_in_riel',
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
        if (is_array($this->additional_info) && isset($this->additional_info['total'])) {
            return number_format((float) $this->additional_info['total'], 2, '.', '');
        }

        if (!is_array($this->items)) {
            return number_format(0, 2, '.', '');
        }
        
        $total = collect($this->items)->sum(function ($item) {
            return (float) str_replace(['$', ' '], '', $item['total']);
        });
        
        return number_format($total, 2, '.', '');
    }
}
