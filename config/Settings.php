<?php

declare(strict_types=1);

use Monolog\Level;

$rootDir = dirname(__DIR__);

$resolvePath = static function (string $path) use ($rootDir): string {
    if (
        $path === ':memory:'
        || str_starts_with($path, '/')
        || str_starts_with($path, '\\')
        || (strlen($path) > 2 && ctype_alpha($path[0]) && $path[1] === ':')
    ) {
        return $path;
    }

    return $rootDir . '/' . ltrim($path, '/\\');
};

return [
    'settings' => [
        'app' => [
            'name' => $_ENV['APP_NAME'] ?? 'Slim Starter',
            'env' => $_ENV['APP_ENV'] ?? 'development',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
        ],
        'twig' => [
            'path' => $rootDir . '/resources/templates',
            'options' => [
                'cache' => ($_ENV['TWIG_CACHE'] ?? false) === 'true' ? $rootDir . '/var/cache/twig' : false,
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'auto_reload' => true,
            ],
        ],
        'doctrine' => [
            'dev_mode' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
            'cache_dir' => $rootDir . '/var/cache/doctrine',
            'metadata_dirs' => [$rootDir . '/src/Entity'],
            'connection' => [
                'driver' => $_ENV['DB_DRIVER'] ?? 'pdo_sqlite',
                'path' => ($_ENV['DB_DRIVER'] ?? 'pdo_sqlite') === 'pdo_sqlite'
                    ? (isset($_ENV['DB_PATH']) ? $resolvePath($_ENV['DB_PATH']) : $rootDir . '/var/app.sqlite')
                    : null,
                'host' => $_ENV['DB_HOST'] ?? '127.0.0.1',
                'port' => (int) ($_ENV['DB_PORT'] ?? 3306),
                'dbname' => $_ENV['DB_NAME'] ?? 'slim_app',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASSWORD'] ?? '',
                'charset' => 'utf8mb4',
            ],
            'migrations' => [
                'table_storage' => [
                    'table_name' => 'doctrine_migration_versions',
                    'version_column_name' => 'version',
                    'version_column_length' => 191,
                    'executed_at_column_name' => 'executed_at',
                    'execution_time_column_name' => 'execution_time',
                ],
                'migrations_paths' => [
                    'App\Migrations' => $rootDir . '/migrations',
                ],
                'all_or_nothing' => true,
                'transactional' => true,
                'check_database_platform' => true,
                'organize_migrations' => 'none',
            ],
        ],
        'logger' => [
            'name' => 'app',
            'path' => isset($_ENV['LOG_PATH']) ? $resolvePath($_ENV['LOG_PATH']) : $rootDir . '/var/log/app.log',
            'level' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN) ? Level::Debug : Level::Info,
        ],
        'vite' => [
            'manifest' => $rootDir . '/public/build/manifest.json',
            'hot_file' => $rootDir . '/public/hot',
            'build_directory' => '/build',
        ],
    ],
];
