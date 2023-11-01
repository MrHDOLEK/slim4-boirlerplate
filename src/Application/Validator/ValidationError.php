<?php

declare(strict_types=1);

namespace App\Application\Validator;

use JsonSerializable;

final class ValidationError implements JsonSerializable
{
    public function __construct(
        private string $field,
        private array $errors,
    ) {}

    public function jsonSerialize(): array
    {
        return [
            $this->field => implode(", ", $this->errors),
        ];
    }
}
