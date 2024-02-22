<?php

declare(strict_types=1);

namespace App\Application\Factory;

use App\Domain\Entity\User\User;

final class UserFactory
{
    public static function createFromRequest(array $userData): User
    {
        return new User(
            $userData["username"],
            $userData["firstName"],
            $userData["lastName"],
        );
    }

    public static function updateFromRequest(User $user, array $userData): User
    {
        $user->setUsername($userData["username"]);
        $user->setFirstName($userData["firstName"]);
        $user->setLastName($userData["lastName"]);

        return $user;
    }
}
