<?php

declare(strict_types=1);

namespace Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->container->get(UserRepository::class);
    }

    #[Test]
    public function savePersistsAndRetrievesUserById(): void
    {
        $user = new User('Test User', 'test@example.com', 'password123');
        $this->repository->save($user);

        $this->assertNotNull($user->getId());

        $found = $this->repository->findById($user->getId());

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame('Test User', $found->getName());
        $this->assertSame('test@example.com', $found->getEmail());
        $this->assertTrue($found->verifyPassword('password123'));
    }

    #[Test]
    public function findByEmailReturnsUserWhenFound(): void
    {
        $user = new User('Email User', 'unique@example.com', 'secret');
        $this->repository->save($user);

        $found = $this->repository->findByEmail('unique@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame($user->getId(), $found->getId());
        $this->assertSame('Email User', $found->getName());
    }

    #[Test]
    public function findByEmailReturnsNullWhenNotFound(): void
    {
        $found = $this->repository->findByEmail('doesnotexist@example.com');

        $this->assertNull($found);
    }

    #[Test]
    public function findByIdReturnsNullWhenNotFound(): void
    {
        $found = $this->repository->findById(9999);

        $this->assertNull($found);
    }

    #[Test]
    public function saveWithoutFlushDoesNotImmediatelyPersist(): void
    {
        $user = new User('No Flush User', 'noflush@example.com', 'secret');
        $this->repository->save($user, flush: false);

        // Before flush, user id has not been assigned by sqlite
        $this->assertNull($user->getId());

        $this->getEntityManager()->flush();

        $this->assertNotNull($user->getId());
        $found = $this->repository->findById($user->getId());
        $this->assertNotNull($found);
    }
}
