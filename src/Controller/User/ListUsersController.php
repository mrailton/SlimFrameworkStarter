<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\Controller;
use App\Repository\UserRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class ListUsersController extends Controller
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function __invoke(Request $request, Response $response): Response
    {
        $users = $this->userRepository->findAll();

        return $this->render($response, 'users/index.html.twig', [
            'page_title' => 'User Management',
            'users' => $users,
        ]);
    }
}
