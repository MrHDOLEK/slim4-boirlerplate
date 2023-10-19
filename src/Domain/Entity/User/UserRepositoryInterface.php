<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

interface UserRepositoryInterface
{
    /**
     * @return array<User>
     */
    public function findAll(): array;

    /**
     * @throws UserNotFoundException
     */
    public function findUserOfId(int $id): User;
}
