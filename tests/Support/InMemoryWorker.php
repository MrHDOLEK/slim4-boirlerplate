<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Infrastructure\Messaging\BaseWorker;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use Throwable;

class InMemoryWorker extends BaseWorker
{
    public function getName(): string
    {
        return "test-worker";
    }

    public function processMessage(TransportMessage $message): void
    {
    }

    public function processFailure(TransportMessage $message, Throwable $exception, Transport $transport): void
    {
    }
}
