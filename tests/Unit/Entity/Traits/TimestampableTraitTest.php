<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Traits;

use App\Entity\Traits\TimestampableTrait;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TimestampableTraitTest extends TestCase
{
    public function testOnPrePersistSetsCreatedAtIfNotSet(): void
    {
        $entity = $this->createTimestampableObject();

        $entity->onPrePersist();

        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
    }

    public function testOnPrePersistPreservesExistingCreatedAt(): void
    {
        $entity = $this->createTimestampableObject();
        $customDate = new DateTimeImmutable('2025-05-01 10:00:00');
        $entity->setCreatedAt($customDate);

        $entity->onPrePersist();

        $this->assertSame($customDate, $entity->getCreatedAt());
    }

    public function testOnPreUpdateSetsUpdatedAt(): void
    {
        $entity = $this->createTimestampableObject();
        $this->assertNull($entity->getUpdatedAt());

        $entity->onPreUpdate();

        $this->assertInstanceOf(DateTimeImmutable::class, $entity->getUpdatedAt());
    }

    public function testGettersAndSetters(): void
    {
        $entity = $this->createTimestampableObject();
        $created = new DateTimeImmutable('2026-01-01 00:00:00');
        $updated = new DateTimeImmutable('2026-01-02 00:00:00');

        $this->assertSame($entity, $entity->setCreatedAt($created));
        $this->assertSame($entity, $entity->setUpdatedAt($updated));

        $this->assertSame($created, $entity->getCreatedAt());
        $this->assertSame($updated, $entity->getUpdatedAt());

        $this->assertSame($entity, $entity->setUpdatedAt(null));
        $this->assertNull($entity->getUpdatedAt());
    }
    private function createTimestampableObject(): object
    {
        return new class {
            use TimestampableTrait;
        };
    }
}
