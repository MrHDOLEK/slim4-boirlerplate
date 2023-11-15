<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Redis\Repository;

use App\Infrastructure\Utils\Constants;

interface RedisRepositoryInterface
{
    public function generateKey(string $value): string;

    public function exists(string $key): int;

    public function get(string $key): object;

    public function set(string $key, object $value): void;

    public function setex(string $key, object $value, int $ttl = Constants::ONE_HOUR): void;

    /**
     * @param array<string> $keys
     */
    public function del(array $keys): void;
}
