<?php

declare(strict_types=1);

use App\Auth\AuthInterface;
use App\Auth\SessionAuth;
use App\Twig\ViteExtension;
use App\View\Vite;

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
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Views\Twig;

return [
    ResponseFactoryInterface::class => fn(): ResponseFactoryInterface => new ResponseFactory(),

    AuthInterface::class => get(SessionAuth::class),

    Vite::class => function (ContainerInterface $c): Vite {
        $settings = $c->get('settings')['vite'];
        return new Vite(
            manifestPath: $settings['manifest'],
            hotFilePath: $settings['hot_file'],
            buildDirectory: $settings['build_directory'] ?? '/build',
        );
    },

    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get('settings')['twig'];
        $twig = Twig::create($settings['path'], $settings['options']);
        $twig->getEnvironment()->addGlobal('auth', $c->get(AuthInterface::class));
        $twig->addExtension(new ViteExtension($c->get(Vite::class)));
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
