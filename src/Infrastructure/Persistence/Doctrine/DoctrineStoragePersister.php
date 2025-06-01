<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Entity\StoragePersisterInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineStoragePersister implements StoragePersisterInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function storeChanges(): void
    {
        $this->entityManager->flush();
    }
}
