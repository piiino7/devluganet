<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use RuntimeException;

class JwtService
{
    public function __construct(
        private string $secret,
        private string $algo = 'HS256',
        private int $ttl = 3600,
        private string $issuer = 'api',
    ) {}

    public static function fromConfig(): self
    {
        $config = require dirname(__DIR__, 2) . '/config/jwt.php';
        return new self(
            $config['secret'],
            $config['algo'],
            $config['ttl'],
            $config['issuer'],
        );
    }

    /**
     * @param array<string,mixed> $claims дополнительные claims (например, roles)
     */
    public function issue(int $userId, array $claims = []): string
    {
        $now = time();
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'sub' => (string)$userId,
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $this->ttl,
        ]);

        return JWT::encode($payload, $this->secret, $this->algo);
    }

    /**
     * @return array<string,mixed> декодированный payload
     * @throws RuntimeException при любой ошибке
     */
    public function decode(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
            return (array)$decoded;
        } catch (ExpiredException $e) {
            throw new RuntimeException('Token expired', 401, $e);
        } catch (SignatureInvalidException $e) {
            throw new RuntimeException('Invalid signature', 401, $e);
        } catch (\Throwable $e) {
            throw new RuntimeException('Invalid token', 401, $e);
        }
    }
}