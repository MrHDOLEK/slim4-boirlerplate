<?php

declare(strict_types=1);

namespace App\Domain\DomainException;

use Throwable;

class ValidationException extends DomainException
{
    private array $errors;

    public function __construct(
        $message,
        $errors = [],
        $code = 422,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
