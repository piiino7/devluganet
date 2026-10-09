<?php

namespace App\Controllers;

use App\Models\Offer;
use App\Models\Cart;

use App\Resources\UserResource;
use App\Resources\CartResource;

use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;


class CartController extends BaseController {

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
                'message' => 'success',
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
            'message' => 'success',
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
                'offer_id' => 'required|int',
                'quantity' => 'required|float|min:1',
            ])
            ->validate();

        $item = Cart::where('seller_id', $seller->id)
            ->where('offer_id', (int)$data['offer_id'])
            ->first();

        if ($item === null) {
            import_log('method ProductController->removeFromCart() returns', [
                'error' => 'item not found',
                'offer_id' => $data['offer_id'],
                'removed_by' => $seller->id,
            ]);

            throw HttpException::notFound('Offer ' . (int)$data['offer_id'] . ' not found in cart');
        }

        $isService = $item->offer->product->kind === 'Услуга';
        $quantity = $isService ? 1 : $data['quantity'];

        if (!$isService AND $item->quantity < $quantity) {
            import_log('method ProductController->removeFromCart() returns', [
                'error' => 'not enough quantity for offer in cart',
                'offer_id' => $data['offer_id'],
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
                    'offer_id' => $data['offer_id'],
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
                    'offer_id' => $data['offer_id'],
                    'removed_by' => $seller->id,
                ]);

                throw new \Exception('decrement elements from cart error');
            }

            import_log('method ProductController->removeFromCart() returns', [
                'message' => 'success',
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
            'message' => 'success',
            'cleared_by' => $seller->id,
        ]);
        $this->json([
            'data' => [
                'message' => 'Clear cart success',
            ],
        ]);
    }
}