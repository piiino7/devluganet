<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Product extends Model
{
    protected $table = 'products';
    protected $guarded = [];

    protected function isActive(): Attribute
    {
        return Attribute::make(
            get: fn($value) => (bool)$value,
        );
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function taxRate()
    {
        return $this->belongsTo(TaxRate::class, 'tax_rate_id');
    }

    public function groups()
    {
        return $this->belongsToMany(
            ProductGroup::class,
            'product_groups_products',
            'product_id',
            'group_id');
    }

    public function attributes()
    {
        return $this->hasMany(ProductAttribute::class);
    }

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}