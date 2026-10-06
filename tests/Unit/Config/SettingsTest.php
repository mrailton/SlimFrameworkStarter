<?php

declare(strict_types=1);

namespace App\Tests\Unit\Config;

use PHPUnit\Framework\TestCase;

class SettingsTest extends TestCase
{
    private array $envBackup;

    protected function setUp(): void
    {
        parent::setUp();
        $this->envBackup = $_ENV;
    }

    protected function tearDown(): void
    {
        $_ENV = $this->envBackup;
        parent::tearDown();
    }

    public function testDefaultPathsResolveToProjectRoot(): void
    {
        unset($_ENV['DB_PATH'], $_ENV['LOG_PATH']);

        $config = require __DIR__ . '/../../../config/Settings.php';
        $settings = $config['settings'];
        $projectRoot = realpath(__DIR__ . '/../../..');

        $this->assertSame($projectRoot . '/var/app.sqlite', $settings['doctrine']['connection']['path']);
        $this->assertSame($projectRoot . '/var/log/app.log', $settings['logger']['path']);
    }

    public function testRelativePathsInEnvResolveToProjectRoot(): void
    {
        $_ENV['DB_PATH'] = 'var/custom.sqlite';
        $_ENV['LOG_PATH'] = 'var/log/custom.log';

        $config = require __DIR__ . '/../../../config/Settings.php';
        $settings = $config['settings'];
        $projectRoot = realpath(__DIR__ . '/../../..');

        $this->assertSame($projectRoot . '/var/custom.sqlite', $settings['doctrine']['connection']['path']);
        $this->assertSame($projectRoot . '/var/log/custom.log', $settings['logger']['path']);
    }

    public function testAbsolutePathsInEnvRemainAbsolute(): void
    {
        $_ENV['DB_PATH'] = '/tmp/test.sqlite';
        $_ENV['LOG_PATH'] = '/tmp/test.log';

        $config = require __DIR__ . '/../../../config/Settings.php';
        $settings = $config['settings'];

        $this->assertSame('/tmp/test.sqlite', $settings['doctrine']['connection']['path']);
        $this->assertSame('/tmp/test.log', $settings['logger']['path']);
    }

    public function testMemoryDbPathRemainsMemory(): void
    {
        $_ENV['DB_PATH'] = ':memory:';

        $config = require __DIR__ . '/../../../config/Settings.php';
        $settings = $config['settings'];

        $this->assertSame(':memory:', $settings['doctrine']['connection']['path']);
    }

    public function testMigrationsSettingsAreConfigured(): void
    {
        $config = require __DIR__ . '/../../../config/Settings.php';
        $doctrine = $config['settings']['doctrine'];
        $projectRoot = realpath(__DIR__ . '/../../..');

        $this->assertArrayHasKey('migrations', $doctrine);
        $this->assertSame('doctrine_migration_versions', $doctrine['migrations']['table_storage']['table_name']);
        $this->assertSame(191, $doctrine['migrations']['table_storage']['version_column_length']);
        $this->assertSame($projectRoot . '/migrations', $doctrine['migrations']['migrations_paths']['App\Migrations']);
    }
}
