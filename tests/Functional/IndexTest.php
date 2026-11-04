<?php

declare(strict_types=1);

namespace Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IndexTest extends TestCase
{
    #[Test]
    public function indexLoads(): void
    {
        $res = $this->get('/');

        $this->assertStringContainsString('INDEX', (string) $res->getBody());
        $this->assertEquals(200, $res->getStatusCode());
    }

    #[Test]
    public function unknownRouteDisplaysNotFoundPage(): void
    {
        $res = $this->get('/page-that-does-not-exist');

        $body = (string) $res->getBody();

        $this->assertSame(404, $res->getStatusCode());
        $this->assertStringContainsString('Page not found', $body);
        $this->assertStringContainsString('href="/"', $body);
    }
}
