<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app): void {
    $container = $app->getContainer();
    $settings = $container instanceof ContainerInterface ? $container->get('settings') : [];
    $debug = (bool) ($settings['app']['debug'] ?? false);

    $app->addBodyParsingMiddleware();

    $app->addRoutingMiddleware();

    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));
};
