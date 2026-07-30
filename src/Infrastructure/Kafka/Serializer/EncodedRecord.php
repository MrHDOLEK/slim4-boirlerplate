<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Serializer;

final readonly class EncodedRecord
{
    public function __construct(
        public string $payload,
        public string $subject,
        public int $schemaId,
        public int $schemaVersion,
    ) {}
}
