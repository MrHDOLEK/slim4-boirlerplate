<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka;

use App\Infrastructure\Kafka\Topic\KafkaTopic;
use Psr\Log\LoggerInterface;
use Throwable;

final class TopicProcessor
{
    private bool $shutdownRequested = false;

    public function __construct(
        private readonly Consumer $consumer,
        private readonly LoggerInterface $logger,
    ) {}

    public function shutdown(): void
    {
        $this->shutdownRequested = true;
    }

    public function process(KafkaTopic $topic): void
    {
        $worker = $topic->getWorker();
        $stream = $this->consumer->poll($topic);

        try {
            while ($stream->valid()) {
                $batch = $stream->current();

                foreach ($batch as $message) {
                    $this->handle($topic, $message);
                    $worker->countProcessedMessage();
                }

                if ($batch !== []) {
                    $this->consumer->commit(end($batch));
                }

                if ($this->shouldStop($worker)) {
                    $stream->send(Signal::STOP);

                    break;
                }

                $stream->next();
            }
        } finally {
            $this->consumer->close();
        }
    }

    private function handle(KafkaTopic $topic, KafkaMessage $message): void
    {
        $worker = $topic->getWorker();

        try {
            $worker->processMessage($message);
        } catch (Throwable $exception) {
            $this->logger->error("Kafka message processing failed", [
                "topic" => $message->topic,
                "partition" => $message->partition,
                "offset" => $message->offset,
                "schema_id" => $message->schemaId(),
                "event_type" => $message->eventType(),
                "exception" => $exception->getMessage(),
            ]);

            $worker->processFailure($message, $exception, $topic);
        }
    }

    private function shouldStop(Worker\KafkaWorker $worker): bool
    {
        if ($this->shutdownRequested) {
            $this->logger->info("Kafka consumer stopping: shutdown signal received");

            return true;
        }

        if ($worker->maxIterationsReached()) {
            $this->logger->info("Kafka consumer stopping: message limit reached", [
                "max_iterations" => $worker->getMaxIterations(),
            ]);

            return true;
        }

        if ($worker->maxLifeTimeReached()) {
            $this->logger->info("Kafka consumer stopping: max lifetime reached", [
                "max_life_time" => $worker->getMaxLifeTime()->format(DATE_ATOM),
            ]);

            return true;
        }

        if ($worker->memoryLimitReached()) {
            $this->logger->info("Kafka consumer stopping: memory limit reached", [
                "memory_limit_bytes" => $worker->getMemoryLimitInBytes(),
                "memory_usage_bytes" => memory_get_usage(true),
            ]);

            return true;
        }

        return false;
    }
}
