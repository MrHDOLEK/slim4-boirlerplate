<?php

declare(strict_types=1);

use App\Domain\Entity\StoragePersisterInterface;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\DoctrineStoragePersister;
use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;

return [
    StoragePersisterInterface::class => DI\get(DoctrineStoragePersister::class),
    UserRepositoryInterface::class => DI\get(UserRepository::class),
];
