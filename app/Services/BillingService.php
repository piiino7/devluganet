<?php

namespace App\Services;

class BillingService
{
    public function __construct(
        private string $baseUrl,
        private string $apiKey,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            getenv('BILLING_URL') ?: '',
            getenv('BILLING_API_KEY') ?: '',
        );
    }

    /**
     * @return array{id:string, name:string, full_name?:string, inn?:string, phone?:string, email?:string}|null
     */
    public function getClients(): ?array
    {
        $url = rtrim($this->baseUrl, '/') . '/clients';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            import_log('billing getClients failed', [
                'http_code' => $httpCode,
                'error'     => $error,
            ]);
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['id'])) {
            return null;
        }

        return [
            'clients' => $data
        ];
    }

    /**
     * @return array{id:string, name:string, full_name?:string, inn?:string, phone?:string, email?:string}|null
     */
    public function getClient(string $clientId): ?array
    {
        $url = rtrim($this->baseUrl, '/') . '/clients/' . urlencode($clientId);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            import_log('billing getClient failed', [
                'client_id' => $clientId,
                'http_code' => $httpCode,
                'error'     => $error,
            ]);
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || empty($data['id'])) {
            return null;
        }

        return [
            'id'        => (string)$data['id'],
            'name'      => (string)($data['name'] ?? ''),
            'full_name' => $data['full_name'] ?? null,
            'inn'       => $data['inn'] ?? null,
            'phone'     => $data['phone'] ?? null,
            'email'     => $data['email'] ?? null,
        ];
    }
}