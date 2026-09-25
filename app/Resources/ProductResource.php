<?php

namespace App\Resources;

use App\Models\Product;

class ProductResource extends Resource
{
    public function __construct(private Product $product) {}

    public function toArray(): array
    {
        return [
            'id'                    => $this->product->id,
            'name'                  => $this->product->name,
            'parent_id'             => $this->product->parent_id,
            'products_count'        => (int)$this->product->products_count
        ];
    }
}
