<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/database/db_init.php';
require __DIR__ . '/import_functions.php';
require __DIR__ . '/app/Support/import_log.php';

use App\Models\User;

import_log('=== request ===', [
    'type'   => $_GET['type']   ?? null,
    'mode'   => $_GET['mode']   ?? null,
    'file'   => $_GET['filename'] ?? null,
    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
    'ip'     => $_SERVER['REMOTE_ADDR'] ?? null,
    'user'   => $_SERVER['PHP_AUTH_USER'] ?? null,
]);

$operator = User::whereHas('roles', fn($q) => $q->where('name', 'operator'))->first();

if (!$operator) {
    import_log('operator_1c not found');
    http_response_code(500);
    echo "failure\nOperator not found";
    exit;
}

$DIR      = __DIR__ . '/1c_catalog';
$FILE_LIMIT = 20 * 1024 * 1024;

if (!is_dir($DIR)) {
    mkdir($DIR, 0775, true);
}

$authOk = false;

if (isset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW'])) {
    if ($_SERVER['PHP_AUTH_USER'] === $operator->name && $operator->verifyPassword($_SERVER['PHP_AUTH_PW'])) {
        $authOk = true;
    }
} elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    if (preg_match('/^Basic\s+(.+)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        [$u, $p] = explode(':', base64_decode($m[1]), 2) + ['', ''];
        if ($u === $operator->name && $operator->verifyPassword($p)) {
            $authOk = true;
        }
    }
}

if (!$authOk) {
    import_log('auth failed');
    header('WWW-Authenticate: Basic realm="1C Exchange"');
    header('HTTP/1.0 401 Unauthorized');
    echo "failure\nAuthorization required";
    exit;
}

import_log('auth ok');

function handleCatalog(string $mode, string $dir, int $fileLimit): void
{
    import_log("catalog mode=$mode");

    switch ($mode) {
        case 'checkauth':
            echo "success\n";
            echo "PHPSESSID\n";
            echo session_id() ?: bin2hex(random_bytes(16));
            echo "\n";
            break;

        case 'init':
            echo "zip=no\n";
            echo "file_limit={$fileLimit}\n";
            break;

        case 'file':
            $name = basename($_GET['filename'] ?? '');
            if ($name === '') {
                import_log('file: no filename');
                echo "failure\nNo filename";
                break;
            }
            $data = file_get_contents('php://input');
            if ($data === false) {
                import_log('file: cannot read body', ['name' => $name]);
                echo "failure\nCannot read body";
                break;
            }

            $bytes = strlen($data);
            $written = file_put_contents($dir . '/' . $name, $data);
            import_log('file saved', [
                'name'    => $name,
                'bytes'   => $bytes,
                'written' => $written,
                'path'    => $dir . '/' . $name,
            ]);

            if ($written === false) {
                echo "failure\nCannot write file";
                break;
            }

            echo "success\n";
            break;

        case 'import':
            $name = basename($_GET['filename'] ?? '');
            if ($name === '') {
                import_log('import: no filename');
                echo "failure\nNo filename";
                break;
            }

            $path = $dir . '/' . $name;
            if (!is_file($path)) {
                import_log('import: file not found', ['path' => $path]);
                echo "failure\nFile not found: $name";
                break;
            }

            $start = microtime(true);

            try {
                importFile($path);

                import_log('import success', [
                    'file'    => $name,
                    'seconds' => round(microtime(true) - $start, 3),
                ]);

                echo "success\n";
            } catch (Throwable $e) {
                import_log('import FAILED', [
                    'file'    => $name,
                    'seconds' => round(microtime(true) - $start, 3),
                    'error'   => $e->getMessage(),
                    'file_at' => $e->getFile() . ':' . $e->getLine(),
                    'trace'   => $e->getTraceAsString(),
                ]);

                echo "failure\n" . $e->getMessage();
            }
            break;

        default:
            import_log("unknown mode: $mode");
            http_response_code(400);
            echo "failure\nUnknown catalog mode";
    }
}


$type = $_GET['type'] ?? '';
$mode = $_GET['mode'] ?? '';

switch ($type) {
    case 'catalog': handleCatalog($mode, $DIR, $FILE_LIMIT); break;
    default:
        http_response_code(400);
        echo "failure\nUnknown type";
}
exit;

