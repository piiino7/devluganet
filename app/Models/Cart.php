<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $table = 'carts';
    protected $guarded = ['id'];

    protected $casts = [
        'quantity'      => 'decimal:3',
        'service_date'  => 'datetime',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}