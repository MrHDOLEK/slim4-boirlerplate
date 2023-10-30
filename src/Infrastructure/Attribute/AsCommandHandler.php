<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS)]
class AsCommandHandler
{
    public function __construct() {}
}
