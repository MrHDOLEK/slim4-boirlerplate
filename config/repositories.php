<?php

declare(strict_types=1);

use App\Domain\Entity\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;
use DI\ContainerBuilder;

return [
    UserRepositoryInterface::class => DI\get(UserRepository::class),
];
