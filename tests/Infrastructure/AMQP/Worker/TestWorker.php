<?php

declare(strict_types=1);

namespace Tests\Infrastructure\AMQP\Worker;

use App\Infrastructure\Messaging\BaseWorker;
use App\Infrastructure\Messaging\Transport;
use App\Infrastructure\Messaging\TransportMessage;
use Lcobucci\Clock\Clock;
use Throwable;

class TestWorker extends BaseWorker
{
    public function __construct(
        Clock $clock,
    ) {
        parent::__construct($clock);
    }

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
