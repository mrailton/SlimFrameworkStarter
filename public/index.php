<?php

declare(strict_types=1);

use App\AppFactory;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$app = AppFactory::createApp();

try {
    $app->run();
} catch (Throwable) {
    // Handle exceptions and errors gracefully
}
