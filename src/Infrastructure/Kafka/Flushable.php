<?php

declare(strict_types=1);

namespace App\Infrastructure\Kafka;

interface Flushable
{
    public function flush(): void;
}
