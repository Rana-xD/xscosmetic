<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductLog extends Model
{


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'items', 'date'
    ];

    protected $casts = [
        'items' => 'array',
    ];
}
