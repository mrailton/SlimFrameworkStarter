<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserCreationAndGetters(): void
    {
        $now = new DateTimeImmutable('2026-01-01 12:00:00');
        $updated = new DateTimeImmutable('2026-01-01 13:00:00');
        $user = new User(
            name: 'John Doe',
            email: 'john@example.com',
            password: 'mysecretpassword',
            createdAt: $now,
            updatedAt: $updated,
        );

        $this->assertNull($user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertNotEmpty($user->getPassword());
        $this->assertNotSame('mysecretpassword', $user->getPassword());
        $this->assertSame($now, $user->getCreatedAt());
        $this->assertSame($updated, $user->getUpdatedAt());
        $this->assertTrue($user->verifyPassword('mysecretpassword'));
        $this->assertFalse($user->verifyPassword('wrongpassword'));

        $this->assertSame($user, $user->setName('Jane Doe'));
        $this->assertSame($user, $user->setEmail('jane@example.com'));
        $this->assertSame($user, $user->setPassword('newpassword123'));

        $newCreatedAt = new DateTimeImmutable('2026-01-02 10:00:00');
        $newUpdatedAt = new DateTimeImmutable('2026-01-02 11:00:00');
        $this->assertSame($user, $user->setCreatedAt($newCreatedAt));
        $this->assertSame($user, $user->setUpdatedAt($newUpdatedAt));

        $this->assertSame('Jane Doe', $user->getName());
        $this->assertSame('jane@example.com', $user->getEmail());
        $this->assertSame($newCreatedAt, $user->getCreatedAt());
        $this->assertSame($newUpdatedAt, $user->getUpdatedAt());
        $this->assertTrue($user->verifyPassword('newpassword123'));
        $this->assertFalse($user->verifyPassword('mysecretpassword'));
    }

    public function testPreHashedPassword(): void
    {
        $hash = password_hash('existing-hash-password', PASSWORD_DEFAULT);
        $user = new User('Prehashed', 'prehashed@example.com', $hash);

        $this->assertSame($hash, $user->getPassword());
        $this->assertTrue($user->verifyPassword('existing-hash-password'));

        $newHash = password_hash('another-secret', PASSWORD_DEFAULT);
        $user->setPassword($newHash);
        $this->assertSame($newHash, $user->getPassword());
        $this->assertTrue($user->verifyPassword('another-secret'));
    }

    public function testEmptyPassword(): void
    {
        $user = new User('No Password', 'nopassword@example.com');

        $this->assertSame('', $user->getPassword());
        $this->assertFalse($user->verifyPassword('anypassword'));
        $this->assertFalse($user->verifyPassword(''));

        $user->setPassword('somepassword');
        $this->assertNotEmpty($user->getPassword());

        $user->setPassword('');
        $this->assertSame('', $user->getPassword());
        $this->assertFalse($user->verifyPassword('somepassword'));
    }

    public function testTimestampLifecycleCallbacks(): void
    {
        $user = new User('Alice', 'alice@example.com');
        $initialCreatedAt = $user->getCreatedAt();
        $this->assertNull($user->getUpdatedAt());

        $user->onPrePersist();
        $this->assertSame($initialCreatedAt, $user->getCreatedAt());

        $user->onPreUpdate();
        $this->assertNotNull($user->getUpdatedAt());
    }
}
