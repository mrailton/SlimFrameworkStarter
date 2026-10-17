<?php

declare(strict_types=1);

namespace Tests;

use App\Auth\AuthManager;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;

class TestCase extends BaseTestCase
{
    protected ?App $app = null;
    protected ?ContainerInterface $container = null;

    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
        $this->bootApp();
    }

    protected function tearDown(): void
    {
        $this->app = null;
        $this->container = null;
        $_SESSION = [];
        parent::tearDown();
    }

    protected function bootApp(array $containerOverrides = []): App
    {
        require_once __DIR__ . '/../bootstrap.php';

        $this->container = createContainer($containerOverrides);
        $this->app = createApp($this->container);

        $this->createDatabaseSchema();

        return $this->app;
    }

    protected function createDatabaseSchema(): void
    {
        if ($this->container instanceof ContainerInterface && $this->container->has(EntityManagerInterface::class)) {
            $em = $this->container->get(EntityManagerInterface::class);
            $schemaTool = new SchemaTool($em);
            $metadata = $em->getMetadataFactory()->getAllMetadata();

            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }

    protected function authenticateAs(User $user): self
    {
        $_SESSION[AuthManager::SESSION_KEY] = $user->getId();
        return $this;
    }

    protected function createRequest(string $method, string $uri, array $headers = [], array $serverParams = []): ServerRequestInterface
    {
        $uriFactory = new UriFactory();
        $requestUri = $uriFactory->createUri($uri);

        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest($method, $requestUri, $serverParams);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }

    protected function get(string $uri): ResponseInterface
    {
        $request = $this->createRequest('GET', $uri);
        return $this->handleRequest($request);
    }

    protected function getCsrfToken(): array
    {
        /** @var Guard $guard */
        $guard = $this->container->get(Guard::class);
        $pair = $guard->generateToken();

        return [
            $guard->getTokenNameKey() => $pair[$guard->getTokenNameKey()],
            $guard->getTokenValueKey() => $pair[$guard->getTokenValueKey()],
        ];
    }

    protected function post(string $uri, array $data = [], bool $withCsrf = true): ResponseInterface
    {
        if ($withCsrf && $this->container instanceof ContainerInterface && $this->container->has(Guard::class)) {
            $data = array_merge($this->getCsrfToken(), $data);
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $request = $this->createRequest('POST', $uri, $headers)->withParsedBody($data);

        return $this->handleRequest($request);
    }
}
