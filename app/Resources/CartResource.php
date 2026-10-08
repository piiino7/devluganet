<?php

namespace App\Resources;

use App\Models\Cart;

class CartResource extends Resource
{
    public function __construct(private Cart $cart) {}

    public function toArray(): array
    {
        $this->cart->offer->load('package');
        $this->cart->offer->product->load('taxRate');

        return [
            'id'                => $this->cart->id,
            'offer'             => (new OfferResource($this->cart->offer))->toArray(),
            'tax_rate'          => $this->cart->offer->product->taxRate->rate,
            'quantity'          => $this->cart->quantity
        ];
    }
}
