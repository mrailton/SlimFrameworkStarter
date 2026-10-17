<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthManager;
use App\Controller\BaseController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Views\Twig;

class LoginController extends BaseController
{
    public function __construct(
        Twig $twig,
        private readonly AuthManager $auth,
    ) {
        parent::__construct($twig);
    }

    public function __invoke(Request $request, Response $response): Response
    {
        if ($this->auth->check()) {
            return $this->redirect($response, '/');
        }

        return $this->render($response, 'auth/login');
    }
}
