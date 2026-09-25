<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PriceType extends Model
{
    protected $table = 'price_types';
    protected $guarded = [];

    protected function taxIncluded(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (bool)$value,
        );
    }

    public function prices()
    {
        return $this->hasMany(Price::class);
    }
}