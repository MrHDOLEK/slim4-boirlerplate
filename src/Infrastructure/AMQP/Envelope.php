<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP;

interface Envelope
{
    public function jsonSerialize(): array;
}
