<?php

declare(strict_types=1);

namespace Tests\Unit\Twig;

use App\Twig\CsrfExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ResponseFactory;
use Twig\Environment;
use Twig\Loader\ArrayLoader;
use Twig\TwigFunction;

class CsrfExtensionTest extends TestCase
{
    private Guard $guard;
    private array $sessionStorage = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->sessionStorage = [];
        $this->guard = new Guard(
            responseFactory: new ResponseFactory(),
            prefix: 'csrf',
            storage: $this->sessionStorage,
            persistentTokenMode: true,
        );
    }

    #[Test]
    public function registersFunctionsAndGlobals(): void
    {
        $extension = new CsrfExtension($this->guard);

        $functions = $extension->getFunctions();
        $functionNames = array_map(fn(TwigFunction $f): string => $f->getName(), $functions);

        $this->assertContains('csrf_fields', $functionNames);
        $this->assertContains('csrf_token', $functionNames);

        $globals = $extension->getGlobals();
        $this->assertArrayHasKey('csrf', $globals);
        $this->assertSame('csrf_name', $globals['csrf']['keys']['name']);
        $this->assertSame('csrf_value', $globals['csrf']['keys']['value']);
    }

    #[Test]
    public function renderFieldsReturnsHiddenHtmlInputs(): void
    {
        $extension = new CsrfExtension($this->guard);

        $html = $extension->renderFields();

        $this->assertStringContainsString('<input type="hidden" name="csrf_name" value="', $html);
        $this->assertStringContainsString('<input type="hidden" name="csrf_value" value="', $html);
    }

    #[Test]
    public function getTokenGeneratesTokenIfNull(): void
    {
        $extension = new CsrfExtension($this->guard);

        $token = $extension->getToken();

        $this->assertSame('csrf_name', $token['name_key']);
        $this->assertSame('csrf_value', $token['value_key']);
        $this->assertNotEmpty($token['name']);
        $this->assertNotEmpty($token['value']);
    }

    #[Test]
    public function rendersInsideTwigEnvironmentWithoutEscaping(): void
    {
        $loader = new ArrayLoader([
            'form.html.twig' => '<form>{{ csrf_fields() }}</form>',
        ]);

        $twig = new Environment($loader);
        $twig->addExtension(new CsrfExtension($this->guard));

        $rendered = $twig->render('form.html.twig');

        $this->assertStringContainsString('<input type="hidden" name="csrf_name"', $rendered);
        $this->assertStringContainsString('<input type="hidden" name="csrf_value"', $rendered);
        $this->assertStringNotContainsString('&lt;input', $rendered);
    }
}
