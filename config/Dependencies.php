<?php

declare(strict_types=1);

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;

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

    EntityManager::class => \DI\get(EntityManagerInterface::class),

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
        /** @var EntityManagerInterface $em */
        $em = $c->get(EntityManagerInterface::class);
        return new UserRepository($em);
    },
];
