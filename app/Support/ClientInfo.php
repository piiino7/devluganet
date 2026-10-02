<?php

namespace App\Support;

class ClientInfo {
    public function __construct() {}

    public static function getInfo(): array
    {
        return [
            'ip'              => self::ip(),
            'forwarded_for'   => self::forwardedFor(),
            'user_agent'      => self::userAgent(),
            'referer'         => self::referer(),
            'accept_language' => self::acceptLanguage(),
            'method'          => $_SERVER['REQUEST_METHOD'] ?? null,
            'path'            => $_SERVER['REQUEST_URI'] ?? null,
            'request_id'      => self::requestId(),
        ];
    }

    public static function ip(): ?string
    {
        // Cloudflare
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        }

        // Nginx
        if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
            return $_SERVER['HTTP_X_REAL_IP'];
        }

        // Прокси/балансировщик — первый IP = клиент
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            return $ips[0] ?: null;
        }

        return $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public static function forwardedFor(): ?string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null;
    }

    public static function userAgent(): ?string
    {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        if ($ua === null) {
            return null;
        }
        // Ограничить длину, чтобы не хранить мегабайты
        return mb_substr($ua, 0, 500);
    }

    public static function referer(): ?string
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? null;
        if ($ref === null) {
            return null;
        }
        return mb_substr($ref, 0, 500);
    }

    public static function acceptLanguage(): ?string
    {
        return $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;
    }

    public static function requestId(): string
    {
        static $id = null;
        if ($id === null) {
            $id = bin2hex(random_bytes(8));
        }
        return $id;
    }
}
