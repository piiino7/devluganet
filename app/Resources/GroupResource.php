<?php

namespace App\Resources;

use App\Models\ProductGroup;

class GroupResource extends Resource
{
    public function __construct(private ProductGroup $group) {}

    public function toArray(): array
    {
        return [
            'id'                    => $this->group->id,
            'name'                  => $this->group->name,
            'parent_id'             => $this->group->parent_id,
            'products_count'        => (int)$this->group->products_count
        ];
    }
}
