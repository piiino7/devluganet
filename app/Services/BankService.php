<?php

namespace App\Services;

use App\Models\Order;

class BankService
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
        private string $provider,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            getenv('BANK_URL') ?: '',
            getenv('BANK_API_KEY') ?: '',
            getenv('BANK_PROVIDER') ?: 'bank_x',
        );
    }

    /**
     * @return array{provider:string, qr_id:string, qr_url:string, expires_at:?string, external_id:?string, raw:array}
     */
    public function createQr(Order $order): array
    {
        $payload = [
            'amount'      => (float)$order->total,
            'currency'    => 'RUB',
            'order_id'    => $order->external_id,
            'description' => 'Заказ ' . $order->number,
        ];

        $response = $this->request('POST', '/qr', $payload);

        return [
            'provider'    => $this->provider,
            'qr_id'       => (string)$response['qr_id'],
            'qr_url'      => (string)$response['qr_url'],
            'expires_at'  => $response['expires_at'] ?? null,
            'external_id' => $response['external_id'] ?? null,
            'raw'         => $response,
        ];
    }

    /**
     * Проверка подписи webhook.
     */
    public function verifyWebhook(array $payload, string $signature): bool
    {
        $expected = hash_hmac(
            'sha256',
            json_encode($payload),
            getenv('BANK_WEBHOOK_SECRET') ?: ''
        );

        return hash_equals($expected, $signature);
    }

    /**
     * @return array<string,mixed>
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/') . $path;

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Bank API error: ' . $error);
        }

        $decoded = json_decode($response, true);

        if ($httpCode >= 400) {
            throw new \RuntimeException(
                'Bank API returned ' . $httpCode . ': ' . $response
            );
        }

        if (!is_array($decoded)) {
            throw new \RuntimeException('Bank API invalid JSON: ' . $response);
        }

        return $decoded;
    }
}