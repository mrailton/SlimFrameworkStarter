<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Http\Message\ResponseInterface;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class BaseController
{
    public function __construct(protected Twig $twig) {}

    /**
     * @param array<string, mixed> $data
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    protected function render(ResponseInterface $response, string $template, array $data = []): ResponseInterface
    {
        return $this->twig->render($response, $template . '.html.twig', $data);
    }

    protected function redirect(ResponseInterface $response, string $uri): ResponseInterface
    {
        return $response->withHeader('Location', $uri)->withStatus(302);
    }
}
