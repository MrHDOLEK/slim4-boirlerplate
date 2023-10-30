<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject\String;

interface StringLiteral
{
    public function __construct(string $string);
}
