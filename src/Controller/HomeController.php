<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController extends Controller
{
    public function __invoke(Request $request, Response $response): Response
    {
        return $this->render($response, 'home.html.twig', [
            'page_title' => 'Slim Framework Starter',
            'features' => [
                'Slim Framework 4 for lightweight routing & PSR-7/15 architecture',
                'PHP-DI 7 for robust Dependency Injection & Autowiring',
                'Doctrine ORM 3 with modern PHP 8 Attributes',
                'Twig 3 templating engine',
                'Monolog PSR-3 logging',
                'Dotenv environment variable support',
                'Extremely testable design with PHPUnit and in-memory test database support',
                'Symfony Console CLI integration for migrations & tasks',
            ],
        ]);
    }
}
