<?php

declare(strict_types=1);

namespace App\Infrastructure\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class AsEventHandler
{
    public function __construct() {}
}
