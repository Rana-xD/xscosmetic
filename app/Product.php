<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name','product_barcode','stock', 'price','cost','cost_group','expire_date','photo','category_id'
    ];

    protected $casts = [
        'cost_group' => 'array',
    ];

    public function category(){
        return $this->belongsTo('App\Category','category_id');
    }

    // public function unit(){
    //     return $this->belongsTo('App\Unit','unit_id');
    // }
    
}
