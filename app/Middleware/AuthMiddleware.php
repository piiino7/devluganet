<?php

namespace App\Middleware;

use App\Services\JwtService;
use App\Support\AuthUser;
use App\Support\HttpException;

class AuthMiddleware
{
    public function __construct(
        private JwtService $jwt,
        private ?array $requiredRoles = null,
    ) {}

    public function handle(): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (!preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
            throw HttpException::unauthorized('Missing or invalid Authorization header');
        }

        $payload = $this->jwt->decode($m[1]);

        if ($this->requiredRoles !== null) {
            $userRole = ($payload['role'] ?? []);
            if (!in_array($userRole, $this->requiredRoles, true)) {
                throw HttpException::forbidden();
            }
        }

        AuthUser::set((int)$payload['sub'], $payload);
    }
}
