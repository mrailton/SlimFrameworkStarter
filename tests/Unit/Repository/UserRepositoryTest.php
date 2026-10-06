<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->container->get(UserRepository::class);
    }

    public function testSaveAndFindUser(): void
    {
        $user = new User('Test User', 'test@example.com');
        $this->repository->save($user);

        $this->assertNotNull($user->getId());

        $found = $this->repository->findById($user->getId());
        $this->assertNotNull($found);
        $this->assertSame('Test User', $found->getName());

        $byEmail = $this->repository->findByEmail('test@example.com');
        $this->assertNotNull($byEmail);
        $this->assertSame($user->getId(), $byEmail->getId());
    }

    public function testFindAll(): void
    {
        $this->repository->save(new User('User One', 'one@example.com'));
        $this->repository->save(new User('User Two', 'two@example.com'));

        $users = $this->repository->findAll();
        $this->assertCount(2, $users);
    }

    public function testDeleteUser(): void
    {
        $user = new User('Delete Me', 'delete@example.com');
        $this->repository->save($user);

        $id = $user->getId();
        $this->assertNotNull($id);

        $this->repository->delete($user);

        $this->assertNull($this->repository->findById($id));
    }

    public function testTimestampsOnPersistAndUpdate(): void
    {
        $user = new User('Timestamp Test', 'timestamp@example.com');
        $this->repository->save($user);

        $this->assertNotNull($user->getId());
        $this->assertNotNull($user->getCreatedAt());
        $initialCreatedAt = $user->getCreatedAt();
        $this->assertNull($user->getUpdatedAt());

        $user->setName('Timestamp Test Updated');
        $this->repository->save($user);

        $this->assertNotNull($user->getUpdatedAt());
        $this->assertSame($initialCreatedAt, $user->getCreatedAt());

        $reloaded = $this->repository->findById($user->getId());
        $this->assertNotNull($reloaded);
        $this->assertSame('Timestamp Test Updated', $reloaded->getName());
        $this->assertNotNull($reloaded->getUpdatedAt());
    }
}
