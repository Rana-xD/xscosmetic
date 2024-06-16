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
        'name','product_barcode','stock', 'price','cost','expire_date','photo','category_id','unit_id'
    ];

    public function category(){
        return $this->belongsTo('App\Category','category_id');
    }

    public function unit(){
        return $this->belongsTo('App\Unit','unit_id');
    }
    
}
