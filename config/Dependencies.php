<?php

declare(strict_types=1);

use App\Auth\AuthManager;
use App\Twig\CsrfExtension;
use App\Twig\ViteExtension;

use function DI\get;

use Doctrine\DBAL\DriverManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

return [
    ResponseFactoryInterface::class => fn(): ResponseFactoryInterface => new ResponseFactory(),

    Guard::class => function (ContainerInterface $c): Guard {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }

        if (!isset($_SESSION['csrf']) || !is_array($_SESSION['csrf'])) {
            $_SESSION['csrf'] = [];
        }

        $responseFactory = $c->get(ResponseFactoryInterface::class);

        return new Guard(
            responseFactory: $responseFactory,
            prefix: 'csrf',
            storage: $_SESSION['csrf'],
            persistentTokenMode: true,
        );
    },

    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get('settings')['twig'];

        $twig = Twig::create($settings['path'], $settings['options']);

        $twig->getEnvironment()->addGlobal('auth', $c->get(AuthManager::class));

        $twig->addExtension(new CsrfExtension($c->get(Guard::class)));

        $twig->addExtension(new ViteExtension(
            isDev: $c->get('settings')['app']['env'] === 'development',
            manifestPath: __DIR__ . '/../public/build/manifest.json',
            buildUrl: '/build/',
            devServer: 'http://localhost:5173',
        ));

        return $twig;
    },

    EntityManagerInterface::class => function (ContainerInterface $c): EntityManagerInterface {
        $doctrineSettings = $c->get('settings')['doctrine'];

        $config = ORMSetup::createAttributeMetadataConfig(
            paths: $doctrineSettings['metadata_dirs'],
            isDevMode: $doctrineSettings['dev_mode'],
        );

        $config->enableNativeLazyObjects(true);

        $connection = DriverManager::getConnection(
            $doctrineSettings['connection'],
            $config,
        );

        return new EntityManager($connection, $config);
    },

    EntityManager::class => get(EntityManagerInterface::class),

    DependencyFactory::class => function (ContainerInterface $c): DependencyFactory {
        $doctrineSettings = $c->get('settings')['doctrine'];
        $migrationsSettings = $doctrineSettings['migrations'] ?? [];

        $config = new ConfigurationArray($migrationsSettings);
        $em = $c->get(EntityManagerInterface::class);

        return DependencyFactory::fromEntityManager(
            $config,
            new ExistingEntityManager($em),
            $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null,
        );
    },

    LoggerInterface::class => function (ContainerInterface $c): LoggerInterface {
        $loggerSettings = $c->get('settings')['logger'];
        $logger = new Logger($loggerSettings['name']);

        $logDir = dirname($loggerSettings['path']);
        if (!is_dir($logDir) && $logDir !== '.' && $logDir !== '') {
            @mkdir($logDir, 0777, true);
        }

        $logger->pushHandler(new StreamHandler($loggerSettings['path'], $loggerSettings['level']));
        return $logger;
    },
];
