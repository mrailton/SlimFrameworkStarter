<?php

declare(strict_types=1);

use App\Twig\ViteExtension;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

return [
    ResponseFactoryInterface::class => fn(): ResponseFactoryInterface => new ResponseFactory(),

    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get('settings')['twig'];

        $twig = Twig::create($settings['path'], $settings['options']);

        $twig->addExtension(new ViteExtension(
            isDev: $c->get('settings')['app']['env'] === 'development',
            manifestPath: __DIR__ . '/../public/build/manifest.json',
            buildUrl: '/build/',
            devServer: 'http://localhost:5173',
        ));

        return $twig;
    },
];
