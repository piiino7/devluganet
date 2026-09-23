<?php

//declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/database/db_init.php';
require __DIR__ . '/import_functions.php';

$files = array_slice($argv, 1);

if ($files === []) {
    // Если аргументов нет — берём всё из 1c_catalog
    $files = glob(__DIR__ . '/1c_catalog/*.xml') ?: [];
}

if ($files === []) {
    fwrite(STDERR, "No XML files found.\n");
    exit(1);
}

foreach ($files as $file) {
    if (!is_file($file)) {
        fwrite(STDERR, "File not found: $file\n");
        continue;
    }

    echo "Importing $file...\n";

    try {
        DB::connection()->transaction(function () use ($file) {
            $xml  = load_xml($file);
            $type = detect_type($xml);

            if ($type === 'offers') {
                importOffers($xml);
            } else {
                importProducts($xml);
            }
        });
        echo "  done.\n";
    } catch (Throwable $e) {
        fwrite(STDERR, "  FAILED: " . $e->getMessage() . "\n");
        exit(2);
    }
}

echo "All done.\n";
