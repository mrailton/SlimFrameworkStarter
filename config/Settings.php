<?php

declare(strict_types=1);

$rootDir = dirname(__DIR__);

return [
    'settings' => [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'Slim Starter',
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ],

        'twig' => [
            'path' => $rootDir . '/resources/views',
            'options' => [
                'cache' => ($_ENV['TWIG_CACHE'] ?? false) === 'true' ? $rootDir . '/var/cache/twig' : false,
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auto_reload' => true,
            ],
        ],
    ],
];
