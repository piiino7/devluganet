<?php

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
$dotenv->load();

return [
    'driver'    => 'mysql',
    'host'      => $_ENV['DB_HOST']     ?? '127.0.0.1',
    'port'      => $_ENV['DB_PORT']     ?? '3306',
    'database'  => $_ENV['DB_NAME']     ?? 'c1scoren',
    'username'  => $_ENV['DB_USER']     ?? 'root',
    'password'  => $_ENV['DB_PASS']     ?? '',
    'charset'   => $_ENV['DB_CHARSET']  ?? 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix'    => '',
];