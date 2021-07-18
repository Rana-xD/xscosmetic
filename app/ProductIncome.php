<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductIncome extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id','unit_id', 'product_name','quantity','total_price','total_cost','profit'
    ];

    public function category(){
        return $this->belongsTo('App\Category','category_id');
    }

    public function unit(){
        return $this->belongsTo('App\Unit','unit_id');
    }
}
