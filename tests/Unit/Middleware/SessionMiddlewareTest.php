<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use App\Middleware\SessionMiddleware;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class SessionMiddlewareTest extends TestCase
{
    #[Test]
    public function processCallsHandlerAndReturnsResponse(): void
    {
        $middleware = new SessionMiddleware();
        $request = new ServerRequestFactory()->createServerRequest('GET', '/');
        $expectedResponse = new ResponseFactory()->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($request)
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $handler);

        $this->assertSame($expectedResponse, $response);
    }
}
