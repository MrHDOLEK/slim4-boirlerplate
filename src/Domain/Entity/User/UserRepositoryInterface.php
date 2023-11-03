<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use App\Domain\Entity\User\Exception\UserNotFoundException;

interface UserRepositoryInterface
{
    /**
     * @throws UserNotFoundException
     */
    public function findAll(): UsersCollection;

    /**
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;

    public function save(User $user): void;
}
