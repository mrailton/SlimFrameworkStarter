<?php

declare(strict_types=1);

namespace App\Action\User;

use App\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Views\Twig;

class ListUsersAction
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly Twig $twig
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $users = $this->userRepository->findAll();

        // If client requests JSON
        $accept = $request->getHeaderLine('Accept');
        if (str_contains($accept, 'application/json')) {
            $response->getBody()->write((string)json_encode($users, JSON_THROW_ON_ERROR));
            return $response->withHeader('Content-Type', 'application/json');
        }

        return $this->twig->render($response, 'users/index.html.twig', [
            'page_title' => 'User Management',
            'users' => $users,
        ]);
    }
}
