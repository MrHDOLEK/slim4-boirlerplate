<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

use App\Domain\Entity\User\User;
use JsonSerializable;

class UserResponseDto implements JsonSerializable
{
    private string $username;
    private string $firstName;
    private string $lastName;

    public function __construct(User $user)
    {
        $this->username = $user->username();
        $this->firstName = $user->firstName();
        $this->lastName = $user->lastName();
    }

    public function jsonSerialize(): array
    {
        return [
            "username" => $this->username,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
        ];
    }
}
