<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue\DelayedQueue;

use App\Infrastructure\AMQP\AMQPChannelFactory;
use App\Infrastructure\AMQP\AMQPChannelOptions;
use App\Infrastructure\AMQP\Queue\AmqpQueue;
use App\Infrastructure\AMQP\Queue\FailedQueue\FailedQueueFactory;
use App\Infrastructure\Messaging\Serializer\MessageSerializer;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\Worker;
use InvalidArgumentException;
use PhpAmqpLib\Channel\AMQPChannel;
use RuntimeException;

class DelayedQueue extends AmqpQueue
{
    private const X_DEAD_LETTER_EXCHANGE = "dlx";

    public function __construct(
        private readonly Transport $queue,
        private readonly int $delayInSeconds,
        private readonly AMQPChannelFactory $AMQPChannelFactory,
        MessageSerializer $serializer,
        FailedQueueFactory $failedQueueFactory,
    ) {
        if ($this->delayInSeconds < 1) {
            throw new InvalidArgumentException("Delay cannot be less than 1 second");
        }
        parent::__construct($AMQPChannelFactory, $serializer, $failedQueueFactory);
    }

    public function getName(): string
    {
        return "delayed-" . $this->delayInSeconds . "s-" . $this->queue->getName();
    }

    public function getWorker(): Worker
    {
        throw new RuntimeException("Delayed queues do not have workers");
    }

    public function getNumberOfConsumers(): int
    {
        return 0;
    }

    protected function getChannel(): AMQPChannel
    {
        $options = new AMQPChannelOptions(false, true, false, false, false, [
            "x-dead-letter-exchange" => ["S", self::X_DEAD_LETTER_EXCHANGE],
            "x-dead-letter-routing-key" => ["S", $this->queue->getName()],
            "x-message-ttl" => ["I", $this->delayInSeconds * 1000],
            "x-expires" => ["I", $this->delayInSeconds * 1000 + 100000],
        ]);

        return $this->AMQPChannelFactory->getForQueue($this, $options);
    }
}
