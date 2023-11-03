<?php

declare(strict_types=1);

namespace Tests;

use App\Infrastructure\Events\EventBus;

abstract class EventHandlerTestCase extends TestCase
{
    public function testItShouldBeInCommandBus(): void
    {
        /** @var EventBus $commandBus */
        $commandBus = $this->getContainer()->get(EventBus::class);

        $this->assertNotEmpty(array_filter(
            $commandBus->getEventHandlers(),
            fn(EventBus $eventHandler) => $eventHandler::class === $this->getEventHandlers()::class,
        ), sprintf('CommandHandler "%s" not found in CommandBus. Did you tag it with an attribute?', $this->getEventHandlers()::class));
    }

    abstract protected function getEventHandlers(): EventBus;
}
