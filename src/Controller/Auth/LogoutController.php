<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthInterface;
use App\Controller\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutController extends Controller
{
    public function __construct(
        private readonly AuthInterface $auth,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $this->auth->logout();

        return $response->withHeader('Location', '/login')->withStatus(302);
    }
}
