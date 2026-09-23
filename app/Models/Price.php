<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Price extends Model
{
    protected $table = 'prices';
    protected $guarded = [];

    protected function price(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (float)$value,
        );
    }

    protected function coefficient(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (float)$value,
        );
    }

    public function offer()
    {
        return $this->belongsTo(Offer::class);
    }

    public function price_type()
    {
        return $this->belongsTo(PriceType::class);
    }
}