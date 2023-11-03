<?php

declare(strict_types=1);

namespace App\Domain\Service\User\DomainEvents;

use App\Infrastructure\Attribute\AsEventHandler;
use App\Infrastructure\Events\DomainEvent;
use App\Infrastructure\Events\EventHandler\EventHandler;
use Psr\Log\LoggerInterface;

#[AsEventHandler]
readonly class UserWasCreatedEventHandler implements EventHandler
{
    public function __construct(
        private LoggerInterface $logger,
    ) {}

    public function handle(DomainEvent $event): void
    {
        assert($event instanceof UserWasCreated);

        $this->logger->info(sprintf("User named %s has been created", $event->user()->firstName()));
    }
}
