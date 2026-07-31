<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Serializer;

use App\Infrastructure\Kafka\Exception\SchemaRegistryFailure;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Exception\MessageSerializationFailure;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Throwable;

final readonly class AvroMessageSerializer implements MessageSerializer
{
    private const EVENT_TYPE_HEADER = "event-type";

    public function __construct(
        private AvroSerializer $serializer,
        private DenormalizerInterface $denormalizer,
        private ?string $schemaSubject = null,
    ) {}

    public function forSubject(string $schemaSubject): self
    {
        return new self($this->serializer, $this->denormalizer, $schemaSubject);
    }

    public function encode(Envelope $envelope): string
    {
        return $this->encodeRecord($envelope)->payload;
    }

    public function encodeRecord(Envelope $envelope): EncodedRecord
    {
        if ($this->schemaSubject === null) {
            throw SchemaRegistryFailure::subjectNotBound($envelope::class);
        }

        return $this->serializer->encode($this->schemaSubject, $envelope->jsonSerialize());
    }

    public function decode(string $body, array $headers): Envelope
    {
        $eventType = $headers[self::EVENT_TYPE_HEADER] ?? null;

        if (!is_subclass_of($eventType, Envelope::class)) {
            throw MessageSerializationFailure::unknownEventType($eventType);
        }

        $record = $this->serializer->decode($body);
        $payload = is_array($record["payload"] ?? null) ? $record["payload"] : $record;

        try {
            return $this->denormalizer->denormalize($payload, $eventType);
        } catch (Throwable $exception) {
            throw MessageSerializationFailure::couldNotHydrate($eventType, $exception);
        }
    }
}
