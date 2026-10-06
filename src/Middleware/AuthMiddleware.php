<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Auth\AuthInterface;
use App\Entity\User;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly AuthInterface $auth,
        private readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->auth->check()) {
            $accept = $request->getHeaderLine('Accept');
            if (str_contains($accept, 'application/json')) {
                $response = $this->responseFactory->createResponse(401);
                $response->getBody()->write((string) json_encode(['error' => 'Unauthorized']));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response = $this->responseFactory->createResponse(302);
            return $response->withHeader('Location', '/login');
        }

        $user = $this->auth->user();
        if ($user instanceof User) {
            $request = $request->withAttribute('user', $user);
        }

        return $handler->handle($request);
    }
}
