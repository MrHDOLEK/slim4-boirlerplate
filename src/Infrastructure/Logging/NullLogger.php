<?php

declare(strict_types=1);

namespace App\Infrastructure\Logging;

use Psr\Log\NullLogger as BaseNullLogger;

class NullLogger extends BaseNullLogger
{
    public function pushProcessor($callback): void
    {
        // Do nothing
    }
}
