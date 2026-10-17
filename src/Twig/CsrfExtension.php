<?php

declare(strict_types=1);

namespace App\Twig;

use const ENT_QUOTES;

use function htmlspecialchars;

use Slim\Csrf\Guard;

use function sprintf;

use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFunction;

final class CsrfExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(private readonly Guard $guard) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('csrf_fields', $this->renderFields(...), ['is_safe' => ['html']]),
            new TwigFunction('csrf_token', $this->getToken(...)),
        ];
    }

    public function getGlobals(): array
    {
        return [
            'csrf' => [
                'keys' => [
                    'name' => $this->guard->getTokenNameKey(),
                    'value' => $this->guard->getTokenValueKey(),
                ],
            ],
        ];
    }

    public function renderFields(): string
    {
        $token = $this->getToken();

        return sprintf(
            '<input type="hidden" name="%s" value="%s">' . "\n"
            . '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($token['name_key'], ENT_QUOTES),
            htmlspecialchars($token['name'], ENT_QUOTES),
            htmlspecialchars($token['value_key'], ENT_QUOTES),
            htmlspecialchars($token['value'], ENT_QUOTES),
        );
    }

    /**
     * @return array{name_key: string, name: string, value_key: string, value: string}
     */
    public function getToken(): array
    {
        $nameKey = $this->guard->getTokenNameKey();
        $valueKey = $this->guard->getTokenValueKey();
        $name = $this->guard->getTokenName();
        $value = $this->guard->getTokenValue();

        if ($name === null || $value === null) {
            $pair = $this->guard->generateToken();
            $name = $pair[$nameKey];
            $value = $pair[$valueKey];
        }

        return [
            'name_key' => $nameKey,
            'name' => $name,
            'value_key' => $valueKey,
            'value' => $value,
        ];
    }
}
