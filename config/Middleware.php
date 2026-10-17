<?php

declare(strict_types=1);

use App\Middleware\SessionMiddleware;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app): void {
    $container = $app->getContainer();
    $settings = $container instanceof ContainerInterface ? $container->get('settings') : [];
    $debug = (bool) ($settings['app']['debug'] ?? false);

    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));

    $app->add(Guard::class);

    $app->addRoutingMiddleware();

    $app->addBodyParsingMiddleware();

    $app->add(SessionMiddleware::class);
};
