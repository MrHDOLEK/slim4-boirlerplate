<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Domain\Entity\User\UserRepositoryInterface;
use Psr\Log\LoggerInterface;

abstract class UserAction extends Action
{
    public function __construct(
        LoggerInterface $logger,
        protected UserRepositoryInterface $userRepository,
    ) {
        parent::__construct($logger);
    }
}
