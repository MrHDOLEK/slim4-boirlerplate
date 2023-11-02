<?php

declare(strict_types=1);

namespace App\Domain\Service\User;

use App\Domain\Entity\User\User;
use App\Domain\Service\User\DomainEvents\UserWasCreated;
use App\Infrastructure\Persistence\Queues\UserEventQueue;

class UserEventsService
{
    public function __construct(
        private UserEventQueue $userEventQueue,
    ) {}

    public function userWasCreated(User $user): void
    {
        $this->userEventQueue->queue(new UserWasCreated($user));
    }
}
