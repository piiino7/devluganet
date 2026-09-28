<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderItem extends Model
{
    protected $table = 'order_items';
    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',
        'product_article',
        'product_code',
        'price',
        'quantity',
        'unit_name',
        'unit_code',
        'tax_rate',
        'total',
    ];
    protected $casts = [
        'price'      => 'decimal:2',
        'quantity'   => 'decimal:3',
        'tax_rate'   => 'decimal:2',
        'total'      => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}