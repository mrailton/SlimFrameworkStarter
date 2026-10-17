<?php

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CsrfTest extends TestCase
{
    #[Test]
    public function loginPageRendersCsrfInputs(): void
    {
        $res = $this->get('/login');
        $body = (string) $res->getBody();

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('name="csrf_name"', $body);
        $this->assertStringContainsString('name="csrf_value"', $body);
    }

    #[Test]
    public function postFailsWhenCsrfTokenIsMissing(): void
    {
        $res = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'securepass123',
        ], withCsrf: false);

        $this->assertSame(400, $res->getStatusCode());
        $this->assertStringContainsString('Failed CSRF check!', (string) $res->getBody());
    }

    #[Test]
    public function postFailsWhenCsrfTokenIsInvalid(): void
    {
        $res = $this->post('/login', [
            'csrf_name' => 'csrf_fake_token_name',
            'csrf_value' => 'invalid_csrf_token_value',
            'email' => 'john@example.com',
            'password' => 'securepass123',
        ], withCsrf: false);

        $this->assertSame(400, $res->getStatusCode());
        $this->assertStringContainsString('Failed CSRF check!', (string) $res->getBody());
    }

    #[Test]
    public function postSucceedsWithValidCsrfToken(): void
    {
        $res = $this->post('/login', [
            'email' => '',
            'password' => '',
        ], withCsrf: true);

        // Passed CSRF validation, reached controller validation (HTTP 422)
        $this->assertSame(422, $res->getStatusCode());
    }
}
