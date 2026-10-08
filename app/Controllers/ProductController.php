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
use App\Resources\OfferResource;
use App\Resources\CartResource;

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

    public function addToCart(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'offer_id'     => 'required|int',
                'quantity'     => 'required|float|min:1',
            ])
            ->validate();

        $productOffer = Offer::find((int)$data['offer_id']);
        $cart = Cart::where('offer_id',$productOffer->id)->first();
        $totalCartQuantity = ($cart->quantity ?? 0.00) + $data['quantity'];

        if ($productOffer === null) {
            import_log('method ProductController->addToCart() returns', [
                'error' => 'Offer for product '. $data['offer_id'] .' not found',
                'asked_by' => $seller->id,
            ]);

            throw HttpException::notFound('Offer not found');
        }

        $isService = $productOffer->product->kind === 'Услуга';

        if (!$isService AND (float)$productOffer->quantity < (float)$totalCartQuantity) {
            throw HttpException::validation([
                'quantity' => ['Asking for ' . $data['quantity'] . '. Already in cart: '. ($cart->quantity ?? 0.00) .'. Not enough products on warehouse: ' . $productOffer->quantity],
            ]);
        }

        try {
            $item = Cart::updateOrCreate(
                [
                    'seller_id'  => $seller->id,
                    'offer_id'   => $productOffer->id,
                ],
                [
                    'quantity' => $isService ? 1 : $totalCartQuantity,
                    'service_date' => $isService ? date('Y-m-d H:i:s', time()) : null,
                ]
            );

            import_log('method ProductController->addToCart() returns', [
                'item_in_cart' => $item->id,
                'added_by' => $seller->id,
            ]);
            $this->json([
                'data' => [
                    'message' => 'success',
                    'item' => (new CartResource($item))->toArray()
                ],
            ]);
        } catch (\Throwable $e) {
            import_log('method ProductController->addToCart() returns', [
                'status'    => $e->status,
                'message'   => $e->getMessage(),
                'added_by' => $seller->id,
            ]);

            throw $e;
        }
    }

    public function getCart(): void
    {
        $seller = AuthUser::requireUser();

        $items = Cart::where('seller_id', $seller->id)
            ->with(['offer.product.unit', 'offer.product.taxRate'])
            ->get();

        import_log('method ProductController->getCart() returns', [
            'items' => $items->pluck('id')->toArray(),
            'asked_by' => $seller->id,
        ]);
        $this->json([
            'data' => [
                'Cart' => CartResource::collection($items)
            ],
        ]);
    }

    public function removeFromCart(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))
            ->rules([
                'product_id'     => 'required|int',
                'quantity'       => 'required|int|min:1',
            ])
            ->validate();

        $item = Cart::where('seller_id', $seller->id)
            ->where('product_id', (int)$data['product_id'])
            ->first();

        if ($item === null) {
            import_log('method ProductController->removeFromCart() returns', [
                'error' => 'item not found',
                'product_id' => $data['product_id'],
                'removed_by' => $seller->id,
            ]);

            throw HttpException::notFound('Item ' . (int)$data['product_id'] . ' not found in cart');
        }

        $isService = $item->product->kind === 'Услуга';
        $quantity = $isService ? 1 : $data['quantity'];

        if ($item->quantity < $quantity) {
            import_log('method ProductController->removeFromCart() returns', [
                'error' => 'not enough quantity',
                'product_id' => $data['product_id'],
                'quantity_for_remove' => $quantity,
                'quantity_in_cart' => $item->quantity,
                'removed_by' => $seller->id,
            ]);

            throw HttpException::validation([
                'quantity' => ['Not enough cart quantity:  '. (float)$quantity . ' to remove ' . $item->quantity]
            ]);
        }

        try {
            $quantityBefore = $item->quantity;
            $affected = $item->decrement('quantity', $quantity);

            if ($item->quantity === "0.000") {
                $item->delete();

                import_log('method ProductController->removeFromCart() returns', [
                    'message' => 'decrement success, item was removed from cart',
                    'product_id' => $data['product_id'],
                    'removed_by' => $seller->id,
                ]);
                $this->json([
                    'data' => [
                        'message' => 'decrement success, item was removed from cart',
                    ],
                ]);
            }

            if ($affected === 0) {
                import_log('method ProductController->removeFromCart() returns', [
                    'error' => 'decrement elements from cart error',
                    'product_id' => $data['product_id'],
                    'removed_by' => $seller->id,
                ]);

                throw new \Exception('decrement elements from cart error');
            }

            import_log('method ProductController->removeFromCart() returns', [
                'quantity_before_decrement' => $quantityBefore,
                'quantity_after_decrement' => $item->quantity,
                'product_id' => $data['product_id'],
                'removed_by' => $seller->id,
            ]);
            $this->json([
                'data' => [
                    'message' => 'decrement success',
                    'Cart' => (new CartResource($item))->toArray()
                ],
            ]);
        } catch (\Throwable $e) {
            import_log('method ProductController->removeFromCart() returns', [
                'error' => $e->getMessage(),
                'status' => $e->status,
                'product_id' => $data['product_id'],
                'removed_by' => $seller->id,
            ]);

            throw $e;
        }
    }

    public function clearCart(): void
    {
        $seller = AuthUser::requireUser();

        $exist = Cart::where('seller_id', $seller->id)
            ->first();

        if ($exist === null) {
            $this->json([
                'data' => [
                    'message' => 'Cart arleady cleared',
                ],
            ]);
        }

        Cart::where('seller_id', $seller->id)
            ->delete();

        import_log('method ProductController->clearCart() returns', [
            'message' => 'Clear cart success',
            'cleared_by' => $seller->id,
        ]);
        $this->json([
            'data' => [
                'message' => 'Clear cart success',
            ],
        ]);
    }

    public function report(): void
    {

    }
}