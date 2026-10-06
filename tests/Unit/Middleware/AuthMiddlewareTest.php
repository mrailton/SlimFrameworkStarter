<?php

declare(strict_types=1);

namespace App\Tests\Unit\Middleware;

use App\Auth\AuthInterface;
use App\Entity\User;
use App\Middleware\AuthMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;

class AuthMiddlewareTest extends TestCase
{
    public function testRedirectsToLoginWhenUnauthenticated(): void
    {
        $auth = $this->createStub(AuthInterface::class);
        $auth->method('check')->willReturn(false);

        $middleware = new AuthMiddleware($auth, new ResponseFactory());

        $request = new ServerRequestFactory()->createServerRequest('GET', '/users');
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testReturnsJson401WhenUnauthenticatedJsonRequest(): void
    {
        $auth = $this->createStub(AuthInterface::class);
        $auth->method('check')->willReturn(false);

        $middleware = new AuthMiddleware($auth, new ResponseFactory());

        $request = new ServerRequestFactory()
            ->createServerRequest('GET', '/users')
            ->withHeader('Accept', 'application/json');

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->never())->method('handle');

        $response = $middleware->process($request, $handler);

        $this->assertSame(401, $response->getStatusCode());
        $this->assertStringContainsString('application/json', $response->getHeaderLine('Content-Type'));
        $this->assertJsonStringEqualsJsonString(
            (string) json_encode(['error' => 'Unauthorized']),
            (string) $response->getBody(),
        );
    }

    public function testPassesThroughWhenAuthenticated(): void
    {
        $user = new User('Auth User', 'auth@example.com', 'pass');
        $auth = $this->createStub(AuthInterface::class);
        $auth->method('check')->willReturn(true);
        $auth->method('user')->willReturn($user);

        $middleware = new AuthMiddleware($auth, new ResponseFactory());

        $request = new ServerRequestFactory()->createServerRequest('GET', '/users');
        $expectedResponse = new ResponseFactory()->createResponse(200);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($this->callback(fn(ServerRequestInterface $req) => $req->getAttribute('user') === $user))
            ->willReturn($expectedResponse);

        $response = $middleware->process($request, $handler);

        $this->assertSame(200, $response->getStatusCode());
    }
}
