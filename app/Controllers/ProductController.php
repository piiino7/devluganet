<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Offer;
use App\Models\Cart;

use App\Resources\UserResource;
use App\Resources\GroupResource;
use App\Resources\ShortProductResource;
use App\Resources\DetailProductResource;

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

    public function getProducts(?string $groupId = null): void
    {
        $seller = AuthUser::requireUser();

        $page  = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 20)));

        $search  = trim((string)($_GET['search'] ?? ''));
        $kind    = trim((string)($_GET['kind'] ?? ''));
        $inStock = (int)($_GET['inStock'] ?? 0) === 1;

        $sort  = (string)($_GET['sort'] ?? 'name');
        $order = strtolower((string)($_GET['order'] ?? 'asc')) === 'desc' ? 'desc' : 'asc';

        $allowedSort = ['name', 'article', 'code', 'id'];
        if (!in_array($sort, $allowedSort, true)) {
            $sort = 'name';
        }

        if ($groupId) {
            $data = (new Validator(['groupId'   => $groupId]))
                ->rules([
                    'groupId'    => 'required|int',
                ])
                ->validate();

            $groupId = (int)$data['groupId'];
        }

        $query = Product::with([
            'offers' => fn($q) => $q
                ->whereHas('prices.priceType', fn($q) => $q->where('name', getenv('SITE_PRICE_TYPE_ID')))
                ->with(['prices' => fn($q) => $q->whereHas(
                    'priceType', fn($q) => $q->where('name', getenv('SITE_PRICE_TYPE_ID'))
                )]),
            'unit',
        ])->where('is_active', true);

        if ($groupId) {
            $query->whereHas('groups', fn($q) => $q->where('product_groups.id', $groupId));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $like = '%' . $search . '%';
                $q->where('name', 'like', $like)
                    ->orWhere('article', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('full_name', 'like', $like);
            });
        }

        if ($kind !== '') {
            $query->where('kind', $kind);
        }

        if ($inStock) {
            $query->whereHas('offers', fn($q) => $q->where('quantity', '>', 0));
        }

        $total = (clone $query)->count();

        $products = $query
            ->orderBy($sort, $order)
            ->limit($limit)
            ->offset(($page - 1) * $limit)
            ->get();

        $this->json([
            'data' => [
                'products' => ShortProductResource::collection($products),
                'asked_by' => (new UserResource($seller))->toArray()
            ],
            'meta' => [
                'total' => $total,
                'page'  => $page,
                'limit' => $limit,
                'pages' => (int)ceil($total / $limit),
                'filters' => [
                    'search'  => $search !== '' ? $search : null,
                    'groupId' => $groupId,
                    'kind'    => $kind !== '' ? $kind : null,
                    'inStock' => $inStock,
                    'sort'    => $sort,
                    'order'   => $order,
                ],
            ],
        ]);
    }

    public function getProduct(string $productId): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator(['productId' => $productId]))
            ->rules([
                'productId'    => 'required|int',
            ])
            ->validate();

        $product = Product::with([
            'unit',
            'taxRate',
            'groups',
            'offers' => fn($q) => $q
                ->whereHas('prices.priceType', fn($q) => $q->where('name', getenv('SITE_PRICE_TYPE_ID')))
                ->with([
                    'package',
                    'prices' => fn($q) => $q
                        ->whereHas('priceType', fn($q) => $q->where('name', getenv('SITE_PRICE_TYPE_ID')))
                        ->with('priceType'),
                ]),
        ])
            ->where('is_active', true)
            ->find($data['productId']);

        if ($product === null) {
            throw HttpException::notFound('Product not found');
        }

        $this->json([
            'data' => [
                'product' => (new DetailProductResource($product))->toArray(),
                'asked_by' => (new UserResource($seller))->toArray()
            ],
        ]);
    }

    public function ListOfClients(): void
    {
        $seller = AuthUser::requireUser();

        $clients = $this->billingService->getClients();

        // НАСТРОИТЬ ПОИСК, ФИЛЬТРЫ

        if ($clients === null) {
            throw HttpException::notFound('Clients not found in billing');
        }

        $this->json([
            'data' => $clients,
        ]);
    }

    public function addToCart(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'client_id'      => 'required|string',
                'product_id'     => 'required|int',
                'quantity'       => 'required|int|min:1',
            ])
            ->validate();

        $product = Product::find((int)$data['product_id']) ?? throw HttpException::notFound('Product not found');;

        $cart = Cart::updateOrCreate(
            [
                'seller_id'          => $seller->id,
                'client_external_id' => $data['client_id'],
                'product_id'         => $product->id,
            ],
            [
                'quantity' => $data['quantity'],
            ]
        );

        $this->json([
            'data' => $cart,
        ]);
    }

    public function GetCart(string $clientId): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator(['client_id' => $clientId]))
            ->rules([
                'client_id'      => 'required|string',
            ])
            ->validate();

        $items = Cart::where('seller_id', $seller->id)
            ->where('client_external_id', (int)$clientId)
            ->with(['product.unit', 'product.taxRate', 'product.offers.prices'])
            ->get();

        $this->json([
            'data' => $items,
        ]);
    }

    public function RemoveFromCart(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'client_id'      => 'required|string',
                'product_id'     => 'required|int',
                'quantity'       => 'required|int|min:1',
            ])
            ->validate();

        $affected = Cart::where('seller_id', $seller->id)
            ->where('client_external_id', (int)$data['client_id'])
            ->where('product_id', (int)$data['product_id'])
            ->where('quantity', '>=', (int)$data['quantity'])
            ->decrement('quantity', (int)$data['quantity']);

        if ($affected === 0) {
            import_log('decrement elements from cart error', [
                'client_id'   => $data['client_id'],
                'product_id' => $data['product_id'],
            ]);
        }

        $this->json([
            'data' => [],
        ]);
    }

    public function ClearCart(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'client_id'      => 'required|string',
            ])
            ->validate();

        Cart::where('seller_id', $seller->id)
            ->where('client_external_id', (int)$data['client_id'])
            ->delete();

        $this->json([
            'data' => [],
        ]);
    }

    public function report(): void
    {

    }
}