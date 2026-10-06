<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\TestCase;

class HomeActionTest extends TestCase
{
    public function testHomePageRendersSuccessfully(): void
    {
        $request = $this->createRequest('GET', '/');
        $response = $this->handleRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Welcome to your Slim Starter App', $body);
        $this->assertStringContainsString('Doctrine ORM', $body);
        $this->assertStringContainsString('Twig', $body);
    }
}
