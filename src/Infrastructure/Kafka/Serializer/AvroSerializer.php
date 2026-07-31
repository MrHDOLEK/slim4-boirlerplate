<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Serializer;

interface AvroSerializer
{
    public function encode(string $subject, array $payload): EncodedRecord;

    public function decode(string $payload): array;

    public function schemaIdOf(string $payload): int;
}
