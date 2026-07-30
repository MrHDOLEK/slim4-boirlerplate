<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging;

interface Envelope
{
    public function jsonSerialize(): array;
}
