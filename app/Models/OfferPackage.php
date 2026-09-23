<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfferPackage extends Model
{
    protected $table = 'offer_packages';
    protected $guarded = [];

    public function offers()
    {
        return $this->hasMany(Offer::class);
    }
}