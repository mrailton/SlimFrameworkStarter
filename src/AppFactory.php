<?php

declare(strict_types=1);

namespace App;

use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Slim\App;

class AppFactory
{
    public static function createContainer(array $definitions = []): ContainerInterface
    {
        $builder = new ContainerBuilder();
        $builder->useAttributes(true);

        $builder->addDefinitions(require __DIR__ . '/../config/Settings.php');
        $builder->addDefinitions(require __DIR__ . '/../config/Dependencies.php');

        if ($definitions !== []) {
            $builder->addDefinitions($definitions);
        }

        return $builder->build();
    }

    public static function createApp(?ContainerInterface $container = null): App
    {
        $container ??= self::createContainer();
        $app = Bridge::create($container);

        (require __DIR__ . '/../config/Middleware.php')($app);
        (require __DIR__ . '/../config/Routes.php')($app);

        return $app;
    }
}
