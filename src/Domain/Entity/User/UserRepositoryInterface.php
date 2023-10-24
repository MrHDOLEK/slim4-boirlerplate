<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

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
}
