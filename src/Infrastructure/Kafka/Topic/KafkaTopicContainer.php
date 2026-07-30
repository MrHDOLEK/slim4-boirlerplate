<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Topic;

use RuntimeException;

class KafkaTopicContainer
{
    /** @var array<string, KafkaTopic> */
    private array $topics = [];

    public function registerTopic(KafkaTopic $topic): void
    {
        $this->topics[$topic->getName()] = $topic;
    }

    /**
     * @return array<string, KafkaTopic>
     */
    public function getTopics(): array
    {
        return $this->topics;
    }

    public function getTopic(string $name): KafkaTopic
    {
        if (!array_key_exists($name, $this->topics)) {
            throw new RuntimeException(sprintf('Topic "%s" not registered in container', $name));
        }

        return $this->topics[$name];
    }
}
