<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic;

use App\Infrastructure\Kafka\Attribute\AsKafkaTopic;
use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Exception\ProducerFailure;
use App\Infrastructure\Kafka\KafkaMessage;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Serializer\EncodedRecord;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterRecord;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterTopicFactory;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use Generator;
use Lcobucci\Clock\Clock;
use RdKafka\Producer;
use RdKafka\ProducerTopic;
use ReflectionClass;
use RuntimeException;
use Throwable;

abstract class KafkaTopic implements Transport
{
    private const FLUSH_TIMEOUT_MS = 10_000;
    private const PRODUCE_CHUNK_SIZE = 500;

    private ?AsKafkaTopic $topicAttribute = null;
    private ?ProducerTopic $producerTopic = null;
    private ?AvroMessageSerializer $boundSerializer = null;

    public function __construct(
        private readonly Producer $producer,
        private readonly AvroMessageSerializer $serializer,
        private readonly Consumer $consumer,
        private readonly DeadLetterTopicFactory $deadLetterTopicFactory,
        private readonly Clock $clock,
    ) {
        if ($attribute = (new ReflectionClass($this))->getAttributes(AsKafkaTopic::class)) {
            $this->topicAttribute = $attribute[0]->newInstance();
        }
    }

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

    public function send(Envelope $envelope): void
    {
        $this->sendBatch([$envelope]);
    }

    public function sendBatch(array $envelopes): void
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

        $this->flush();
    }

    public function receive(): Generator
    {
        yield from $this->consumer->poll($this->getName());
    }

    public function ack(TransportMessage $message): void
    {
        $this->consumer->markOffsetProcessed($this->deliveryOf($message));
    }

    public function reject(TransportMessage $message, Throwable $exception): void
    {
        $this->deadLetterTopicFactory
            ->buildFor($this)
            ->send(DeadLetterRecord::from($this->deliveryOf($message), $exception, $this->clock->now()));

        $this->getWorker()->processFailure($message, $exception, $this);
        $this->ack($message);
    }

    protected function partitionKeyFor(Envelope $envelope): ?string
    {
        return null;
    }

    private function flush(): void
    {
        $result = $this->producer->flush(self::FLUSH_TIMEOUT_MS);

        if ($result !== RD_KAFKA_RESP_ERR_NO_ERROR) {
            throw ProducerFailure::flushFailed($this->getName(), $result);
        }
    }

    private function deliveryOf(TransportMessage $message): KafkaMessage
    {
        if (!$message->handle instanceof KafkaMessage) {
            throw new RuntimeException(sprintf('Message from transport "%s" was not delivered by %s', $message->transport, self::class));
        }

        return $message->handle;
    }

    private function serializer(): AvroMessageSerializer
    {
        return $this->boundSerializer ??= $this->serializer->forSubject($this->getSchemaSubject());
    }

    private function publish(Envelope $envelope): void
    {
        $record = $this->serializer()->encodeRecord($envelope);

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
