<?php

declare(strict_types=1);

$app = require_once __DIR__ . '/../bootstrap.php';

try {
    $app->run();
} catch (Throwable) {
    // Handle exceptions and errors gracefully
}
