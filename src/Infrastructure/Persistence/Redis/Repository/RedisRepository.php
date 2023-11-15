<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Redis\Repository;

use App\Infrastructure\Utils\Constants;
use Predis\Client as RedisClient;

class RedisRepository implements RedisRepositoryInterface
{
    public function __construct(
        private readonly RedisClient $redis,
        private readonly string $appName,
    ) {}

    public function generateKey(string $value): string
    {
        return $this->appName . ":" . $value;
    }

    public function exists(string $key): int
    {
        return $this->redis->exists($key);
    }

    public function get(string $key): object
    {
        return json_decode($this->redis->get($key));
    }

    public function set(string $key, object $value): void
    {
        $this->redis->set($key, json_encode($value));
    }

    public function setex(string $key, object $value, int $ttl = Constants::ONE_HOUR): void
    {
        $this->redis->setex($key, $ttl, json_encode($value));
    }

    /**
     * @param array<string> $keys
     */
    public function del(array $keys): void
    {
        $this->redis->del($keys);
    }
}
