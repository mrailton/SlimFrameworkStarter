<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class CreateUserAction
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Twig $twig
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
        $isJson = str_contains($accept, 'application/json');

        if (!empty($errors)) {
            if ($isJson) {
                $response->getBody()->write((string)json_encode(['errors' => $errors], JSON_THROW_ON_ERROR));
                return $response->withStatus(422)->withHeader('Content-Type', 'application/json');
            }

            return $this->twig->render($response->withStatus(422), 'users/index.html.twig', [
                'page_title' => 'User Management',
                'users' => $this->userRepository->findAll(),
                'errors' => $errors,
                'form' => ['name' => $name, 'email' => $email],
            ]);
        }

        $user = new User(name: $name, email: $email);
        $this->userRepository->save($user);

        if ($isJson) {
            $response->getBody()->write((string)json_encode($user, JSON_THROW_ON_ERROR));
            return $response->withStatus(201)->withHeader('Content-Type', 'application/json');
        }

        return $response->withHeader('Location', '/users')->withStatus(302);
    }
}
