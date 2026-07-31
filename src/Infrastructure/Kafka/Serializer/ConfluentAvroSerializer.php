<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Serializer;

use App\Infrastructure\Kafka\Exception\SchemaRegistryFailure;
use AvroSchema;
use FlixTech\AvroSerializer\Objects\RecordSerializer;
use FlixTech\SchemaRegistryApi\Registry;
use Throwable;

final readonly class ConfluentAvroSerializer implements AvroSerializer
{
    private const WIRE_FORMAT_HEADER_BYTES = 5;

    public function __construct(
        private RecordSerializer $recordSerializer,
        private Registry $registry,
    ) {}

    public function encode(string $subject, array $payload): EncodedRecord
    {
        try {
            $schema = $this->latestSchema($subject);

            return new EncodedRecord(
                $this->recordSerializer->encodeRecord($subject, $schema, $payload),
                $subject,
                $this->registry->schemaId($subject, $schema),
                $this->registry->schemaVersion($subject, $schema),
            );
        } catch (Throwable $exception) {
            throw SchemaRegistryFailure::whileEncoding($subject, $exception);
        }
    }

    public function decode(string $payload): array
    {
        try {
            $decoded = $this->recordSerializer->decodeMessage($payload);
        } catch (Throwable $exception) {
            throw SchemaRegistryFailure::whileDecoding($this->schemaIdOf($payload), $exception);
        }

        if (!is_array($decoded)) {
            throw SchemaRegistryFailure::nonRecordPayload(get_debug_type($decoded));
        }

        return $decoded;
    }

    public function schemaIdOf(string $payload): int
    {
        if (strlen($payload) < self::WIRE_FORMAT_HEADER_BYTES) {
            throw SchemaRegistryFailure::payloadTooShort(strlen($payload));
        }

        $header = unpack("Cversion/NschemaId", substr($payload, 0, self::WIRE_FORMAT_HEADER_BYTES));

        if ($header === false || $header["version"] !== 0) {
            throw SchemaRegistryFailure::notConfluentWireFormat();
        }

        return $header["schemaId"];
    }

    private function latestSchema(string $subject): AvroSchema
    {
        $schema = $this->registry->latestVersion($subject);

        if (!$schema instanceof AvroSchema) {
            throw SchemaRegistryFailure::subjectNotResolvable($subject);
        }

        return $schema;
    }
}
