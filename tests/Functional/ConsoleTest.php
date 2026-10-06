<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\TestCase;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\ConsoleRunner as MigrationsConsoleRunner;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Console\ConsoleRunner as OrmConsoleRunner;
use Doctrine\ORM\Tools\Console\EntityManagerProvider\SingleManagerProvider;
use Symfony\Component\Console\Application;

class ConsoleTest extends TestCase
{
    public function testDependencyFactoryIsRegisteredInContainer(): void
    {
        $this->assertTrue($this->container->has(DependencyFactory::class));
        $dependencyFactory = $this->container->get(DependencyFactory::class);
        $this->assertInstanceOf(DependencyFactory::class, $dependencyFactory);
    }

    public function testConsoleRegistersOrmAndMigrationCommands(): void
    {
        $cli = new Application('Test Console');

        $em = $this->container->get(EntityManagerInterface::class);
        $emProvider = new SingleManagerProvider($em);
        OrmConsoleRunner::addCommands($cli, $emProvider);

        $dependencyFactory = $this->container->get(DependencyFactory::class);
        MigrationsConsoleRunner::addCommands($cli, $dependencyFactory);

        $this->assertTrue($cli->has('orm:schema-tool:create'));
        $this->assertTrue($cli->has('orm:schema-tool:update'));
        $this->assertTrue($cli->has('migrations:migrate'));
        $this->assertTrue($cli->has('migrations:diff'));
        $this->assertTrue($cli->has('migrations:status'));
        $this->assertTrue($cli->has('migrations:version'));
        $this->assertTrue($cli->has('migrations:dump-schema'));
        $this->assertTrue($cli->has('migrations:execute'));
        $this->assertTrue($cli->has('migrations:generate'));
        $this->assertTrue($cli->has('migrations:latest'));
        $this->assertTrue($cli->has('migrations:list'));
        $this->assertTrue($cli->has('migrations:rollup'));
        $this->assertTrue($cli->has('migrations:sync-metadata-storage'));
        $this->assertTrue($cli->has('migrations:up-to-date'));
    }
}
