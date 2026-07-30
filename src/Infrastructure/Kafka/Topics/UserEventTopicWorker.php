<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topics;

use App\Infrastructure\Kafka\KafkaMessage;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Kafka\Worker\BaseKafkaWorker;
use Lcobucci\Clock\Clock;
use Psr\Log\LoggerInterface;
use Throwable;

class UserEventTopicWorker extends BaseKafkaWorker
{
    public function __construct(
        private readonly LoggerInterface $logger,
        Clock $clock,
    ) {
        parent::__construct($clock);
    }

    public function getName(): string
    {
        return "user-event-topic-worker";
    }

    public function processMessage(KafkaMessage $message): void
    {
        $this->logger->info("Consumed user event", [
            "event_type" => $message->eventType(),
            "schema_id" => $message->schemaId(),
            "offset" => $message->offset,
        ]);
    }

    public function processFailure(KafkaMessage $message, Throwable $exception, KafkaTopic $topic): void
    {
        $this->logger->error("User event could not be processed", [
            "topic" => $message->topic,
            "partition" => $message->partition,
            "offset" => $message->offset,
            "exception" => $exception->getMessage(),
        ]);
    }
}
