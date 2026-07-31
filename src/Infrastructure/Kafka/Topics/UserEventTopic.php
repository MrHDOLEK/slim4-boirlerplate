<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topics;

use App\Infrastructure\Events\EventQueueWorker;
use App\Infrastructure\Kafka\Attribute\AsKafkaTopic;
use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Topic\DeadLetter\DeadLetterTopicFactory;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Worker;
use Lcobucci\Clock\Clock;
use RdKafka\Producer;

#[AsKafkaTopic(name: "user-events", schemaSubject: "user-events-value", numberOfWorkers: 1)]
class UserEventTopic extends KafkaTopic
{
    public function __construct(
        Producer $producer,
        AvroMessageSerializer $serializer,
        Consumer $consumer,
        DeadLetterTopicFactory $deadLetterTopicFactory,
        Clock $clock,
        private readonly EventQueueWorker $worker,
    ) {
        parent::__construct($producer, $serializer, $consumer, $deadLetterTopicFactory, $clock);
    }

    public function getWorker(): Worker
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
