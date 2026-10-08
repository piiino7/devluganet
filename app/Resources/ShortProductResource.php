<?php

namespace App\Resources;

use App\Models\Product;

class ShortProductResource extends Resource
{
    public function __construct(private Product $product) {}

    public function toArray(): array
    {
        $offer = $this->product->offers->first();
        $price = $offer?->price;

        return [
            'id'           => $this->product->id,
            'name'         => $this->product->name,
            'kind'         => $this->product->kind,
            'unit'         => $this->product->unit->name,
            'offer'        => $offer,
            'price'        => $price ? (float)$price->price : null,
            'quantity'     => $offer?->quantity !== null ? (float)$offer->quantity : null,
        ];
    }
}
