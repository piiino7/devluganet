<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Offer extends Model
{
    protected $table = 'offers';
    protected $guarded = [];

    protected function quantity(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (float)$value,
        );
    }

    public function package()
    {
        return $this->belongsTo(OfferPackage::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
}