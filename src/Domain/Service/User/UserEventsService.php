<?php

declare(strict_types=1);

namespace App\Domain\Service\User;

use App\Domain\Entity\User\User;
use App\Domain\Service\User\DomainEvents\UserWasCreated;
use App\Domain\Service\User\DomainEvents\UserWasUpdated;
use App\Infrastructure\Events\EventPublisher;

class UserEventsService
{
    public function __construct(
        private EventPublisher $eventPublisher,
    ) {}

    public function userWasCreated(User $user): void
    {
        $this->eventPublisher->publish(new UserWasCreated($user));
    }

    public function userWasUpdated(User $user): void
    {
        $this->eventPublisher->publish(new UserWasUpdated($user));
    }
}
