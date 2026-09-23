<?php

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
$dotenv->load();

return [
    'secret' => $_ENV['JWT_SECRET'],
    'algo'   => 'HS256',
    'ttl'    => 86400,
    'issuer' => 'c1scoren-api',
];