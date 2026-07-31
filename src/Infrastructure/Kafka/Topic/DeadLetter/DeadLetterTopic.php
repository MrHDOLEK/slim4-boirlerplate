<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic\DeadLetter;

use App\Infrastructure\Kafka\Consumer;
use App\Infrastructure\Kafka\Serializer\AvroMessageSerializer;
use App\Infrastructure\Kafka\Topic\KafkaTopic;
use App\Infrastructure\Messaging\Envelope;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\Worker;
use Lcobucci\Clock\Clock;
use RdKafka\Producer;
use RuntimeException;

class DeadLetterTopic extends KafkaTopic
{
    private const NAME_SUFFIX = "-dead-letter";
    private const SCHEMA_SUBJECT = "dead-letter-value";

    public function __construct(
        private readonly Transport $topic,
        Producer $producer,
        AvroMessageSerializer $serializer,
        Consumer $consumer,
        DeadLetterTopicFactory $deadLetterTopicFactory,
        Clock $clock,
    ) {
        parent::__construct($producer, $serializer, $consumer, $deadLetterTopicFactory, $clock);
    }

    public function getName(): string
    {
        return $this->topic->getName() . self::NAME_SUFFIX;
    }

    public function getSchemaSubject(): string
    {
        return self::SCHEMA_SUBJECT;
    }

    public function getWorker(): Worker
    {
        throw new RuntimeException("Dead letter topics do not have workers");
    }

    public function getNumberOfConsumers(): int
    {
        return 0;
    }

    protected function partitionKeyFor(Envelope $envelope): ?string
    {
        return $envelope instanceof DeadLetterRecord ? $envelope->originalKey : null;
    }
}
