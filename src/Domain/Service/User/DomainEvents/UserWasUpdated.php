<?php

declare(strict_types=1);

namespace App\Domain\Service\User\DomainEvents;

use App\Domain\Entity\User\User;
use App\Infrastructure\Events\DomainEvent;

class UserWasUpdated extends DomainEvent
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }
}
