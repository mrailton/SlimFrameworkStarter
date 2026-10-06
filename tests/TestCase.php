<?php

declare(strict_types=1);

namespace App\Tests;

use App\Auth\SessionAuth;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Psr7\Factory\UriFactory;

abstract class TestCase extends BaseTestCase
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

    /**
     * Authenticate test session as given user.
     */
    protected function authenticateAs(User $user): self
    {
        $_SESSION[SessionAuth::SESSION_KEY] = $user->getId();
        return $this;
    }

    /**
     * Boots the application and initializes the test database schema.
     *
     * @param array<string, mixed> $containerOverrides
     */
    protected function bootApp(array $containerOverrides = []): App
    {
        require_once __DIR__ . '/../bootstrap.php';

        $this->container = createContainer($containerOverrides);
        $this->app = createApp($this->container);

        $this->createDatabaseSchema();

        return $this->app;
    }

    /**
     * Creates all mapped tables in the database (e.g. SQLite in-memory).
     */
    protected function createDatabaseSchema(): void
    {
        if ($this->container instanceof ContainerInterface && $this->container->has(EntityManagerInterface::class)) {
            /** @var EntityManagerInterface $em */
            $em = $this->container->get(EntityManagerInterface::class);
            $schemaTool = new SchemaTool($em);
            $metadata = $em->getMetadataFactory()->getAllMetadata();

            $schemaTool->dropSchema($metadata);
            $schemaTool->createSchema($metadata);
        }
    }

    /**
     * Gets the EntityManager instance from the container.
     */
    protected function getEntityManager(): EntityManagerInterface
    {
        return $this->container->get(EntityManagerInterface::class);
    }

    /**
     * Creates a PSR-7 ServerRequest for testing.
     *
     * @param array<string, string> $headers
     * @param array<string, mixed> $serverParams
     */
    protected function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        array $serverParams = [],
    ): ServerRequestInterface {
        $uriFactory = new UriFactory();
        $requestUri = $uriFactory->createUri($uri);

        $serverRequestFactory = new ServerRequestFactory();
        $request = $serverRequestFactory->createServerRequest($method, $requestUri, $serverParams);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $request;
    }

    /**
     * Creates a JSON request.
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function createJsonRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = [],
    ): ServerRequestInterface {
        $streamFactory = new StreamFactory();
        $stream = $streamFactory->createStream(json_encode($data, JSON_THROW_ON_ERROR));

        $headers['Content-Type'] = 'application/json';
        $headers['Accept'] = 'application/json';

        return $this->createRequest($method, $uri, $headers)
            ->withBody($stream)
            ->withParsedBody($data);
    }

    /**
     * Creates a form submission request (e.g., application/x-www-form-urlencoded).
     *
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    protected function createFormRequest(
        string $method,
        string $uri,
        array $data = [],
        array $headers = [],
    ): ServerRequestInterface {
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';

        return $this->createRequest($method, $uri, $headers)
            ->withParsedBody($data);
    }

    /**
     * Dispatches a request through the Slim application.
     */
    protected function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        return $this->app->handle($request);
    }
}
