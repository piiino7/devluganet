<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;
$dbConfig = require dirname(__DIR__) . '/config/database.php';

$capsule->addConnection($dbConfig);

$capsule->setAsGlobal();
$capsule->bootEloquent();

return $capsule;