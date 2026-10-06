<?php

declare(strict_types=1);

namespace App\Controller\Auth;

use App\Auth\AuthInterface;
use App\Controller\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class LoginController extends Controller
{
    public function __construct(
        private readonly AuthInterface $auth,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        if ($this->auth->check()) {
            return $response->withHeader('Location', '/users')->withStatus(302);
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

        if ($errors === []) {
            if ($this->auth->attempt($email, $password)) {
                return $response->withHeader('Location', '/users')->withStatus(302);
            }

            $errors['auth'] = 'Invalid email or password.';
        }

        return $this->render($response->withStatus(422), 'auth/login.html.twig', [
            'page_title' => 'Sign In',
            'errors' => $errors,
            'form' => ['email' => $email],
        ]);
    }
}
