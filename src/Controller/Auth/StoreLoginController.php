<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthManager;
use App\Controller\BaseController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use Slim\Views\Twig;

class StoreLoginController extends BaseController
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

        $data = (array) ($request->getParsedBody() ?? []);
        $email = trim((string) ($data['email'] ?? ''));
        $password = (string) ($data['password'] ?? '');

        $errors = [];
        if (empty($email)) {
            $errors['email'] = 'Email is required.';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required.';
        }

        if ($errors !== []) {
            return $this->render($response->withStatus(422), 'auth/login', [
                'page_title' => 'Sign In',
                'errors' => $errors,
                'form' => ['email' => $email],
            ]);
        }

        if ($this->auth->attempt($email, $password)) {
            return $this->redirect($response, '/');
        }

        $errors['auth'] = 'Invalid email or password.';

        return $this->render($response->withStatus(422), 'auth/login', [
            'page_title' => 'Sign In',
            'errors' => $errors,
            'form' => ['email' => $email],
        ]);
    }
}
