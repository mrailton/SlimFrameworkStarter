<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\TestCase;

class UserActionTest extends TestCase
{
    public function testUnauthenticatedUserRedirectedToLogin(): void
    {
        $request = $this->createRequest('GET', '/users');
        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
    }

    public function testUnauthenticatedJsonRequestReturnsUnauthorized(): void
    {
        $request = $this->createJsonRequest('GET', '/users');
        $response = $this->handleRequest($request);

        $this->assertSame(401, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Unauthorized', $body);
    }

    public function testListUsersHtmlWhenAuthenticated(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('Test User', 'test@example.com', 'password123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $request = $this->createRequest('GET', '/users');
        $response = $this->handleRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('User Management', $body);
        $this->assertStringContainsString('Test User', $body);
        $this->assertStringContainsString('test@example.com', $body);
    }

    public function testCreateUserViaFormWhenAuthenticated(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $admin = new User('Admin', 'admin@example.com', 'password123');
        $userRepo->save($admin);

        $this->authenticateAs($admin);

        $request = $this->createFormRequest('POST', '/users', [
            'name' => 'Alice Doe',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaderLine('Location'));

        $user = $userRepo->findByEmail('alice@example.com');
        $this->assertNotNull($user);
        $this->assertSame('Alice Doe', $user->getName());
        $this->assertTrue($user->verifyPassword('secret123'));
    }

    public function testCreateUserValidationError(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $admin = new User('Admin', 'admin@example.com', 'password123');
        $userRepo->save($admin);

        $this->authenticateAs($admin);

        $request = $this->createFormRequest('POST', '/users', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => '123',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Name is required.', $body);
        $this->assertStringContainsString('A valid email is required.', $body);
        $this->assertStringContainsString('Password must be at least 6 characters.', $body);
    }

    public function testCreateUserEmptyPasswordValidationError(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $admin = new User('Admin', 'admin@example.com', 'password123');
        $userRepo->save($admin);

        $this->authenticateAs($admin);

        $request = $this->createFormRequest('POST', '/users', [
            'name' => 'Alice',
            'email' => 'alice2@example.com',
            'password' => '',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Password is required.', $body);
    }

    public function testCreateUserDuplicateEmailError(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $admin = new User('Admin', 'admin@example.com', 'password123');
        $userRepo->save($admin);

        $userRepo->save(new User('Bob', 'bob@example.com', 'password123'));

        $this->authenticateAs($admin);

        $request = $this->createFormRequest('POST', '/users', [
            'name' => 'Bob Clone',
            'email' => 'bob@example.com',
            'password' => 'password123',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Email already exists.', $body);
    }
}
