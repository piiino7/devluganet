<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Offer extends Model
{
    protected $table = 'offers';
    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:3',
        'min_quantity' => 'decimal:3',
        'reserved' => 'decimal:3',
        'price'    => 'decimal:3',
    ];

    /*protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (float)$value,
        );
    }*/

    public function package()
    {
        return $this->belongsTo(OfferPackage::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function available(): float
    {
        return $this->quantity - $this->reserved - $this->min_quantity;
    }
}