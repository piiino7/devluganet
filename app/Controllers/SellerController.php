<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Offer;

use App\Resources\UserResource;
use App\Resources\GroupResource;

use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;


class SellerController extends BaseController {

    public function __construct() {}

    public function getGroups(): void
    {
        $seller = AuthUser::requireUser();

        $groups = ProductGroup::withCount('products')->orderBy('name')->get();

        $this->json([
            'data' => [
                'groups' => GroupResource::collection($groups),
                'asked_by' => (new UserResource($seller))->toArray()
            ],
        ]);
    }

    public function getProducts(): void
    {
        $seller = AuthUser::requireUser();

        $products = Product::with([
            'offers' => fn($q) => $q->with(['prices' => fn($q) => $q->whereHas(
                'priceType', fn($q) => $q->where('name', 'Сайт')
            )]),
            'unit',
            'taxRate',
            'groups',
        ])
            ->where('is_active', true)
            ->get();

        $this->json([
            'data' => $products,
        ]);
    }

    public function getProduct(): void
    {
        // ПОЛУЧАТЬ КОНКРЕТНЫЙ ПРОДУКТ СО ВСЕМИ ДЕТАЛЯМИ
    }

    public function addToCard(): void
    {

    }

    public function removeFromCart(): void
    {

    }

    public function clearCart(): void
    {

    }

    public function makeAnOrder(): void
    {

    }

    public function report(): void
    {

    }
}