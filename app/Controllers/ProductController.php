<?php

namespace App\Controllers;

use App\Models\Product;
use App\Models\ProductGroup;
use App\Models\Offer;

use App\Resources\UserResource;
use App\Resources\GroupResource;
use App\Resources\ShortProductResource;
use App\Resources\DetailProductResource;
use App\Resources\OfferResource;

use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;


class ProductController extends BaseController {

    public function __construct() {}

    public function getGroups(): void
    {
        $seller = AuthUser::requireUser();

        $groups = ProductGroup::withCount('products')->orderBy('name')->get();

        import_log('method ProductController->getGroups() returns', [
            'groups' => $groups->pluck('id')->toArray(),
            'asked_by' => $seller->id,
        ]);

        $this->json([
            'data' => [
                'groups' => GroupResource::collection($groups)
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

        $query = Product::with(['offers', 'unit',])->where('is_active', true);

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

        import_log('method ProductController->getProducts() returns', [
            'groupId' => $groupId,
            'products' => $products->pluck('id')->toArray(),
            'asked_by' => $seller->id,
        ]);
        $this->json([
            'data' => [
                'products' => ShortProductResource::collection($products),
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

        $product = Product::with('unit', 'taxRate', 'groups', 'offers')
            ->where('is_active', true)
            ->find($data['productId']);

        if ($product === null) {
            import_log('method ProductController->getProduct() returns', [
                'error' => 'Product not found',
                'asked_by' => $seller->id,
            ]);
            throw HttpException::notFound('Product not found');
        }

        import_log('method ProductController->getProduct() returns', [
            'product' => $product->id,
            'asked_by' => $seller->id,
        ]);
        $this->json([
            'data' => [
                'product' => (new DetailProductResource($product))->toArray()
            ],
        ]);
    }

    public function changeAlias(string $productId): void
    {
        $seller = AuthUser::requireUser();

        $body = $this->body();
        $body['productId'] = $productId;

        $data = (new Validator($body))
            ->rules([
                'productId' => 'required|int',
                'alias' => 'required|string'
            ])
            ->validate();

        $product = Product::find($data['productId']);

        if ($product === null) {
            import_log('method ProductController->changeAlias() returns', [
                'error' => 'Product not found',
                'asked_by' => $seller->id,
            ]);
            throw HttpException::notFound('Product not found');
        }

        $product->update(['alias' => $data['alias']]);

        if (!$product) {
            import_log('updating alias failed', [
                'error' => 'Cannot change this alias',
                'login' => $seller->name,
                'client_info' => $this->client,
            ]);

            throw HttpException::forbidden('Cannot change this alias');
        }

        import_log('changing alias success', [
            'login' => $seller->name,
            'client_info' => $this->client,
        ]);

        $this->json([
            'data' => [
                'status' => 'success',
                'message' => 'alias was successfully changed',
                'product' => (new DetailProductResource($product))->toArray(),
            ],
        ]);
    }

    public function getOffer(string $offerId): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator(['offerId' => $offerId]))
            ->rules([
                'offerId'    => 'required|int',
            ])
            ->validate();

        $offer = Offer::find($data['offerId']);

        $this->json([
            'data' => [
                'offerResource' => (new OfferResource($offer))->toArray()
            ],
        ]);
    }

    public function report(): void
    {

    }
}