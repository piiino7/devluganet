<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Support\HttpException;
use Illuminate\Support\Collection;
use Illuminate\Database\Capsule\Manager as DB;

class OrderService
{
    public function __construct() {}

    /**
     * @param array{items: array<array{product_id:int, quantity:int}>} $items
     * @param array $clientData — данные из биллинга
     */
    public function createOrder(User $seller, array $clientData, array $items, ?string $comment): Order {
        $products = $this->loadProducts($items);

        $orderItems = $this->buildItems($items, $products);

        $total = array_sum(array_column($orderItems, 'total'));

        return DB::connection()->transaction(function () use (
            $seller, $clientData, $orderItems, $total, $comment
        ) {
            $order = Order::create([
                'external_id'        => $this->generateUuid(),
                'number'             => $this->generateNumber(),
                'seller_id'          => $seller->id,
                'client_external_id' => $clientData['id'],
                'client_name'        => $clientData['name'],
                'client_full_name'   => $clientData['full_name'] ?? null,
                'client_inn'         => $clientData['inn'] ?? null,
                'client_phone'       => $clientData['phone'] ?? null,
                'client_email'       => $clientData['email'] ?? null,
                'client_payload'     => $clientData,
                'status'             => 'pending_payment',
                'total'              => $total,
                'currency'           => 'руб.',
                'payment_status'     => 'pending',
                'comment'            => $comment,
            ]);

            foreach ($orderItems as $item) {
                $order->items()->create($item);
            }

            return $order;
        });
    }

    public function attachPayment(Order $order, array $payment): void
    {
        DB::connection()->transaction(function () use ($order, $payment) {
            $order->update([
                'payment_provider'      => $payment['provider'],
                'payment_qr_id'         => $payment['qr_id'],
                'payment_qr_url'        => $payment['qr_url'],
                'payment_qr_expires_at' => $payment['expires_at'] ?? null,
                'payment_payload'       => $payment,
            ]);

            $order->payments()->create([
                'provider'    => $payment['provider'],
                'qr_id'       => $payment['qr_id'],
                'external_id' => $payment['external_id'] ?? null,
                'amount'      => $order->total,
                'currency'    => $order->currency,
                'status'      => 'pending',
                'payload'     => $payment,
            ]);
        });
    }

    /**
     * @param array<array{product_id:int, quantity:int}> $items
     * @return \Illuminate\Support\Collection<int, Product>
     */
    private function loadProducts(array $items): Collection
    {
        $ids = array_column($items, 'product_id');

        $products = Product::with([
            'unit',
            'taxRate',
            'offers' => fn($q) => $q
                ->whereHas('prices.priceType', fn($q) => $q->where('name', getenv('SITE_PRICE_TYPE_ID')))
                ->with(['prices' => fn($q) => $q->whereHas(
                    'priceType', fn($q) => $q->where('name', getenv('SITE_PRICE_TYPE_ID'))
                )]),
        ])->find($ids);

        if (count($products) !== count(array_unique($ids))) {
            $found = $products->pluck('id')->all();
            $missing = array_diff($ids, $found);
            throw HttpException::validation([
                'items' => ['Products not found: ' . implode(', ', $missing)],
            ]);
        }

        return $products;
    }

    /**
     * @param array<array{product_id:int, quantity:int}> $items
     * @return array<array<string,mixed>>
     */
    private function buildItems(array $items, Collection $products): array
    {
        $result = [];

        foreach ($items as $item) {
            $product = $products->firstWhere('id', $item['product_id']);
            $offer   = $product->offers->first();
            $price   = $offer?->prices->first();

            if (!$price) {
                throw HttpException::validation([
                    'items' => ["Product {$product->id} has no site price"],
                ]);
            }

            $quantity = (float)$item['quantity'];
            $unitPrice = (float)$price->price;
            $lineTotal = $unitPrice * $quantity;

            $result[] = [
                'product_id'      => $product->id,
                'product_name'    => $product->name,
                'product_article' => $product->article,
                'product_code'    => $product->code,
                'price'           => $unitPrice,
                'quantity'        => $quantity,
                'unit_name'       => $product->unit?->name,
                'unit_code'       => $product->unit?->code,
                'tax_rate'        => $product->taxRate?->rate,
                'total'           => $lineTotal,
            ];
        }

        return $result;
    }

    private function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function generateNumber(): string
    {
        // ORD-20260928-00001
        $date = date('Ymd');
        $last = Order::where('number', 'like', "ORD-{$date}-%")
            ->orderByDesc('id')
            ->value('number');

        $seq = $last ? ((int)substr($last, -5)) + 1 : 1;

        return sprintf('ORD-%s-%05d', $date, $seq);
    }
}