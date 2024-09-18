<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $table = 'change';

    protected $fillable = [
        'usd',
        'riel',
        'date'
    ];
}
