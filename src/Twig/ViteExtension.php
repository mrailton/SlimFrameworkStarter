<?php

declare(strict_types=1);

namespace App\Twig;

use RuntimeException;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ViteExtension extends AbstractExtension
{
    private ?array $manifest = null;

    public function __construct(
        private readonly bool $isDev,
        private readonly string $manifestPath,
        private readonly string $buildUrl = '/build/',
        private readonly string $devServer = 'http://localhost:5173',
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite', $this->render(...), ['is_safe' => ['html']]),
        ];
    }

    /** @param string|string[] $entries */
    public function render(string|array $entries): string
    {
        $entries = (array) $entries;

        return $this->isDev
            ? $this->renderDev($entries)
            : $this->renderProd($entries);
    }

    private function renderDev(array $entries): string
    {
        $tags = [sprintf('<script type="module" src="%s/@vite/client"></script>', $this->devServer)];

        foreach ($entries as $entry) {
            $url = $this->e($this->devServer . '/' . ltrim($entry, '/'));
            $tags[] = $this->isCss($entry)
                ? sprintf('<link rel="stylesheet" href="%s">', $url)
                : sprintf('<script type="module" src="%s"></script>', $url);
        }

        return implode("\n", $tags);
    }

    private function renderProd(array $entries): string
    {
        $manifest = $this->loadManifest();
        $css = $preload = $scripts = [];

        foreach ($entries as $entry) {
            if (!isset($manifest[$entry])) {
                throw new RuntimeException("Vite entry '{$entry}' not found in manifest.");
            }

            $chunk = $manifest[$entry];

            if ($this->isCss($entry)) {
                $css[] = $chunk['file'];
            } else {
                $scripts[] = $chunk['file'];
            }

            foreach ($this->collectCss($manifest, $entry) as $file) {
                $css[] = $file;
            }
            foreach ($this->collectImports($manifest, $entry) as $file) {
                $preload[] = $file;
            }
        }

        $tags = [];
        foreach (array_unique($css) as $file) {
            $tags[] = sprintf('<link rel="stylesheet" href="%s">', $this->url($file));
        }
        foreach (array_unique($scripts) as $file) {
            $tags[] = sprintf('<script type="module" src="%s"></script>', $this->url($file));
        }
        foreach (array_unique($preload) as $file) {
            $tags[] = sprintf('<link rel="modulepreload" href="%s">', $this->url($file));
        }

        return implode("\n", $tags);
    }

    /** CSS from the chunk itself and everything it imports, recursively. */
    private function collectCss(array $manifest, string $key, array &$seen = []): array
    {
        if (isset($seen[$key]) || !isset($manifest[$key])) {
            return [];
        }
        $seen[$key] = true;

        $files = $manifest[$key]['css'] ?? [];
        foreach ($manifest[$key]['imports'] ?? [] as $import) {
            $files = array_merge($files, $this->collectCss($manifest, $import, $seen));
        }

        return $files;
    }

    /** JS files of imported chunks, for modulepreload. */
    private function collectImports(array $manifest, string $key, array &$seen = []): array
    {
        $files = [];
        foreach ($manifest[$key]['imports'] ?? [] as $import) {
            if (isset($seen[$import]) || !isset($manifest[$import])) {
                continue;
            }
            $seen[$import] = true;
            $files[] = $manifest[$import]['file'];
            $files = array_merge($files, $this->collectImports($manifest, $import, $seen));
        }

        return $files;
    }

    private function loadManifest(): array
    {
        if ($this->manifest !== null) {
            return $this->manifest;
        }

        if (!is_file($this->manifestPath)) {
            throw new RuntimeException("Vite manifest not found at {$this->manifestPath}. Run `npm run build`.");
        }

        return $this->manifest = json_decode(
            file_get_contents($this->manifestPath),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    private function url(string $file): string
    {
        return $this->e(rtrim($this->buildUrl, '/') . '/' . ltrim($file, '/'));
    }

    private function isCss(string $file): bool
    {
        return (bool) preg_match('/\.(css|scss|sass|less|styl)$/i', $file);
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES);
    }
}
