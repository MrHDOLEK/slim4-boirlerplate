<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic;

use App\Infrastructure\Attribute\AsKafkaTopic;
use App\Infrastructure\Kafka\Exception\ProducerFailure;
use App\Infrastructure\Kafka\Flushable;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Serializer\EncodedRecord;
use App\Infrastructure\Kafka\Worker\KafkaWorker;
use App\Infrastructure\Messaging\Envelope;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use ReflectionClass;
use RuntimeException;

abstract class KafkaTopic implements Flushable
{
    private const FLUSH_TIMEOUT_MS = 10_000;
    private const PRODUCE_CHUNK_SIZE = 500;

    private ?AsKafkaTopic $topicAttribute = null;
    private ?ProducerTopic $producerTopic = null;

    public function __construct(
        private readonly Producer $producer,
        private readonly AvroSerializer $serializer,
    ) {
        if ($attribute = (new ReflectionClass($this))->getAttributes(AsKafkaTopic::class)) {
            $this->topicAttribute = $attribute[0]->newInstance();
        }
    }

    abstract public function getWorker(): KafkaWorker;

    public function getName(): string
    {
        return $this->attribute()->getName();
    }

    public function getSchemaSubject(): string
    {
        return $this->attribute()->getSchemaSubject();
    }

    public function getNumberOfConsumers(): int
    {
        return $this->attribute()->getNumberOfWorkers();
    }

    public function produce(Envelope $envelope): void
    {
        $this->produceBatch([$envelope]);
    }

    /**
     * @param array<Envelope> $envelopes
     */
    public function produceBatch(array $envelopes): void
    {
        if ($envelopes === []) {
            return;
        }

        /** @phpstan-ignore-next-line */
        if (!empty(array_filter($envelopes, fn($envelope) => !$envelope instanceof Envelope))) {
            throw new RuntimeException(sprintf("All envelopes need to implement %s", Envelope::class));
        }

        foreach (array_chunk($envelopes, self::PRODUCE_CHUNK_SIZE) as $chunk) {
            foreach ($chunk as $envelope) {
                $this->publish($envelope);
            }

            $this->producer->poll(0);
        }
    }

    public function flush(): void
    {
        $result = $this->producer->flush(self::FLUSH_TIMEOUT_MS);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw ProducerFailure::flushFailed($this->getName(), $result);
        }
    }

    protected function partitionKeyFor(Envelope $envelope): ?string
    {
        return null;
    }

    private function publish(Envelope $envelope): void
    {
        $record = $this->serializer->encode($this->getSchemaSubject(), $envelope->jsonSerialize());

        $this->topic()->producev(
            partition: RD_KAFKA_PARTITION_UA,
            msgflags: RD_KAFKA_MSG_F_BLOCK,
            payload: $record->payload,
            key: $this->partitionKeyFor($envelope),
            headers: $this->headersFor($envelope, $record),
        );
    }

    /**
     * @return array<string, string>
     */
    private function headersFor(Envelope $envelope, EncodedRecord $record): array
    {
        return [
            "schema-subject" => $record->subject,
            "schema-id" => (string)$record->schemaId,
            "schema-version" => (string)$record->schemaVersion,
            "event-type" => $envelope::class,
            "content-type" => "application/vnd.confluent.avro+binary",
        ];
    }

    private function topic(): ProducerTopic
    {
        return $this->producerTopic ??= $this->producer->newTopic($this->getName());
    }

    private function attribute(): AsKafkaTopic
    {
        if (!$this->topicAttribute) {
            throw new RuntimeException("AsKafkaTopic attribute not set");
        }

        return $this->topicAttribute;
    }
}
