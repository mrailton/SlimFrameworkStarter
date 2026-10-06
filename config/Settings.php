<?php

declare(strict_types=1);

use Monolog\Level;

return [
    'settings' => [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'Slim Starter',
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ],
        'twig' => [
            'path' => __DIR__ . '/../templates',
            'options' => [
                'cache' => ($_ENV['TWIG_CACHE'] ?? false) === 'true' ? __DIR__ . '/../var/cache/twig' : false,
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auto_reload' => true,
            ],
        ],
        'doctrine' => [
            'dev_mode' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'cache_dir' => __DIR__ . '/../var/cache/doctrine',
            'metadata_dirs' => [__DIR__ . '/../src/Entity'],
            'connection' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_sqlite',
                'path' => ($_ENV['DB_DRIVER'] ?? 'pdo_sqlite') === 'pdo_sqlite' 
                    ? ($_ENV['DB_PATH'] ?? __DIR__ . '/../var/app.sqlite')
                    : null,
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int)($_ENV['DB_PORT'] ?? 3306),
                'dbname' => $_ENV['DB_NAME'] ?? 'slim_app',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
            ],
        ],
        'logger' => [
            'name' => 'app',
            'path' => $_ENV['LOG_PATH'] ?? __DIR__ . '/../var/log/app.log',
            'level' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN) ? Level::Debug : Level::Info,
        ],
    ],
];
