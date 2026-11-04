<?php

declare(strict_types=1);

namespace Tests;

use App\AppFactory;
use App\Auth\AuthManager;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Exception;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Csrf\Guard;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\UriFactory;

class TestCase extends BaseTestCase
{
    /** @var App<ContainerInterface>|null */
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

    /**
     * @param array<string, mixed> $containerOverrides
     * @return App<ContainerInterface>
     * @throws Exception
     */
    protected function bootApp(array $containerOverrides = []): App
    {
        $this->container = AppFactory::createContainer($containerOverrides);
        $app = AppFactory::createApp($this->container);

        $this->app = $app;

        $this->createDatabaseSchema();

        return $app;
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
        return $this->getService(EntityManagerInterface::class);
    }

    protected function authenticateAs(User $user): self
    {
        $_SESSION[AuthManager::SESSION_KEY] = $user->getId();
        return $this;
    }

    /**
     * @param array<string, string|string[]> $headers
     * @param array<string, mixed> $serverParams
     */
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
        assert($this->app instanceof App);

        return $this->app->handle($request);
    }

    protected function get(string $uri): ResponseInterface
    {
        $request = $this->createRequest('GET', $uri);
        return $this->handleRequest($request);
    }

    /**
     * @return array<string, string>
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getCsrfToken(): array
    {
        $guard = $this->getService(Guard::class);
        $pair = $guard->generateToken();

        return [
            $guard->getTokenNameKey() => $pair[$guard->getTokenNameKey()],
            $guard->getTokenValueKey() => $pair[$guard->getTokenValueKey()],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function post(string $uri, array $data = [], bool $withCsrf = true): ResponseInterface
    {
        if ($withCsrf && $this->container instanceof ContainerInterface && $this->container->has(Guard::class)) {
            $data = array_merge($this->getCsrfToken(), $data);
        }

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $request = $this->createRequest('POST', $uri, $headers)->withParsedBody($data);

        return $this->handleRequest($request);
    }

    /**
     * Helper to retrieve typed services from container in tests.
     *
     * @template T of object
     * @param class-string<T> $id
     * @return T
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getService(string $id): object
    {
        assert($this->container instanceof ContainerInterface);
        /** @var T $service */
        $service = $this->container->get($id);
        return $service;
    }
}
