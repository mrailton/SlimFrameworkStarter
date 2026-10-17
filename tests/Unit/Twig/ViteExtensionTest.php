<?php

declare(strict_types=1);

namespace Tests\Unit\Twig;

use App\Twig\ViteExtension;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\Node\Expression\ArrayExpression;

class ViteExtensionTest extends TestCase
{
    private array $tempFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        $this->tempFiles = [];

        parent::tearDown();
    }

    public static function cssExtensionsProvider(): array
    {
        return [
            'css file' => ['resources/css/app.css'],
            'scss file' => ['resources/css/app.scss'],
            'sass file' => ['resources/css/app.sass'],
            'less file' => ['resources/css/app.less'],
            'styl file' => ['resources/css/app.styl'],
            'uppercase extension' => ['resources/css/app.CSS'],
        ];
    }

    public static function buildUrlProvider(): array
    {
        return [
            'with trailing slash' => ['/dist/', '/dist/assets/app-123.js'],
            'without trailing slash' => ['/dist', '/dist/assets/app-123.js'],
            'full url cdn' => ['https://cdn.example.com/build/', 'https://cdn.example.com/build/assets/app-123.js'],
            'url requiring escaping' => ['/build?v=1&env=prod', '/build?v=1&amp;env=prod/assets/app-123.js'],
        ];
    }

    #[Test]
    public function registersViteFunction(): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        );

        $functions = $extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('vite', $functions[0]->getName());
        $this->assertTrue($functions[0]->needsEnvironment() === false);

        $node = new ArrayExpression([], 1);
        $this->assertSame(['html'], $functions[0]->getSafe($node));
    }

    #[Test]
    public function rendersDevClientAndSingleScriptEntry(): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        );

        $output = $extension->render('resources/js/app.js');

        $expected = implode("\n", [
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            '<script type="module" src="http://localhost:5173/resources/js/app.js"></script>',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    #[DataProvider('cssExtensionsProvider')]
    public function rendersDevClientAndCssEntries(string $entry): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        );

        $output = $extension->render($entry);

        $expected = implode("\n", [
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            sprintf('<link rel="stylesheet" href="http://localhost:5173/%s">', $entry),
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function rendersDevMultipleEntries(): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        );

        $output = $extension->render([
            'resources/js/app.js',
            'resources/css/app.css',
        ]);

        $expected = implode("\n", [
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            '<script type="module" src="http://localhost:5173/resources/js/app.js"></script>',
            '<link rel="stylesheet" href="http://localhost:5173/resources/css/app.css">',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function rendersDevWithCustomDevServer(): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
            devServer: 'http://127.0.0.1:3000',
        );

        $output = $extension->render('resources/js/app.js');

        $expected = implode("\n", [
            '<script type="module" src="http://127.0.0.1:3000/@vite/client"></script>',
            '<script type="module" src="http://127.0.0.1:3000/resources/js/app.js"></script>',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function rendersDevTrimsLeadingSlashesFromEntries(): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        );

        $output = $extension->render('/resources/js/app.js');

        $expected = implode("\n", [
            '<script type="module" src="http://localhost:5173/@vite/client"></script>',
            '<script type="module" src="http://localhost:5173/resources/js/app.js"></script>',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function escapesSpecialCharactersInDevEntryUrls(): void
    {
        $extension = new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        );

        $output = $extension->render('resources/js/app.js?foo=1&bar=2');

        $this->assertStringContainsString('resources/js/app.js?foo=1&amp;bar=2', $output);
    }

    #[Test]
    public function throwsExceptionWhenManifestFileDoesNotExist(): void
    {
        $manifestPath = '/non/existent/path/manifest.json';
        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains("Vite manifest not found at {$manifestPath}. Run `npm run build`.");

        $extension->render('resources/js/app.js');
    }

    #[Test]
    public function throwsExceptionWhenManifestContainsInvalidJson(): void
    {
        $manifestPath = $this->createManifestFile('{ invalid json');
        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $this->expectException(JsonException::class);

        $extension->render('resources/js/app.js');
    }

    #[Test]
    public function throwsExceptionWhenEntryDoesNotExistInManifest(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageIsOrContains("Vite entry 'resources/js/missing.js' not found in manifest.");

        $extension->render('resources/js/missing.js');
    }

    #[Test]
    public function rendersProdSingleScriptEntry(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
                'isEntry' => true,
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $output = $extension->render('resources/js/app.js');

        $this->assertSame('<script type="module" src="/build/assets/app-123.js"></script>', $output);
    }

    #[Test]
    public function rendersProdSingleCssEntry(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/css/app.css' => [
                'file' => 'assets/app-123.css',
                'isEntry' => true,
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $output = $extension->render('resources/css/app.css');

        $this->assertSame('<link rel="stylesheet" href="/build/assets/app-123.css">', $output);
    }

    #[Test]
    public function rendersProdScriptWithAssociatedCssAndPreloadImports(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
                'isEntry' => true,
                'css' => ['assets/app-123.css'],
                'imports' => ['_vendor-456.js'],
            ],
            '_vendor-456.js' => [
                'file' => 'assets/vendor-456.js',
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $output = $extension->render('resources/js/app.js');

        $expected = implode("\n", [
            '<link rel="stylesheet" href="/build/assets/app-123.css">',
            '<script type="module" src="/build/assets/app-123.js"></script>',
            '<link rel="modulepreload" href="/build/assets/vendor-456.js">',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function rendersProdWithRecursiveImportsAndCss(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
                'isEntry' => true,
                'imports' => ['_chunk-a.js'],
            ],
            '_chunk-a.js' => [
                'file' => 'assets/chunk-a.js',
                'css' => ['assets/chunk-a.css'],
                'imports' => ['_chunk-b.js'],
            ],
            '_chunk-b.js' => [
                'file' => 'assets/chunk-b.js',
                'css' => ['assets/chunk-b.css'],
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $output = $extension->render('resources/js/app.js');

        $expected = implode("\n", [
            '<link rel="stylesheet" href="/build/assets/chunk-a.css">',
            '<link rel="stylesheet" href="/build/assets/chunk-b.css">',
            '<script type="module" src="/build/assets/app-123.js"></script>',
            '<link rel="modulepreload" href="/build/assets/chunk-a.js">',
            '<link rel="modulepreload" href="/build/assets/chunk-b.js">',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function handlesCircularDependenciesGracefully(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/entry.js' => [
                'file' => 'assets/entry.js',
                'imports' => ['_chunk-a.js'],
            ],
            '_chunk-a.js' => [
                'file' => 'assets/chunk-a.js',
                'css' => ['assets/chunk-a.css'],
                'imports' => ['_chunk-b.js'],
            ],
            '_chunk-b.js' => [
                'file' => 'assets/chunk-b.js',
                'imports' => ['_chunk-a.js'], // circular import back to chunk-a
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $output = $extension->render('resources/js/entry.js');

        $expected = implode("\n", [
            '<link rel="stylesheet" href="/build/assets/chunk-a.css">',
            '<script type="module" src="/build/assets/entry.js"></script>',
            '<link rel="modulepreload" href="/build/assets/chunk-a.js">',
            '<link rel="modulepreload" href="/build/assets/chunk-b.js">',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    public function deduplicatesCssScriptsAndPreloads(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/page-a.js' => [
                'file' => 'assets/page-a.js',
                'css' => ['assets/common.css'],
                'imports' => ['_common-vendor.js'],
            ],
            'resources/js/page-b.js' => [
                'file' => 'assets/page-b.js',
                'css' => ['assets/common.css'],
                'imports' => ['_common-vendor.js'],
            ],
            '_common-vendor.js' => [
                'file' => 'assets/vendor.js',
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $output = $extension->render([
            'resources/js/page-a.js',
            'resources/js/page-b.js',
        ]);

        $expected = implode("\n", [
            '<link rel="stylesheet" href="/build/assets/common.css">',
            '<script type="module" src="/build/assets/page-a.js"></script>',
            '<script type="module" src="/build/assets/page-b.js"></script>',
            '<link rel="modulepreload" href="/build/assets/vendor.js">',
        ]);

        $this->assertSame($expected, $output);
    }

    #[Test]
    #[DataProvider('buildUrlProvider')]
    public function handlesCustomBuildUrl(string $buildUrl, string $expectedHref): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
            buildUrl: $buildUrl,
        );

        $output = $extension->render('resources/js/app.js');

        $this->assertSame(
            sprintf('<script type="module" src="%s"></script>', $expectedHref),
            $output,
        );
    }

    #[Test]
    public function cachesManifestInMemory(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-original.js',
            ],
        ]);

        $extension = new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        );

        $firstOutput = $extension->render('resources/js/app.js');
        $this->assertStringContainsString('assets/app-original.js', $firstOutput);

        // Delete the manifest file on disk
        unlink($manifestPath);

        // Subsequent call should still succeed using the cached manifest in memory
        $secondOutput = $extension->render('resources/js/app.js');
        $this->assertSame($firstOutput, $secondOutput);
    }

    #[Test]
    public function rendersViteFunctionWithinTwigEnvironmentInDevMode(): void
    {
        $loader = new ArrayLoader([
            'layout.html.twig' => '{{ vite("resources/js/app.js") }}',
        ]);

        $twig = new Environment($loader);
        $twig->addExtension(new ViteExtension(
            isDev: true,
            manifestPath: '/path/to/manifest.json',
        ));

        $output = $twig->render('layout.html.twig');

        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/@vite/client"></script>', $output);
        $this->assertStringContainsString('<script type="module" src="http://localhost:5173/resources/js/app.js"></script>', $output);
        // Ensure HTML is not escaped (i.e. not &lt;script...)
        $this->assertStringNotContainsString('&lt;script', $output);
    }

    #[Test]
    public function rendersViteFunctionWithinTwigEnvironmentInProductionMode(): void
    {
        $manifestPath = $this->createManifestFile([
            'resources/js/app.js' => [
                'file' => 'assets/app-123.js',
            ],
        ]);

        $loader = new ArrayLoader([
            'layout.html.twig' => '{{ vite("resources/js/app.js") }}',
        ]);

        $twig = new Environment($loader);
        $twig->addExtension(new ViteExtension(
            isDev: false,
            manifestPath: $manifestPath,
        ));

        $output = $twig->render('layout.html.twig');

        $this->assertSame('<script type="module" src="/build/assets/app-123.js"></script>', $output);
    }

    private function createManifestFile(array|string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'vite_manifest_');
        if ($path === false) {
            throw new RuntimeException('Failed to create temporary manifest file.');
        }

        $json = is_array($content)
            ? json_encode($content, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
            : $content;

        file_put_contents($path, $json);
        $this->tempFiles[] = $path;

        return $path;
    }
}
