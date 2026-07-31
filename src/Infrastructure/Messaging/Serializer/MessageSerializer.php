<?php

declare(strict_types=1);

namespace App\Infrastructure\Messaging\Serializer;

use App\Infrastructure\Messaging\Envelope;

interface MessageSerializer
{
    public function encode(Envelope $envelope): string;

    /**
     * @param array<string, string> $headers
     */
    public function decode(string $body, array $headers): Envelope;
}
