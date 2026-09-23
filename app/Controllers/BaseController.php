<?php

namespace App\Controllers;

abstract class BaseController
{
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    protected function error(string $message, int $status = 400, array $extra = []): void
    {
        $this->json(['error' => array_merge(['message' => $message], $extra)], $status);
    }

    protected function body(): array
    {
        $raw = file_get_contents('php://input');
        if ($raw === '' || $raw === false) {
            return [];
        }
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $this->error('Invalid JSON', 400);
        }
        return $data;
    }
}