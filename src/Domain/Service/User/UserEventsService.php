<?php

declare(strict_types=1);

namespace App\Domain\Service\User;

use App\Domain\Entity\User\User;
use App\Domain\Service\User\DomainEvents\UserWasCreated;
use App\Infrastructure\Persistence\Queues\UserEventQueue;

final readonly class UserEventsService
{
    public function __construct(
        private UserEventQueue $userCommandQueue,
    ) {}

    public function userWasCreated(User $user): void
    {
        $this->userCommandQueue->queue(new UserWasCreated($user));
    }
}
