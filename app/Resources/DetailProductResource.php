<?php

namespace App\Resources;

use App\Models\Product;

class DetailProductResource extends Resource
{
    public function __construct(private Product $product) {}

    public function toArray(): array
    {
        return [
            'id'                => $this->product->id,
            'name'              => $this->product->name,
            'full_name'         => $this->product->full_name,
            'article'           => $this->product->article,
            'code'              => $this->product->code,
            'description'       => $this->product->description,
            'kind'              => $this->product->kind,
            //'nomenclature_type' => $this->product->nomenclature_type,
            'unit'              => $this->product->unit ? [
                'name' => $this->product->unit->name,
                'code' => $this->product->unit->code,
            ] : null,
            'tax_rate'          => $this->product->taxRate ? [
                'name' => $this->product->taxRate->name,
                'rate' => $this->product->taxRate->rate,
            ] : null,
            'offers'      => $this->product->relationLoaded('offers')
                ? OfferResource::collection($this->product->offers)
                : [],
        ];
    }
}
