<?php

declare(strict_types=1);

namespace App\Tests\Unit\View;

use App\View\Vite;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class ViteTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempDir = sys_get_temp_dir() . '/vite_test_' . uniqid('', true);
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

    public function testHotModeDetectionAndUrl(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        $vite = new Vite($manifestPath, $hotFilePath);
        $this->assertFalse($vite->isHot());

        file_put_contents($hotFilePath, "http://localhost:5173/\n");
        $this->assertTrue($vite->isHot());
        $this->assertSame('http://localhost:5173', $vite->getHotUrl());
    }

    public function testGetHotUrlThrowsWhenNotHot(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        $vite = new Vite($manifestPath, $hotFilePath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Vite hot file does not exist');
        $vite->getHotUrl();
    }

    public function testRenderInHotMode(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';
        file_put_contents($hotFilePath, 'http://localhost:5173');

        $vite = new Vite($manifestPath, $hotFilePath);
        $rendered = $vite->render(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/@vite/client"></script>', $rendered);
        $this->assertStringContainsString('<link rel="stylesheet" href="http://localhost:5173/resources/css/app.css">', $rendered);
        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/resources/js/app.js"></script>', $rendered);
    }

    public function testRenderInManifestMode(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        $manifestContent = json_encode([
            'resources/css/app.css' => [
                'file' => 'assets/app-123.css',
                'isEntry' => true,
            ],
            'resources/js/app.js' => [
                'file' => 'assets/app-456.js',
                'css' => ['assets/vendor-789.css'],
                'isEntry' => true,
            ],
        ], JSON_THROW_ON_ERROR);

        file_put_contents($manifestPath, $manifestContent);

        $vite = new Vite($manifestPath, $hotFilePath, '/build');
        $rendered = $vite->render(['resources/css/app.css', 'resources/js/app.js']);

        $this->assertStringContainsString('<link rel="stylesheet" href="/build/assets/app-123.css">', $rendered);
        $this->assertStringContainsString('<link rel="stylesheet" href="/build/assets/vendor-789.css">', $rendered);
        $this->assertStringContainsString('<script type="module" src="/build/assets/app-456.js"></script>', $rendered);
    }

    public function testRenderThrowsWhenManifestMissing(): void
    {
        $manifestPath = $this->tempDir . '/missing_manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        $vite = new Vite($manifestPath, $hotFilePath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Vite manifest not found');
        $vite->render('resources/js/app.js');
    }

    public function testRenderThrowsWhenManifestMalformed(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';
        file_put_contents($manifestPath, '{invalid_json}');

        $vite = new Vite($manifestPath, $hotFilePath);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains('Malformed Vite manifest');
        $vite->render('resources/js/app.js');
    }

    public function testRenderThrowsWhenEntrypointNotFound(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';
        file_put_contents($manifestPath, json_encode([], JSON_THROW_ON_ERROR));

        $vite = new Vite($manifestPath, $hotFilePath);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageIsOrContains('Vite entrypoint "resources/js/unknown.js" not found');
        $vite->render('resources/js/unknown.js');
    }

    public function testAssetResolution(): void
    {
        $manifestPath = $this->tempDir . '/manifest.json';
        $hotFilePath = $this->tempDir . '/hot';

        file_put_contents($manifestPath, json_encode([
            'resources/images/logo.png' => [
                'file' => 'assets/logo-123.png',
            ],
        ], JSON_THROW_ON_ERROR));

        $vite = new Vite($manifestPath, $hotFilePath, '/build');

        $this->assertSame('/build/assets/logo-123.png', $vite->asset('resources/images/logo.png'));
        $this->assertSame('/build/images/static.png', $vite->asset('images/static.png'));

        file_put_contents($hotFilePath, 'http://localhost:5173');
        $this->assertSame('http://localhost:5173/resources/images/logo.png', $vite->asset('resources/images/logo.png'));
    }
}
