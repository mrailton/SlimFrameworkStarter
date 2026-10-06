<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions(require __DIR__ . '/Settings.php');
$containerBuilder->addDefinitions(require __DIR__ . '/Dependencies.php');

return $containerBuilder->build();
