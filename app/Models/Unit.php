<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    protected $table = 'units';
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}