<?php

declare(strict_types=1);

namespace Tests\Unit\Auth;

use App\Auth\AuthManager;
use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class AuthManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        parent::tearDown();
    }

    #[Test]
    public function checkReturnsFalseWhenNoUserLoggedIn(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->never())->method('findById');

        $auth = new AuthManager($userRepo);

        $this->assertFalse($auth->check());
        $this->assertNull($auth->user());
        $this->assertNull($auth->id());
    }

    #[Test]
    public function checkReturnsTrueWhenUserIsLoggedIn(): void
    {
        $user = new User('John Doe', 'john@example.com', 'password123');
        $this->setId($user, 42);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findById')
            ->with(42)
            ->willReturn($user);

        $_SESSION[AuthManager::SESSION_KEY] = 42;

        $auth = new AuthManager($userRepo);

        $this->assertTrue($auth->check());
        $this->assertSame(42, $auth->id());
        $this->assertSame($user, $auth->user());

        // Subsequent check/user calls should use cached user without querying repository again
        $this->assertTrue($auth->check());
        $this->assertSame($user, $auth->user());
    }

    #[Test]
    public function checkReturnsFalseWhenSessionUserDoesNotExistInRepository(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findById')
            ->with(999)
            ->willReturn(null);

        $_SESSION[AuthManager::SESSION_KEY] = 999;

        $auth = new AuthManager($userRepo);

        $this->assertFalse($auth->check());
        $this->assertNull($auth->user());
        $this->assertSame(999, $auth->id());
    }

    #[Test]
    public function idReturnsNullForNonNumericSessionValue(): void
    {
        $_SESSION[AuthManager::SESSION_KEY] = 'not-a-number';

        $userRepo = $this->createStub(UserRepository::class);
        $auth = new AuthManager($userRepo);

        $this->assertNull($auth->id());
        $this->assertNull($auth->user());
        $this->assertFalse($auth->check());
    }

    #[Test]
    public function attemptReturnsFalseWhenUserNotFound(): void
    {
        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findByEmail')
            ->with('unknown@example.com')
            ->willReturn(null);

        $auth = new AuthManager($userRepo);

        $result = $auth->attempt('unknown@example.com', 'password123');

        $this->assertFalse($result);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
    }

    #[Test]
    public function attemptReturnsFalseWhenPasswordIsIncorrect(): void
    {
        $user = new User('John Doe', 'john@example.com', 'correct_password');
        $this->setId($user, 1);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findByEmail')
            ->with('john@example.com')
            ->willReturn($user);

        $auth = new AuthManager($userRepo);

        $result = $auth->attempt('john@example.com', 'wrong_password');

        $this->assertFalse($result);
        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
        $this->assertFalse($auth->check());
    }

    #[Test]
    public function attemptLogsInAndReturnsTrueWhenCredentialsMatch(): void
    {
        $user = new User('John Doe', 'john@example.com', 'correct_password');
        $this->setId($user, 10);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->once())
            ->method('findByEmail')
            ->with('john@example.com')
            ->willReturn($user);

        $auth = new AuthManager($userRepo);

        $result = $auth->attempt('john@example.com', 'correct_password');

        $this->assertTrue($result);
        $this->assertSame(10, $_SESSION[AuthManager::SESSION_KEY]);
        $this->assertTrue($auth->check());
        $this->assertSame($user, $auth->user());
    }

    #[Test]
    public function loginCachesUserAndSetsSession(): void
    {
        $user = new User('Jane Doe', 'jane@example.com', 'pass');
        $this->setId($user, 5);

        $userRepo = $this->createMock(UserRepository::class);
        $userRepo->expects($this->never())->method('findById');

        $auth = new AuthManager($userRepo);
        $auth->login($user);

        $this->assertSame(5, $_SESSION[AuthManager::SESSION_KEY]);
        $this->assertSame($user, $auth->user());
        $this->assertTrue($auth->check());
    }

    #[Test]
    public function logoutClearsSessionAndCachedUser(): void
    {
        $user = new User('Jane Doe', 'jane@example.com', 'pass');
        $this->setId($user, 5);

        $userRepo = $this->createStub(UserRepository::class);

        $auth = new AuthManager($userRepo);
        $auth->login($user);

        $this->assertTrue($auth->check());

        $auth->logout();

        $this->assertArrayNotHasKey(AuthManager::SESSION_KEY, $_SESSION);
        $this->assertNull($auth->user());
        $this->assertFalse($auth->check());
        $this->assertNull($auth->id());
    }

    private function setId(User $user, int $id): void
    {
        $reflection = new ReflectionProperty(User::class, 'id');
        $reflection->setValue($user, $id);
    }
}
