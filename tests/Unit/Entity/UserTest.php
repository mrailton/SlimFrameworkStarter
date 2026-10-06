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
        $user = new User(name: 'John Doe', email: 'john@example.com', createdAt: $now);

        $this->assertNull($user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertSame('john@example.com', $user->getEmail());
        $this->assertSame($now, $user->getCreatedAt());

        $user->setName('Jane Doe');
        $user->setEmail('jane@example.com');

        $this->assertSame('Jane Doe', $user->getName());
        $this->assertSame('jane@example.com', $user->getEmail());
    }

    public function testJsonSerialization(): void
    {
        $now = new DateTimeImmutable('2026-01-01 12:00:00');
        $user = new User(name: 'John Doe', email: 'john@example.com', createdAt: $now);

        $serialized = $user->jsonSerialize();
        $this->assertSame('John Doe', $serialized['name']);
        $this->assertSame('john@example.com', $serialized['email']);
        $this->assertSame($now->format(DATE_ATOM), $serialized['createdAt']);
    }
}
