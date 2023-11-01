<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use Doctrine\ORM\Mapping\Entity;
use JsonSerializable;

/**
 * @OA\Schema(
 *   title="User",
 *   required={"username","firstName","lastName"}
 * )
 */
#[Entity]
class User implements JsonSerializable
{
    /** @phpstan-ignore-next-line */
    private int $id;

    /** @OA\Property(type="string", example="Janusz123") */
    private string $username;

    /** @OA\Property(type="string", example="Janusz") */
    private string $firstName;

    /** @OA\Property(type="string", example="Borowy") */
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

    public function firstName(): string
    {
        return $this->firstName;
    }

    public function lastName(): string
    {
        return $this->lastName;
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
