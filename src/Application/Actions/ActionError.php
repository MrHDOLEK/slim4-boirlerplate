<?php

declare(strict_types=1);

namespace App\Application\Actions;

use JsonSerializable;

class ActionError implements JsonSerializable
{
    public const BAD_REQUEST = "BAD_REQUEST";
    public const INSUFFICIENT_PRIVILEGES = "INSUFFICIENT_PRIVILEGES";
    public const NOT_ALLOWED = "NOT_ALLOWED";
    public const NOT_IMPLEMENTED = "NOT_IMPLEMENTED";
    public const RESOURCE_NOT_FOUND = "RESOURCE_NOT_FOUND";
    public const SERVER_ERROR = "SERVER_ERROR";
    public const UNAUTHENTICATED = "UNAUTHENTICATED";
    public const VALIDATION_ERROR = "VALIDATION_ERROR";
    public const VERIFICATION_ERROR = "VERIFICATION_ERROR";
    public const UNPROCESSABLE_ENTITY = "UNPROCESSABLE ENTITY";

    public function __construct(
        private string $description,
        private ?array $errors = null,
    ) {}

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getErrors(): ?array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;

        return $this;
    }

    public function jsonSerialize(): array
    {
        return [
            "description" => $this->description,
            "errors" => $this->errors,
        ];
    }
}
