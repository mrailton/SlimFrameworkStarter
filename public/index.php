<?php

declare(strict_types=1);

use Dotenv\Dotenv;

$app = require_once __DIR__ . '/../bootstrap.php';

if (file_exists(__DIR__ . '/../.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

try {
    $app->run();
} catch (Throwable) {
    // Handle exceptions and errors gracefully
}
