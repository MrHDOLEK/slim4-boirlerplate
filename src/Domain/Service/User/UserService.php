<?php

declare(strict_types=1);

namespace App\Domain\Service\User;

use App\Domain\Entity\StoragePersisterInterface;
use App\Domain\Entity\User\Exception\UserNotFoundException;
use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;

final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
        private UserEventsService $userEventsService,
        private StoragePersisterInterface $storagePersister,
    ) {}

    /**
     * @throws UserNotFoundException
     */
    public function getUserById(int $userId): User
    {
        return $this->userRepository->findUserOfId($userId);
    }

    /**
     * @throws UserNotFoundException
     */
    public function getAllUsers(): UsersCollection
    {
        return $this->userRepository->getAll();
    }

    public function createUser(User $user): void
    {
        $this->userRepository->add($user);
        $this->storagePersister->storeChanges();
        $this->userEventsService->userWasCreated($user);
    }

    public function updateUser(User $user): void
    {
        $this->userRepository->add($user);
        $this->storagePersister->storeChanges();
        $this->userEventsService->userWasUpdated($user);
    }
}
