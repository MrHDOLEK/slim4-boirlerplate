<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka;

use App\Infrastructure\Kafka\Exception\ConsumerFailure;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use Generator;
use Psr\Log\LoggerInterface;
use RdKafka\Exception;
use RdKafka\KafkaConsumer;
use RdKafka\Message as RdKafkaMessage;

final class Consumer
{
    private const POLL_TIMEOUT_MS = 1_000;
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly KafkaConsumer $consumer,
        private readonly AvroSerializer $serializer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * Yields batches of decoded messages until the caller sends Signal::STOP back into the
     * generator. Subscription happens once, before the loop, not per message.
     *
     * @return Generator<int, array<KafkaMessage>, Signal|null, void>
     */
    public function poll(KafkaTopic $topic): Generator
    {
        $this->consumer->subscribe([$topic->getName()]);

        $batch = [];

        while (true) {
            $message = $this->consumer->consume(self::POLL_TIMEOUT_MS);

            if ($this->isEndOfStream($message)) {
                if ($batch !== []) {
                    $signal = yield $batch;
                    $batch = [];

                    if ($signal === Signal::STOP) {
                        return;
                    }
                }

                continue;
            }

            $this->guardAgainstFatalError($message);

            $batch[] = $this->decode($message);

            if (count($batch) < self::BATCH_SIZE) {
                continue;
            }

            $signal = yield $batch;
            $batch = [];

            if ($signal === Signal::STOP) {
                return;
            }
        }
    }

    public function commit(KafkaMessage $message): void
    {
        try {
            $this->consumer->commit();
        } catch (Exception $exception) {
            throw ConsumerFailure::commitFailed($exception->getCode(), $exception->getMessage());
        }
    }

    public function close(): void
    {
        $this->consumer->close();
    }

    private function decode(RdKafkaMessage $message): KafkaMessage
    {
        return new KafkaMessage(
            topic: (string)$message->topic_name,
            partition: $message->partition,
            offset: $message->offset,
            payload: $this->serializer->decode((string)$message->payload),
            headers: array_map(strval(...), $message->headers),
            key: $message->key,
            timestamp: $message->timestamp,
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
