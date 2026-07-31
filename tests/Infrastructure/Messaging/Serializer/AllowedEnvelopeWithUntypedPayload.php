<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging\Serializer;

use App\Infrastructure\Messaging\Envelope;

class AllowedEnvelopeWithUntypedPayload implements Envelope
{
    public function __construct(
        private mixed $payload,
    ) {}

    public function jsonSerialize(): array
    {
        return ["eventName" => "AllowedEnvelopeWithUntypedPayload", "payload" => $this->payload];
    }
}
