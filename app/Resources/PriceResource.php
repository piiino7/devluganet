<?php

namespace App\Resources;

use App\Models\Price;

class PriceResource extends Resource
{
    public function __construct(private Price $price) {}

    public function toArray(): array
    {
        return [
            'price'      => (float)$this->price->price,
            'currency'   => $this->price->currency,
            'price_type' => $this->price->relationLoaded('priceType') && $this->price->priceType
                ? $this->price->priceType->name
                : null,
        ];
    }
}