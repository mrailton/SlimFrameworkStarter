<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;

class BaseController
{
    public function __construct(protected Twig $twig) {}

    protected function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        return $this->twig->render($response, $template . '.html.twig', $data);
    }

    protected function redirect(ResponseInterface $response, string $uri): ResponseInterface
    {
        return $response->withHeader('Location', $uri)->withStatus(302);
    }
}
