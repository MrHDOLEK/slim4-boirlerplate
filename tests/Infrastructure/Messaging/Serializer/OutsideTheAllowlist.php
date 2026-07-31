<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Messaging\Serializer;

use App\Infrastructure\Messaging\Envelope;

class OutsideTheAllowlist implements Envelope
{
    public function jsonSerialize(): array
    {
        return ["eventName" => "OutsideTheAllowlist", "payload" => []];
    }
}
