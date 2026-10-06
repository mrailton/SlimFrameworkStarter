<?php

declare(strict_types=1);

use App\Repository\UserRepository;
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
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use function DI\get;

return [
    Twig::class => function (ContainerInterface $c): Twig {
        $settings = $c->get('settings')['twig'];
        $twig = Twig::create($settings['path'], $settings['options']);
        return $twig;
    },

    EntityManagerInterface::class => function (ContainerInterface $c): EntityManagerInterface {
        $doctrineSettings = $c->get('settings')['doctrine'];
        
        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: $doctrineSettings['metadata_dirs'],
            isDevMode: $doctrineSettings['dev_mode'],
        );

        if (PHP_VERSION_ID >= 80400) {
            $config->enableNativeLazyObjects(true);
        }

        $connection = DriverManager::getConnection(
            $doctrineSettings['connection'],
            $config
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
            $c->has(LoggerInterface::class) ? $c->get(LoggerInterface::class) : null
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

    UserRepository::class => function (ContainerInterface $c): UserRepository {
        return new UserRepository($c->get(EntityManagerInterface::class));
    },
];
