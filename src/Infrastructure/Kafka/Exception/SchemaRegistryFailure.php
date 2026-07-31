<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Exception;

use RuntimeException;
use Throwable;

final class SchemaRegistryFailure extends RuntimeException
{
    public static function whileEncoding(string $subject, Throwable $previous): self
    {
        return new self(
            sprintf('Could not encode a record for subject "%s": %s', $subject, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function whileDecoding(int $schemaId, Throwable $previous): self
    {
        return new self(
            sprintf("Could not decode a record written with schema id %d: %s", $schemaId, $previous->getMessage()),
            0,
            $previous,
        );
    }

    public static function subjectNotBound(string $envelope): self
    {
        return new self(sprintf('No Avro schema subject is bound to this serializer, cannot encode "%s"', $envelope));
    }

    public static function subjectNotResolvable(string $subject): self
    {
        return new self(sprintf('Schema registry returned no usable schema for subject "%s"', $subject));
    }

    public static function notConfluentWireFormat(): self
    {
        return new self("Payload is not in Confluent wire format: expected a leading magic byte 0x00 followed by a 4-byte schema id");
    }

    public static function payloadTooShort(int $length): self
    {
        return new self(sprintf("Payload of %d bytes is too short to carry the 5-byte Confluent wire format header", $length));
    }

    public static function nonRecordPayload(string $type): self
    {
        return new self(sprintf("Decoded an Avro payload of type %s, expected a record", $type));
    }
}
