<?php

namespace App\Resources;

use App\Models\Offer;

class OfferResource extends Resource
{
    public function __construct(private Offer $offer) {}

    public function toArray(): array
    {
        return [
            'id'          => $this->offer->id,
            'package'  => $this->offer->relationLoaded('package') && $this->offer->package
                ? $this->offer->package->name
                : null,
            'quantity' => (float)$this->offer->quantity,
            'prices'   => $this->offer->relationLoaded('prices')
                ? PriceResource::collection($this->offer->prices)
                : [],
        ];
    }
}
