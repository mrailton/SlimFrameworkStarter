<?php

declare(strict_types=1);


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
        $this->assertEquals(200, $res->getStatusCode());
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

    public function testLogoutClearsSessionAndRedirects(): void
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
}
