<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

class User
{
    /** @phpstan-ignore-next-line */
    private int $id;

    private string $username;
    private string $firstName;
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
}
