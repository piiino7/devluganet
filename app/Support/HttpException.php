<?php

namespace App\Support;

use RuntimeException;

class HttpException extends RuntimeException
{
    public function __construct(
        public int $status,
        string $message,
        public array $errors = [],
    ) {
        parent::__construct($message, $status);
    }

    public static function notFound(string $m = 'Not found'): self
    {
        return new self(404, $m);
    }

    public static function unauthorized(string $m = 'Unauthorized'): self
    {
        return new self(401, $m);
    }

    public static function forbidden(string $m = 'Forbidden'): self
    {
        return new self(403, $m);
    }

    public static function validation(array $errors, string $m = 'Validation failed'): self
    {
        return new self(422, $m, $errors);
    }

    public static function badRequest(string $m = 'Bad request'): self
    {
        return new self(400, $m);
    }
}
