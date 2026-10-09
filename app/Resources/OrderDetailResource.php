<?php

namespace App\Resources;

use App\Models\Order;

class OrderDetailResource extends Resource
{
    public function __construct(private Order $order) {}

    public function toArray(): array
    {
        return [
            'id'          => $this->order->id,
            'external_id' => $this->order->external_id,
            'number'      => $this->order->number,
            'status'      => $this->order->status,
            'total'       => (float)$this->order->total,
            'currency'    => $this->order->currency,
            'comment'     => $this->order->comment,
            'client_personal_account' => $this->order->client_personal_account,

            'seller' => $this->order->relationLoaded('seller') && $this->order->seller
                ? [
                    'id'    => $this->order->seller->id,
                    'name'  => $this->order->seller->name,
                    'email' => $this->order->seller->email,
                ]
                : null,

            'items' => $this->order->relationLoaded('items')
                ? OrderItemResource::collection($this->order->items)
                : [],

            'payment' => $this->order->relationLoaded('payments')
                ? OrderPaymentResource::collection($this->order->payments)
                : [],

            'exported_at' => $this->order->exported_at,
            'created_at'  => $this->order->created_at,
            'updated_at'  => $this->order->updated_at,
        ];
    }
}