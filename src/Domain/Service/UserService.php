<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UserNotFoundException;
use App\Domain\Entity\User\UserRepositoryInterface;
use App\Domain\Entity\User\UsersCollection;

final readonly class UserService
{
    public function __construct(
        private UserRepositoryInterface $userRepository,
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
        return $this->userRepository->findAll();
    }
}
