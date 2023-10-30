<?php

declare(strict_types=1);

namespace App\Infrastructure\ValueObject;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

final class UuidFactory
{
    public static function random(): UuidInterface
    {
        return Uuid::uuid4();
    }
}
