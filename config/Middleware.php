<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

return function (App $app): void {
    $container = $app->getContainer();
    $settings = $container instanceof ContainerInterface ? $container->get('settings') : [];
    $debug = (bool) ($settings['app']['debug'] ?? true);

    // Parse JSON, form-data and XML request bodies
    $app->addBodyParsingMiddleware();

    // Add routing middleware
    $app->addRoutingMiddleware();

    // Add Twig view middleware
    $app->add(TwigMiddleware::createFromContainer($app, Twig::class));

    // Add Error middleware
    $logger = $container instanceof ContainerInterface && $container->has(LoggerInterface::class)
        ? $container->get(LoggerInterface::class)
        : null;

    $app->addErrorMiddleware($debug, true, true, $logger);
};
