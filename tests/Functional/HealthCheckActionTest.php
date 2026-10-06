<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Tests\TestCase;

class HealthCheckActionTest extends TestCase
{
    public function testHealthCheckEndpoint(): void
    {
        $request = $this->createRequest('GET', '/api/health');
        $response = $this->handleRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));

        $data = json_decode((string)$response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('healthy', $data['status']);
        $this->assertArrayHasKey('timestamp', $data);
        $this->assertArrayHasKey('php_version', $data);
    }
}
