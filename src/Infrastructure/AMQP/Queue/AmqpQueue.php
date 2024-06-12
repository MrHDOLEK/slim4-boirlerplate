<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\Envelope;
use App\Infrastructure\Attribute\AsAmqpQueue;
use App\Infrastructure\Serialization\Json;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use RuntimeException;

abstract class AmqpQueue implements Queue
{
    private const int TWELVE_HOURS_IN_MS = 43200000;

    private ?AsAmqpQueue $amqpQueueAttribute = null;

    public function __construct(
        private readonly AMQPChannelFactory $AMQPChannelFactory,
    ) {
        if ($attribute = (new \ReflectionClass($this))->getAttributes(AsAmqpQueue::class)) {
            $this->amqpQueueAttribute = $attribute[0]->newInstance();
        }
    }

    public function getName(): string
    {
        if (!$this->amqpQueueAttribute) {
            throw new RuntimeException("AsAmqpQueue attribute not set");
        }

        return $this->amqpQueueAttribute->getName();
    }

    public function getNumberOfConsumers(): int
    {
        if (!$this->amqpQueueAttribute) {
            throw new RuntimeException("AsAmqpQueue attribute not set");
        }

        return $this->amqpQueueAttribute->getNumberOfWorkers();
    }

    public function queue(Envelope $envelope): void
    {
        $properties = [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "expiration" => self::TWELVE_HOURS_IN_MS,
            "application_headers" => new AMQPTable(
                [
                    "x-retry-count" => 0,
                ],
            ),
        ];
        $message = new AMQPMessage(serialize($envelope), $properties);

        $this->getChannel()->basic_publish($message, "", $this->getName());
    }

    public function queueBatch(array $envelopes): void
    {
        if (empty($envelopes)) {
            return;
        }

        /** @phpstan-ignore-next-line */
        if (!empty(array_filter($envelopes, fn($envelope) => !$envelope instanceof Envelope))) {
            throw new RuntimeException(sprintf("All envelopes need to implement %s", Envelope::class));
        }

        $channel = $this->getChannel();

        foreach ($envelopes as $envelope) {
            $properties = [
                "content_type" => "text/plain",
                "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                "expiration" => self::TWELVE_HOURS_IN_MS,
                "application_headers" => new AMQPTable([
                    "x-retry-count" => 0,
                ]),
            ];

            $message = new AMQPMessage(serialize($envelope), $properties);

            $channel->batch_basic_publish($message, "", $this->getName());
        }
        $channel->publish_batch();
    }

    public function queueRawJson(Envelope $envelope): void
    {
        $properties = [
            "content_type" => "text/plain",
            "delivery_mode" => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            "expiration" => self::TWELVE_HOURS_IN_MS,
            "application_headers" => new AMQPTable([
                "x-retry-count" => 0,
            ]),
        ];
        $message = new AMQPMessage(Json::encode($envelope->jsonSerialize()), $properties);

        $this->getChannel()->basic_publish($message, "", $this->getName());
    }

    protected function getChannel(): AMQPChannel
    {
        return $this->AMQPChannelFactory->getForQueue($this);
    }
}
