<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthInterface;
use App\Controller\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ShowLoginController extends Controller
{
    public function __construct(
        private readonly AuthInterface $auth,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($this->auth->check()) {
            return $response->withHeader('Location', '/users')->withStatus(302);
        }

        return $this->render($response, 'auth/login.html.twig', [
            'page_title' => 'Sign In',
        ]);
    }
}
