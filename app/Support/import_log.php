<?php

function import_log(string $message, array $context = []): void
{
    $dir = dirname(__DIR__, 2) . '/storage/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    $line = sprintf(
        "[%s] %s%s\n",
        date('Y-m-d H:i:s'),
        $message,
        $context !== [] ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : ''
    );

    file_put_contents($dir . '/import.log', $line, FILE_APPEND | LOCK_EX);
}