<?php

namespace App\Support;

use App\Models\User;

class AuthUser
{
    private static ?int $userId = null;
    private static array $payload = [];
    private static ?User $user = null;

    public static function set(int $userId, array $payload): void
    {
        self::$userId  = $userId;
        self::$payload = $payload;
        self::$user    = null;
    }

    public static function id(): ?int
    {
        return self::$userId;
    }

    public static function payload(): array
    {
        return self::$payload;
    }

    public static function roles(): array
    {
        return (array)(self::$payload['roles'] ?? []);
    }

    public static function user(): ?User
    {
        if (self::$user === null && self::$userId !== null) {
            self::$user = User::find(self::$userId);
        }
        return self::$user;
    }

    public static function requireUser(): User
    {
        $user = self::user();
        if (!$user) {
            throw \App\Support\HttpException::unauthorized();
        }
        return $user;
    }
}
