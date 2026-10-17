<?php

declare(strict_types=1);

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

return [
    ResponseFactoryInterface::class => fn(): ResponseFactoryInterface => new ResponseFactory(),

    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get('settings')['twig'];

        return Twig::create($settings['path'], $settings['options']);
    },
];
