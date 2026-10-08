<?php

//declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__) . '/database/db_init.php';
require __DIR__ . '/api_functions.php';

use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Middleware\AuthMiddleware;
use App\Services\JwtService;
use App\Services\RefreshTokenService;
use App\Support\HttpException;
use App\Support\ClientInfo;

$dotenv = Dotenv\Dotenv::createUnsafeImmutable(dirname(__DIR__));
$dotenv->load();

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');

// CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Device-Id, X-Device-Name');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$jwt = JwtService::fromConfig();
$client = ClientInfo::getInfo();
$refreshToken = new RefreshTokenService();

$controllers = [
    AuthController::class   => new AuthController($jwt, $client, $refreshToken),
];

$method = $_SERVER['REQUEST_METHOD'];

$route = $_GET['route'] ?? '/';
$path  = '/' . trim((string)$route, '/');
if ($path === '//') {
    $path = '/';
}

$routes = require __DIR__ . '/routes.php';

$pathMatched = false;
$notFoundPath = $path;

try {
    foreach ($routes as [$routeMethod, $routePath, $controllerClass, $action, $auth]) {
        if (!preg_match(route_to_regex($routePath), $path, $m)) {
            continue;
        }

        $pathMatched = true;

        if ($routeMethod !== $method) {
            continue;
        }

        if ($auth === true) {
            (new AuthMiddleware($jwt))->handle();
        } elseif (is_array($auth) && $auth !== []) {
            (new AuthMiddleware($jwt, $auth))->handle();
        }

        $controller = resolve($controllerClass, $controllers);

        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Handler not found: $controllerClass::$action");
        }

        array_shift($m);
        $controller->$action(...$m);
        exit;
    }

    // путь найден, метод не подходит
    if ($pathMatched) {
        http_response_code(405);
        echo json_encode(['error' => ['message' => 'Method not allowed']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => ['message' => 'Not found: ' . $notFoundPath]], JSON_UNESCAPED_UNICODE);
    exit;

} catch (HttpException $e) {
    http_response_code($e->status);
    import_log('HTTP Exception', [
        'status'    => $e->status,
        'message'   => $e->getMessage(),
    ]);
    $payload = ['error' => [
        'code' => $e->getCode(),
        'message' => $e->getMessage()
    ]];
    if ($e->errors !== []) {
        $payload['error']['errors'] = $e->errors;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;

} catch (RuntimeException $e) {
    http_response_code($e->status);
    import_log('Runtime Exception', [
        'status'    => $e->status,
        'message'   => $e->getMessage(),
    ]);
    $payload = ['error' => [
        'code' => $e->getCode(),
        'message' => $e->getMessage()
    ]];
    if ($e->errors !== []) {
        $payload['error']['errors'] = $e->errors;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
} catch (\Throwable $e) {
    //http_response_code(500);
    import_log('Internal Server Error', [
        'status'    => $e->status,
        'message'   => $e->getMessage(),
    ]);
    echo $e;
    echo json_encode([
        'error' => [
            'code' => $e->getCode(),
            'message' => 'Internal server error',
            'debug'   => $_ENV['APP_DEBUG'] === 'true' ? $e->getMessage() : null,
        ],
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
