<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthManager;
use App\Controller\BaseController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class LogoutController extends BaseController
{
    public function __construct(private readonly AuthManager $auth) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $this->auth->logout();

        return $this->redirect($response, '/login');
    }
}
