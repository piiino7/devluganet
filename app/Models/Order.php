<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use SoftDeletes;

    protected $table = 'orders';
    protected $fillable = [
        'external_id', 'number', 'seller_id',
        'client_personal_account', 'status', 'total', 'currency',
        'payment_provider', 'payment_qr_id', 'payment_qr_url',
        'payment_qr_expires_at', 'payment_status', 'payment_payload',
        'paid_at', 'exported_at', 'comment',
    ];
    protected $casts = [
        'total'                 => 'decimal:2',
        'payment_payload'       => 'array',
        'paid_at'               => 'datetime',
        'exported_at'           => 'datetime',
        'payment_qr_expires_at' => 'datetime',
        'deleted_at'            => 'datetime',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(OrderPayment::class);
    }

}