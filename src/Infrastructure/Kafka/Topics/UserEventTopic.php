<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topics;

use App\Infrastructure\Attribute\AsKafkaTopic;
use App\Infrastructure\Kafka\Serializer\AvroSerializer;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Kafka\Worker\KafkaWorker;
use App\Infrastructure\Messaging\Envelope;
use RdKafka\Producer;

#[AsKafkaTopic(name: "user-events", schemaSubject: "user-events-value", numberOfWorkers: 1)]
class UserEventTopic extends KafkaTopic
{
    public function __construct(
        Producer $producer,
        AvroSerializer $serializer,
        private readonly UserEventTopicWorker $worker,
    ) {
        parent::__construct($producer, $serializer);
    }

    public function getWorker(): KafkaWorker
    {
        return $this->worker;
    }

    protected function partitionKeyFor(Envelope $envelope): ?string
    {
        $payload = $envelope->jsonSerialize();

        return isset($payload["payload"]["user"]["username"])
            ? (string)$payload["payload"]["user"]["username"]
            : null;
    }
}
