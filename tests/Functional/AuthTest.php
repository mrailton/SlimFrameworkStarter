<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Auth\SessionAuth;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\TestCase;

class AuthTest extends TestCase
{
    public function testShowLoginFormWhenGuest(): void
    {
        $request = $this->createRequest('GET', '/login');
        $response = $this->handleRequest($request);

        $this->assertSame(200, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Sign In', $body);
        $this->assertStringContainsString('action="/login"', $body);
        $this->assertStringContainsString('name="email"', $body);
        $this->assertStringContainsString('name="password"', $body);
    }

    public function testShowLoginFormRedirectsWhenAlreadyAuthenticated(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('Test User', 'test@example.com', 'password123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $request = $this->createRequest('GET', '/login');
        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaderLine('Location'));
    }

    public function testLoginPostRedirectsWhenAlreadyAuthenticated(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('Test User', 'test@example.com', 'password123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $request = $this->createFormRequest('POST', '/login', [
            'email' => 'test@example.com',
            'password' => 'password123',
        ]);
        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaderLine('Location'));
    }

    public function testLoginSuccess(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $request = $this->createFormRequest('POST', '/login', [
            'email' => 'john@example.com',
            'password' => 'securepass123',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/users', $response->getHeaderLine('Location'));
        $this->assertSame($user->getId(), $_SESSION[SessionAuth::SESSION_KEY] ?? null);
    }

    public function testLoginFailureInvalidPassword(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $request = $this->createFormRequest('POST', '/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpass',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Invalid email or password.', $body);
        $this->assertArrayNotHasKey(SessionAuth::SESSION_KEY, $_SESSION);
    }

    public function testLoginFailureNonExistentEmail(): void
    {
        $request = $this->createFormRequest('POST', '/login', [
            'email' => 'unknown@example.com',
            'password' => 'password123',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Invalid email or password.', $body);
    }

    public function testLoginValidationErrors(): void
    {
        $request = $this->createFormRequest('POST', '/login', [
            'email' => '',
            'password' => '',
        ]);

        $response = $this->handleRequest($request);

        $this->assertSame(422, $response->getStatusCode());
        $body = (string) $response->getBody();
        $this->assertStringContainsString('Email is required.', $body);
        $this->assertStringContainsString('Password is required.', $body);
    }

    public function testLogoutClearsSessionAndRedirects(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $this->authenticateAs($user);
        $this->assertSame($user->getId(), $_SESSION[SessionAuth::SESSION_KEY]);

        $request = $this->createRequest('POST', '/logout');
        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
        $this->assertArrayNotHasKey(SessionAuth::SESSION_KEY, $_SESSION);
    }

    public function testLogoutViaGetMethod(): void
    {
        /** @var UserRepository $userRepo */
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $request = $this->createRequest('GET', '/logout');
        $response = $this->handleRequest($request);

        $this->assertSame(302, $response->getStatusCode());
        $this->assertSame('/login', $response->getHeaderLine('Location'));
        $this->assertArrayNotHasKey(SessionAuth::SESSION_KEY, $_SESSION);
    }
}
