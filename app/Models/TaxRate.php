<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRate extends Model
{
    protected $table = 'tax_rates';
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}