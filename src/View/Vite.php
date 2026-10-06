<?php

declare(strict_types=1);

namespace App\View;

use InvalidArgumentException;
use JsonException;
use RuntimeException;

class Vite
{
    /**
     * @var array<string, array{file: string, src?: string, isEntry?: bool, css?: list<string>, imports?: list<string>}>|null
     */
    private ?array $manifestData = null;

    public function __construct(
        private readonly string $manifestPath,
        private readonly string $hotFilePath,
        private readonly string $buildDirectory = '/build',
    ) {}

    public function isHot(): bool
    {
        return file_exists($this->hotFilePath);
    }

    public function getHotUrl(): string
    {
        if (!$this->isHot()) {
            throw new RuntimeException("Vite hot file does not exist at \"{$this->hotFilePath}\".");
        }

        $url = trim((string) file_get_contents($this->hotFilePath));
        return rtrim($url, '/');
    }

    /**
     * @param string|list<string> $entrypoints
     */
    public function render(string|array $entrypoints, ?string $buildDirectory = null): string
    {
        $entries = is_array($entrypoints) ? $entrypoints : [$entrypoints];
        $buildDir = rtrim($buildDirectory ?? $this->buildDirectory, '/');

        if ($this->isHot()) {
            $hotUrl = $this->getHotUrl();
            $tags = [
                sprintf('<script type="module" src="%s/@vite/client"></script>', $hotUrl),
            ];

            foreach ($entries as $entry) {
                $entryUrl = $hotUrl . '/' . ltrim($entry, '/');
                if ($this->isCssFile($entry)) {
                    $tags[] = sprintf('<link rel="stylesheet" href="%s">', $entryUrl);
                } else {
                    $tags[] = sprintf('<script type="module" src="%s"></script>', $entryUrl);
                }
            }

            return implode("\n", $tags);
        }

        $manifest = $this->getManifestData();
        $tags = [];
        $renderedCss = [];
        $renderedJs = [];

        foreach ($entries as $entry) {
            if (!isset($manifest[$entry])) {
                throw new InvalidArgumentException(
                    "Vite entrypoint \"{$entry}\" not found in manifest \"{$this->manifestPath}\".",
                );
            }

            $chunk = $manifest[$entry];

            if (isset($chunk['css']) && is_array($chunk['css'])) {
                foreach ($chunk['css'] as $cssFile) {
                    if (!isset($renderedCss[$cssFile])) {
                        $renderedCss[$cssFile] = true;
                        $tags[] = sprintf('<link rel="stylesheet" href="%s/%s">', $buildDir, ltrim($cssFile, '/'));
                    }
                }
            }

            $file = $chunk['file'];
            if ($this->isCssFile($file)) {
                if (!isset($renderedCss[$file])) {
                    $renderedCss[$file] = true;
                    $tags[] = sprintf('<link rel="stylesheet" href="%s/%s">', $buildDir, ltrim($file, '/'));
                }
            } else {
                if (!isset($renderedJs[$file])) {
                    $renderedJs[$file] = true;
                    $tags[] = sprintf('<script type="module" src="%s/%s"></script>', $buildDir, ltrim($file, '/'));
                }
            }
        }

        return implode("\n", $tags);
    }

    public function asset(string $path, ?string $buildDirectory = null): string
    {
        if ($this->isHot()) {
            return $this->getHotUrl() . '/' . ltrim($path, '/');
        }

        $manifest = $this->getManifestData();
        $buildDir = rtrim($buildDirectory ?? $this->buildDirectory, '/');

        if (isset($manifest[$path]['file'])) {
            return $buildDir . '/' . ltrim($manifest[$path]['file'], '/');
        }

        return $buildDir . '/' . ltrim($path, '/');
    }

    /**
     * @return array<string, array{file: string, src?: string, isEntry?: bool, css?: list<string>, imports?: list<string>}>
     */
    private function getManifestData(): array
    {
        if ($this->manifestData !== null) {
            return $this->manifestData;
        }

        if (!file_exists($this->manifestPath)) {
            throw new RuntimeException(
                "Vite manifest not found at \"{$this->manifestPath}\". Run \"npm run build\" or start dev server with \"npm run dev\".",
            );
        }

        $content = (string) file_get_contents($this->manifestPath);

        try {
            /** @var array<string, array{file: string, src?: string, isEntry?: bool, css?: list<string>, imports?: list<string>}> $data */
            $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
            $this->manifestData = $data;
            return $this->manifestData;
        } catch (JsonException $e) {
            throw new RuntimeException("Malformed Vite manifest at \"{$this->manifestPath}\": " . $e->getMessage(), 0, $e);
        }
    }

    private function isCssFile(string $file): bool
    {
        $clean = parse_url($file, PHP_URL_PATH) ?? $file;
        return (bool) preg_match('/\.(css|less|sass|scss|styl|stylus|pcss|postcss)$/i', (string) $clean);
    }
}
