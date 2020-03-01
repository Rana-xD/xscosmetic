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
        'product_code', 'product_name', 'price','photo','category_id'
    ];

    public function category(){
        return $this->belongsTo('App\Category','category_id');
    }
}
