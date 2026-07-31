<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic\DeadLetter;

use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Messaging\Transport;
use Lcobucci\Clock\Clock;
use RdKafka\Producer;

class DeadLetterTopicFactory
{
    public function __construct(
        private readonly Producer $producer,
        private readonly AvroMessageSerializer $serializer,
        private readonly Consumer $consumer,
        private readonly Clock $clock,
    ) {}

    public function buildFor(Transport $topic): KafkaTopic
    {
        return new DeadLetterTopic(
            $topic,
            $this->producer,
            $this->serializer,
            $this->consumer,
            $this,
            $this->clock,
        );
    }
}
