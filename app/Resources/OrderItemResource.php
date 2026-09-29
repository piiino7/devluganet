<?php

namespace App\Resources;

use App\Models\OrderItem;

class OrderItemResource extends Resource
{
    public function __construct(private OrderItem $item) {}

    public function toArray(): array
    {
        return [
            'id'              => $this->item->id,
            'product_id'      => $this->item->product_id,
            'product_name'    => $this->item->product_name,
            'product_article' => $this->item->product_article,
            'product_code'    => $this->item->product_code,
            'price'           => (float)$this->item->price,
            'quantity'        => (float)$this->item->quantity,
            'unit_name'       => $this->item->unit_name,
            'unit_code'       => $this->item->unit_code,
            'tax_rate'        => $this->item->tax_rate !== null ? (float)$this->item->tax_rate : null,
            'total'           => (float)$this->item->total,
        ];
    }
}