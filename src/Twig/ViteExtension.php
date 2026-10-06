<?php

declare(strict_types=1);

namespace App\Twig;

use App\View\Vite;
use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFunction;

class ViteExtension extends AbstractExtension
{
    public function __construct(
        private readonly Vite $vite,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('vite', $this->render(...), ['is_safe' => ['html']]),
            new TwigFunction('vite_asset', $this->asset(...)),
        ];
    }

    /**
     * @param string|list<string> $entrypoints
     */
    public function render(string|array $entrypoints, ?string $buildDirectory = null): Markup
    {
        return new Markup($this->vite->render($entrypoints, $buildDirectory), 'UTF-8');
    }

    public function asset(string $path, ?string $buildDirectory = null): string
    {
        return $this->vite->asset($path, $buildDirectory);
    }
}
