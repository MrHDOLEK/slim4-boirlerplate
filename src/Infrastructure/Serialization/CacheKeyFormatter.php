<?php

declare(strict_types=1);

namespace App\Infrastructure\Serialization;

use App\Infrastructure\Utils\Constants;

class CacheKeyFormatter
{
    public static function format(string $rawKey): string
    {
        return str_replace("/", Constants::ARGUMENT_SEPARATOR, $rawKey);
    }

    public static function formatByEntity(object $entity): string
    {
        return self::formatByClassNameAndId(get_class($entity), $entity->id());
    }

    public static function formatByClassNameAndId(string $className, string|int $id): string
    {
        return sprintf(
            "%s%s%s",
            $className,
            Constants::ARGUMENT_SEPARATOR,
            $id,
        );
    }
}
