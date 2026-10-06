<?php

declare(strict_types=1);

namespace App\Action\Api;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HealthCheckAction
{
    public function __invoke(Request $request, Response $response): Response
    {
        $payload = [
            'status' => 'healthy',
            'timestamp' => time(),
            'php_version' => PHP_VERSION,
        ];

        $response->getBody()->write((string)json_encode($payload, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
