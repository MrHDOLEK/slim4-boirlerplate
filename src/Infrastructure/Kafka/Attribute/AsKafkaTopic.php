<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsKafkaTopic
{
    public function __construct(
        private string $name,
        private string $schemaSubject,
        private int $numberOfWorkers = 1,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getSchemaSubject(): string
    {
        return $this->schemaSubject;
    }

    public function getNumberOfWorkers(): int
    {
        return $this->numberOfWorkers;
    }
}
