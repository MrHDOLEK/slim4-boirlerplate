<?php

declare(strict_types=1);

namespace App\Domain\Entity\User;

use App\Domain\DomainException\DomainRecordNotFoundException;

class UserNotFoundException extends DomainRecordNotFoundException
{
    public $message = "The user you requested does not exist.";
}
