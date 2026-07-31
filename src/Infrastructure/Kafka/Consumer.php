<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka;

use App\Infrastructure\Kafka\Exception\ConsumerFailure;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Messaging\Signal;
use App\Infrastructure\Messaging\TransportMessage;
use Generator;
use Psr\Log\LoggerInterface;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Message as RdKafkaMessage;
use RdKafka\TopicPartition;

final class Consumer
{
    public const TRANSPORT = "kafka";
    private const POLL_TIMEOUT_MS = 1_000;
    private const BATCH_SIZE = 100;

    /** @var array<string, KafkaMessage> */
    private array $pendingOffsets = [];

    public function __construct(
        private readonly KafkaConsumer $consumer,
        private readonly AvroSerializer $serializer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @return Generator<int, array<TransportMessage>, Signal|null, void>
     */
    public function poll(string $topic): Generator
    {
        $this->consumer->subscribe([$topic]);

        $batch = [];

        try {
            while (true) {
                $message = $this->consumer->consume(self::POLL_TIMEOUT_MS);

                if ($this->isEndOfStream($message)) {
                    if ($batch === []) {
                        continue;
                    }
                } else {
                    $this->guardAgainstFatalError($message);

                    $batch[] = $this->decode($message);

                    if (count($batch) < self::BATCH_SIZE) {
                        continue;
                    }
                }

                $delivered = $batch;
                $batch = [];

                $signal = yield $delivered;
                $this->commitPendingOffsets();

                if ($signal === Signal::STOP) {
                    return;
                }
            }
        } finally {
            $this->commitPendingOffsets();
            $this->consumer->close();
        }
    }

    public function markOffsetProcessed(KafkaMessage $message): void
    {
        $this->pendingOffsets[$message->topic . ":" . $message->partition] = $message;
    }

    private function commitPendingOffsets(): void
    {
        if ($this->pendingOffsets === []) {
            return;
        }

        $offsets = array_map(
            fn(KafkaMessage $message): TopicPartition => new TopicPartition($message->topic, $message->partition, $message->offset + 1),
            array_values($this->pendingOffsets),
        );
        $this->pendingOffsets = [];

        try {
            $this->consumer->commit($offsets);
        } catch (Exception $exception) {
            throw ConsumerFailure::commitFailed($exception->getCode(), $exception->getMessage());
        }
    }

    private function decode(RdKafkaMessage $message): TransportMessage
    {
        $delivery = new KafkaMessage(
            topic: (string)$message->topic_name,
            partition: $message->partition,
            offset: $message->offset,
            payload: $this->serializer->decode((string)$message->payload),
            headers: array_map(strval(...), $message->headers),
            key: $message->key,
            timestamp: $message->timestamp,
        );

        return new TransportMessage(
            body: $delivery->payload,
            headers: $delivery->headers,
            transport: self::TRANSPORT,
            source: $delivery->topic,
            handle: $delivery,
        );
    }

    private function isEndOfStream(?RdKafkaMessage $message): bool
    {
        if ($message === null) {
            return true;
        }

        return in_array(
            $message->err,
            [RD_KAFKA_RESP_ERR__PARTITION_EOF, RD_KAFKA_RESP_ERR__TIMED_OUT],
            true,
        );
    }

    private function guardAgainstFatalError(RdKafkaMessage $message): void
    {
        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
            return;
        }

        $this->logger->error("Kafka consumer received an error", [
            "error_code" => $message->err,
            "reason" => $message->errstr(),
            "topic" => $message->topic_name,
            "partition" => $message->partition,
        ]);

        throw ConsumerFailure::fatal($message->err, $message->errstr());
    }
}
