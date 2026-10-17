<?php

declare(strict_types=1);

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Dotenv\Dotenv;
use Psr\Container\ContainerInterface;
use Slim\App;

require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

function createContainer(array $definitions = []): ContainerInterface
{
    $builder = new ContainerBuilder();
    $builder->useAttributes(true);

    $builder->addDefinitions(require __DIR__ . '/config/Settings.php');
    $builder->addDefinitions(require __DIR__ . '/config/Dependencies.php');

    if ($definitions !== []) {
        $builder->addDefinitions($definitions);
    }

    return $builder->build();
}

function createApp(?ContainerInterface $container = null): App
{
    $container ??= createContainer();
    $app = Bridge::create($container);

    (require __DIR__ . '/config/Middleware.php')($app);
    (require __DIR__ . '/config/Routes.php')($app);

    return $app;
}

return createApp();
