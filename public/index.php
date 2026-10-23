<?php

declare(strict_types=1);

use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app = require_once __DIR__ . '/../bootstrap.php';

try {
    $app->run();
} catch (Throwable) {
    // Handle exceptions and errors gracefully
}
