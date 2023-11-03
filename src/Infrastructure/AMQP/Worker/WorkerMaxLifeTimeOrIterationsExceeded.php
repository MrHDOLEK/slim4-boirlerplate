<?php

declare(strict_types=1);

namespace App\Infrastructure\AMQP\Worker;

use RuntimeException;

class WorkerMaxLifeTimeOrIterationsExceeded extends RuntimeException
{
}
