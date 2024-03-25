<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use JsonSerializable;
use OpenApi\Attributes as OA;

#[OA\Schema(title: "User", required: ["username", "firstName", "lastName"])]
class User implements JsonSerializable
{
    private int $id;

    #[OA\Property(type: "string", example: "Janusz123")]
    private string $username;

    #[OA\Property(type: "string", example: "Janusz")]
    private string $firstName;

    #[OA\Property(type: "string", example: "Borowy")]
    private string $lastName;

    public function __construct(
        string $username,
        string $firstName,
        string $lastName,
    ) {
        $this->username = strtolower($username);
        $this->firstName = ucfirst($firstName);
        $this->lastName = ucfirst($lastName);
    }

    public function id(): ?int
    {
        return $this->id;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function setUsername(string $username): string
    {
        return $this->username = $username;
    }

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): string
    {
        return $this->firstName = $firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): string
    {
        return $this->lastName = $lastName;
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize(): array
    {
        return [
            "username" => $this->username,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
        ];
    }
}
