<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Doctrine;

use App\Infrastructure\Persistence\Doctrine\DoctrineStoragePersister;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class DoctrineStoragePersisterTest extends TestCase
{
    public function testStoreChangesCallsFlushOnEntityManagerSuccess(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects($this->once())
            ->method("flush");

        $persister = new DoctrineStoragePersister($entityManager);
        $persister->storeChanges();
    }
}
