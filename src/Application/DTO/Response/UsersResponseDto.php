<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

use App\Domain\Entity\User\User;
use App\Domain\Entity\User\UsersCollection;
use JsonSerializable;

class UsersResponseDto implements JsonSerializable
{
    private array $users;

    public function __construct(UsersCollection $usersCollection)
    {
        $this->users = $usersCollection->items();
    }

    public function jsonSerialize(): array
    {
        return array_map(
            fn(User $user) => new UserResponseDto($user),
            $this->users,
        );
    }
}
