<?php

declare(strict_types=1);

namespace Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    #[Test]
    public function instantiatesWithDefaults(): void
    {
        $user = new User();

        $this->assertNull($user->getId());
        $this->assertSame('', $user->getName());
        $this->assertSame('', $user->getEmail());
        $this->assertSame('', $user->getPassword());
    }

    #[Test]
    public function instantiatesWithParameters(): void
    {
        $user = new User('John Doe', 'john@example.com', 'secret123');

        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertNotSame('secret123', $user->getPassword());
        $this->assertTrue($user->verifyPassword('secret123'));
    }

    #[Test]
    public function setNameAndEmail(): void
    {
        $user = new User();
        $user->setName('Alice');
        $user->setEmail('alice@example.com');

        $this->assertSame('Alice', $user->getName());
        $this->assertSame('alice@example.com', $user->getEmail());
    }

    #[Test]
    public function setPasswordHashesPassword(): void
    {
        $user = new User();
        $user->setPassword('myPassword');

        $this->assertNotSame('myPassword', $user->getPassword());
        $this->assertTrue(password_verify('myPassword', $user->getPassword()));
        $this->assertTrue($user->verifyPassword('myPassword'));
    }

    #[Test]
    public function setPasswordWithEmptyStringClearsPassword(): void
    {
        $user = new User('John', 'john@example.com', 'initial');
        $this->assertTrue($user->verifyPassword('initial'));

        $user->setPassword('');

        $this->assertSame('', $user->getPassword());
        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword('initial'));
    }

    #[Test]
    public function verifyPasswordFailsWithWrongPassword(): void
    {
        $user = new User('John', 'john@example.com', 'correct_password');

        $this->assertFalse($user->verifyPassword('wrong_password'));
    }

    #[Test]
    public function verifyPasswordFailsWithEmptyPasswordInput(): void
    {
        $user = new User('John', 'john@example.com', 'correct_password');

        $this->assertFalse($user->verifyPassword(''));
    }

    #[Test]
    public function verifyPasswordFailsWhenStoredPasswordIsEmpty(): void
    {
        $user = new User('John', 'john@example.com');

        $this->assertFalse($user->verifyPassword(''));
        $this->assertFalse($user->verifyPassword('any_password'));
    }

    #[Test]
    public function lifecycleCallbacksSetTimestamps(): void
    {
        $user = new User('John', 'john@example.com');

        $user->onPrePersist();
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());

        $user->onPreUpdate();
        $this->assertInstanceOf(DateTimeImmutable::class, $user->getUpdatedAt());
    }

    #[Test]
    public function manualTimestampSetters(): void
    {
        $user = new User();
        $now = new DateTimeImmutable('2026-01-01 10:00:00');
        $updated = new DateTimeImmutable('2026-01-01 11:00:00');

        $user->setCreatedAt($now);
        $user->setUpdatedAt($updated);

        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($updated, $user->getUpdatedAt());
    }
}
