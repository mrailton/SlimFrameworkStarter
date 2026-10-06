<?php

declare(strict_types=1);

namespace App\Tests\Unit\Auth;

use App\Auth\SessionAuth;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\TestCase;

class SessionAuthTest extends TestCase
{
    private SessionAuth $auth;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRepository = $this->container->get(UserRepository::class);
        $this->auth = $this->container->get(SessionAuth::class);
    }

    public function testAttemptSuccess(): void
    {
        $user = new User('Auth User', 'auth@example.com', 'password123');
        $this->userRepository->save($user);

        $this->assertTrue($this->auth->attempt('auth@example.com', 'password123'));
        $this->assertTrue($this->auth->check());
        $this->assertSame($user->getId(), $this->auth->id());
        $this->assertSame('auth@example.com', $this->auth->user()?->getEmail());
    }

    public function testAttemptFailure(): void
    {
        $user = new User('Auth User', 'auth@example.com', 'password123');
        $this->userRepository->save($user);

        $this->assertFalse($this->auth->attempt('auth@example.com', 'wrongpassword'));
        $this->assertFalse($this->auth->attempt('notfound@example.com', 'password123'));
        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->user());
    }

    public function testLoginAndLogout(): void
    {
        $user = new User('Direct Login', 'direct@example.com', 'pass');
        $this->userRepository->save($user);

        $this->auth->login($user);
        $this->assertTrue($this->auth->check());
        $this->assertSame($user->getId(), $this->auth->id());

        $this->auth->logout();
        $this->assertFalse($this->auth->check());
        $this->assertNull($this->auth->user());
    }
}
