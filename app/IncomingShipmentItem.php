<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class IncomingShipmentItem extends Model
{
    protected $fillable = [
        'incoming_shipment_id',
        'name',
        'barcode',
        'qty',
        'cost',
        'price',
        'category_id',
        'expire_date',
        'status',
        'confirmed_by',
        'confirmed_at',
        'linked_product_id',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];

    public function shipment()
    {
        return $this->belongsTo(IncomingShipment::class, 'incoming_shipment_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function confirmer()
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public function linkedProduct()
    {
        return $this->belongsTo(Product::class, 'linked_product_id');
    }
}
