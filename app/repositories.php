<?php

declare(strict_types=1);

use App\Domain\User\UserRepositoryInterface;
use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;
use DI\ContainerBuilder;

return function (ContainerBuilder $containerBuilder): void {
    $containerBuilder->addDefinitions([
        UserRepositoryInterface::class => \DI\autowire(UserRepository::class),
    ]);
};
