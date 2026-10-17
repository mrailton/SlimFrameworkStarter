<?php

declare(strict_types=1);

namespace Tests\Functional;

use App\Auth\AuthManager;
use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AuthTest extends TestCase
{
    #[Test]
    public function loginPageRenders(): void
    {
        $res = $this->get('/login');
        $body = (string) $res->getBody();

        $this->assertStringContainsString('Sign In', $body);
        $this->assertSame(200, $res->getStatusCode());
    }

    #[Test]
    public function loginPageRedirectsWhenAlreadyAuthenticated(): void
    {
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $res = $this->get('/login');

        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/', $res->getHeaderLine('Location'));
    }

    #[Test]
    public function loginWorks(): void
    {
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $res = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'securepass123',
        ]);

        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/', $res->getHeaderLine('Location'));
        $this->assertSame($user->getId(), $_SESSION[AuthManager::SESSION_KEY] ?? null);
    }

    #[Test]
    public function storeLoginRedirectsWhenAlreadyAuthenticated(): void
    {
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $res = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'securepass123',
        ]);

        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/', $res->getHeaderLine('Location'));
    }

    #[Test]
    public function loginFailsWithInvalidPassword(): void
    {
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $res = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $body = (string) $res->getBody();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('Invalid email or password.', $body);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function loginFailsWithNonExistentEmail(): void
    {
        $res = $this->post('/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password123',
        ]);

        $body = (string) $res->getBody();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('Invalid email or password.', $body);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function loginFailsWithEmptyEmail(): void
    {
        $res = $this->post('/login', [
            'email' => '',
            'password' => 'password123',
        ]);

        $body = (string) $res->getBody();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('Email is required.', $body);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function loginFailsWithEmptyPassword(): void
    {
        $res = $this->post('/login', [
            'email' => 'john@example.com',
            'password' => '',
        ]);

        $body = (string) $res->getBody();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('Password is required.', $body);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function loginFailsWithEmptyEmailAndPassword(): void
    {
        $res = $this->post('/login', [
            'email' => '',
            'password' => '',
        ]);

        $body = (string) $res->getBody();

        $this->assertSame(422, $res->getStatusCode());
        $this->assertStringContainsString('Email is required.', $body);
        $this->assertStringContainsString('Password is required.', $body);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function logoutClearsSessionAndRedirects(): void
    {
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('John Doe', 'john@example.com', 'securepass123');
        $userRepo->save($user);

        $this->authenticateAs($user);
        $this->assertSame($user->getId(), $_SESSION[AuthManager::SESSION_KEY]);

        $res = $this->get('/logout');

        $this->assertSame(302, $res->getStatusCode());
        $this->assertSame('/login', $res->getHeaderLine('Location'));
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function layoutRendersLoginButtonForGuests(): void
    {
        $res = $this->get('/');
        $body = (string) $res->getBody();

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('/login', $body);
        $this->assertStringNotContainsString('Signed in as', $body);
        $this->assertStringNotContainsString('/logout', $body);
    }

    #[Test]
    public function layoutRendersUserNameAndLogoutLinkForAuthenticatedUsers(): void
    {
        $userRepo = $this->container->get(UserRepository::class);
        $user = new User('Jane Smith', 'jane@example.com', 'securepass123');
        $userRepo->save($user);

        $this->authenticateAs($user);

        $res = $this->get('/');
        $body = (string) $res->getBody();

        $this->assertSame(200, $res->getStatusCode());
        $this->assertStringContainsString('Signed in as', $body);
        $this->assertStringContainsString('Jane Smith', $body);
        $this->assertStringContainsString('/logout', $body);
    }
}
