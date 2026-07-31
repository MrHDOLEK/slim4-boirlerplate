<?php

declare(strict_types=1);

namespace Tests\Support\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsFixture
{
    public function __construct(
        private string $name,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }
}
