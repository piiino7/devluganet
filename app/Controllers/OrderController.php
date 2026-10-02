<?php

namespace App\Controllers;

use App\Models\Offer;
use App\Models\Order;

use App\Resources\UserResource;
use App\Resources\OrderResource;
use App\Resources\OrderDetailResource;

use App\Support\AuthUser;
use App\Support\HttpException;
use App\Support\Validator;

use App\Services\BankService;
use App\Services\BillingService;
use App\Services\OrderService;

use App\Policies\UserPolicy;
use Illuminate\Database\Capsule\Manager as DB;


class SellerController extends BaseController {

    public function __construct() {}

    public function makeAnOrder(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))->rules([
            'client_id'              => 'required|string',
            'items'                  => 'required|array',
            'items.*.product_id'     => 'required|int',
            'items.*.quantity'       => 'required|int|min:1',
            'items.*.service_date'   => 'date',
            'comment'                => 'string|max:1000',
        ])->validate();

        $clientData = $this->billingService->getClient($data['client_id']);

        if ($clientData === null) {
            throw HttpException::notFound('Client not found in billing');
        }

        $order = $this->orderService->createOrder($seller, $clientData, $data['items'], $data['comment'] ?? null);

        $order->load(['items']);

        $this->json([
            'data' => new OrderDetailResource($order),
        ]);
    }

    public function QRpayment(): void
    {
        $seller = AuthUser::requireUser();

        $data = (new Validator($this->body()))->rules([
            'order_id'              => 'required|int',
        ])->validate();

        $order = Order::with('items')->find((int)$data['order_id'])
            ?? throw HttpException::notFound('Order not found');

        if ($order->seller_id !== $seller->id && !$seller->isAdmin()) {
            throw HttpException::forbidden('Not your order'); // Вынести в политики
        }

        if (!in_array($order->status, ['pending_payment', 'failed'], true)) {
            throw HttpException::badRequest('Order cannot be paid');
        }

        if ($order->payment_qr_url && $order->payment_qr_expires_at > now()) {
            $this->json([
                'data' => [
                    'order' => new OrderDetailResource($order),
                    'qr'    => [
                        'url'        => $order->payment_qr_url,
                        'expires_at' => $order->payment_qr_expires_at,
                    ],
                ],
            ]);
            return;
        }

        try {
            $payment = $this->bankService->createQr($order);

            $this->orderService->attachPayment($order, $payment);
        } catch (\Throwable $e) {
            import_log('QR generation failed', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            throw HttpException::badRequest('Cannot generate QR. Try again later.');
        }

        $order->refresh();

        $this->json([
            'order' => new OrderDetailResource($order),
            'qr' => [
                'url'        => $payment['qr_url'],
                'expires_at' => $payment['expires_at'],
            ],
        ], 201);
    }

    public function webhook(): void
    {
        $payload = $this->body();
        $signature = $_SERVER['HTTP_X_BANK_SIGNATURE'] ?? '';

        if (!$this->bankService->verifyWebhook($payload, $signature)) {
            throw HttpException::unauthorized('Invalid signature');
        }

        $qrId = $payload['qr_id'] ?? null;
        if (!$qrId) {
            throw HttpException::badRequest('Missing qr_id');
        }

        $order = Order::where('payment_qr_id', $qrId)->first()
            ?? throw HttpException::notFound('Order not found');

        $existing = $order->payments()
            ->where('qr_id', $qrId)
            ->where('status', $payload['status'])
            ->exists();

        if ($existing) {
            $this->json(['status' => 'already processed']);
            return;
        }

        DB::connection()->transaction(function () use ($order, $payload, $qrId) {
            $order->payments()->create([
                'provider' => $order->payment_provider,
                'qr_id'    => $qrId,
                'amount'   => (float)$payload['amount'],
                'currency' => $order->currency,
                'status'   => $payload['status'],
                'payload'  => $payload,
                'paid_at'  => $payload['status'] === 'paid' ? date('Y-m-d H:i:s') : null,
            ]);

            if ($payload['status'] === 'paid') {
                $order->update([
                    'status'         => 'paid',
                    'payment_status' => 'paid',
                    'paid_at'        => date('Y-m-d H:i:s'),
                ]);

                foreach ($order->items as $item) {
                    $affected = Offer::where('product_id', $item->product_id)
                        ->where('quantity', '>=', $item->quantity)
                        ->decrement('quantity', $item->quantity);

                    if ($affected === 0) {
                        import_log('stock not enough', [
                            'order_id'   => $order->id,
                            'product_id' => $item->product_id,
                        ]);
                    }
                }
            } else {
                $order->update([
                    'status'         => 'failed',
                    'payment_status' => $payload['status'],
                ]);
            }
        });

        $this->json(['status' => 'ok']);
    }
}