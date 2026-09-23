<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductGroup extends Model
{
    protected $table = 'product_groups';
    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(ProductGroup::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(ProductGroup::class, 'parent_id');
    }

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_groups_products',
            'group_id',
            'product_id'
        );
    }
}