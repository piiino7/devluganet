<?php

namespace App\Resources;

use App\Models\Order;

class OrderResource extends Resource
{
    public function __construct(private Order $order) {}

    public function toArray(): array
    {
        return [
            'id'         => $this->order->id,
            'number'     => $this->order->number,
            'status'     => $this->order->status,
            'total'      => (float)$this->order->total,
            'currency'   => $this->order->currency,
            'client'     => [
                'external_id' => $this->order->client_external_id,
                'name'        => $this->order->client_name,
            ],
            'seller'     => $this->order->relationLoaded('seller') && $this->order->seller
                ? [
                    'id'   => $this->order->seller->id,
                    'name' => $this->order->seller->name,
                ]
                : null,
            'items_count' => $this->order->relationLoaded('items')
                ? $this->order->items->count()
                : null,
            'paid_at'     => $this->order->paid_at,
            'created_at'  => $this->order->created_at,
        ];
    }
}