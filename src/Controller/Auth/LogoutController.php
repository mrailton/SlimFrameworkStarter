<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthManager;
use App\Controller\BaseController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LogoutController extends BaseController
{
    public function __construct(private readonly AuthManager $auth) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $this->auth->logout();

        return $this->redirect($response, '/login');
    }
}
