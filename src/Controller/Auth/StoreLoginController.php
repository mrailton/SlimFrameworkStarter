<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthManager;
use App\Controller\BaseController;
use Slim\Psr7\Request;
use Slim\Psr7\Response;

class StoreLoginController extends BaseController
{
    public function __construct(private readonly AuthManager $auth) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = (array) $request->getParsedBody() ?? [];
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

        $errors['email'] = 'Invalid email or password.';

        return $this->render($response->withStatus(422), 'auth/login', [
            'page_title' => 'Sign In',
            'errors' => $errors,
            'form' => ['email' => $email],
        ]);
    }
}
