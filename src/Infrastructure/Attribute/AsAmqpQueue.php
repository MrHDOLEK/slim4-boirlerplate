<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsAmqpQueue
{
    public function __construct(
        private string $name,
        private int $numberOfWorkers,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getNumberOfWorkers(): int
    {
        return $this->numberOfWorkers;
    }
}
