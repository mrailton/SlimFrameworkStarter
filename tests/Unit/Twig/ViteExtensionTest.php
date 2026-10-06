<?php

declare(strict_types=1);

namespace App\Tests\Unit\Twig;

use App\Twig\ViteExtension;
use App\View\Vite;
use PHPUnit\Framework\TestCase;
use Twig\Markup;

class ViteExtensionTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/vite_ext_test_' . uniqid('', true);
        mkdir($this->tempDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            $files = glob($this->tempDir . '/*') ?: [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->tempDir);
        }
        parent::tearDown();
    }

    public function testGetFunctions(): void
    {
        $vite = new Vite('/tmp/manifest.json', '/tmp/hot');
        $extension = new ViteExtension($vite);

        $functions = $extension->getFunctions();
        $this->assertCount(2, $functions);

        $names = [];
        foreach ($functions as $function) {
            $names[] = $function->getName();
        }

        $this->assertContains('vite', $names);
        $this->assertContains('vite_asset', $names);
    }

    public function testRenderReturnsMarkup(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        file_put_contents($manifestPath, json_encode([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
            ],
        ], JSON_THROW_ON_ERROR));

        $vite = new Vite($manifestPath, $hotFilePath, '/build');
        $extension = new ViteExtension($vite);

        $result = $extension->render('resources/js/app.js', '/custom-build');

        $this->assertInstanceOf(Markup::class, $result);
        $this->assertSame('<script type="module" src="/custom-build/assets/app-123.js"></script>', (string) $result);
    }

    public function testAssetDelegatesToVite(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        file_put_contents($manifestPath, json_encode([
            'resources/images/logo.png' => [
                'file' => 'assets/logo-123.png',
            ],
        ], JSON_THROW_ON_ERROR));

        $vite = new Vite($manifestPath, $hotFilePath, '/build');
        $extension = new ViteExtension($vite);

        $result = $extension->asset('resources/images/logo.png');

        $this->assertSame('/build/assets/logo-123.png', $result);
    }
}
