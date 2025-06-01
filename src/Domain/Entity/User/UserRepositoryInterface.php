<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use App\Domain\Entity\User\Exception\UserNotFoundException;

interface UserRepositoryInterface
{
    /**
     * @throws UserNotFoundException
     */
    public function getAll(): UsersCollection;

    /**
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;

    public function add(User $user): void;

    public function remove(User $user): void;
}
