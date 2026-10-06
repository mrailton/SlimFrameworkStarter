<?php

declare(strict_types=1);

namespace App\Controller;

use DI\Attribute\Inject;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Views\Twig;

abstract class Controller
{
    #[Inject]
    protected Twig $twig;

    protected function render(Response $response, string $template, array $data = []): Response
    {
        return $this->twig->render($response, $template, $data);
    }
}
