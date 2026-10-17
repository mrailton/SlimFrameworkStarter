<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Controller\BaseController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class LoginController extends BaseController
{
    public function __invoke(Request $request, Response $response): Response
    {
        return $this->render($response, 'auth/login');
    }
}
