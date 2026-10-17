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
}
