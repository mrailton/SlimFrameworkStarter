<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\Controller;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CreateUserController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $data = (array)($request->getParsedBody() ?? []);
        $name = trim((string)($data['name'] ?? ''));
        $email = trim((string)($data['email'] ?? ''));

        $errors = [];
        if (empty($name)) {
            $errors['name'] = 'Name is required.';
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'A valid email is required.';
        } elseif ($this->userRepository->findByEmail($email) !== null) {
            $errors['email'] = 'Email already exists.';
        }

        $accept = $request->getHeaderLine('Accept');

        if (!empty($errors)) {
            return $this->render($response->withStatus(422), 'users/index.html.twig', [
                'page_title' => 'User Management',
                'users' => $this->userRepository->findAll(),
                'errors' => $errors,
                'form' => ['name' => $name, 'email' => $email],
            ]);
        }

        $user = new User(name: $name, email: $email);
        $this->userRepository->save($user);

        return $response->withHeader('Location', '/users')->withStatus(302);
    }
}
