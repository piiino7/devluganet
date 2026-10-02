<?php

namespace App\Resources;

use App\Models\Cart;

class CartResource extends Resource
{
    public function __construct(private Cart $cart) {}

    public function toArray(): array
    {
        return [
            'id'                => $this->cart->id,
            'product'           => (new ShortProductResource($this->cart->product))->toArray(),
            'tax_rate'          => $this->cart->product->rate,
            'quantity'          => $this->cart->quantity
        ];
    }
}
