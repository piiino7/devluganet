<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class OrderPayment extends Model
{
    protected $table = 'order_payments';
    protected $fillable = [
        'order_id',
        'provider',
        'qr_id',
        'external_id',
        'amount',
        'currency',
        'status',
        'payload',
        'paid_at',
    ];
    protected $casts = [
        'amount'     => 'decimal:2',
        'payload'    => 'array',
        'paid_at'    => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}