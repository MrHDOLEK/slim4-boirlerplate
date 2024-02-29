<?php

declare(strict_types=1);

namespace App\Application\DTO\Response;

use App\Domain\Entity\User\User;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(title: "UserResponseDto")]
class UserResponseDto implements JsonSerializable
{
    #[OA\Property(type: "string", example: "username")]
    private string $username;

    #[OA\Property(type: "string", example: "firstName")]
    private string $firstName;

    #[OA\Property(type: "string", example: "lastName")]
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
