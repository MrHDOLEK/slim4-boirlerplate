<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Queue;

class QueueContainer
{
    /** @var array<Queue> */
    private array $queues = [];

    public function registerQueue(Queue $queue): void
    {
        $this->queues[$queue->getName()] = $queue;
    }

    public function getQueue(string $name): Queue
    {
        if (!array_key_exists($name, $this->queues)) {
            throw new \RuntimeException(sprintf('Queue "%s" not registered in container', $name));
        }

        return $this->queues[$name];
    }

    /**
     * @return array<\App\Infrastructure\AMQP\Queue\Queue>
     */
    public function getQueues(): array
    {
        return $this->queues;
    }
}
