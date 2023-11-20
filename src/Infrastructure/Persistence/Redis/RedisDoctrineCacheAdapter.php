<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Redis;

use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisDoctrineCacheAdapter implements CacheItemPoolInterface
{
    public function __construct(
        private RedisAdapter $redisAdapter,
    ) {}

    public function getItem($key): CacheItemInterface
    {
        return $this->redisAdapter->getItem($key);
    }

    public function getItems(array $keys = []): iterable
    {
        return $this->redisAdapter->getItems($keys);
    }

    public function hasItem($key): bool
    {
        return $this->redisAdapter->hasItem($key);
    }

    public function clear(): bool
    {
        return $this->redisAdapter->clear();
    }

    public function deleteItem($key): bool
    {
        return $this->redisAdapter->deleteItem($key);
    }

    public function deleteItems(array $keys): bool
    {
        return $this->redisAdapter->deleteItems($keys);
    }

    public function save(CacheItemInterface $item): bool
    {
        return $this->redisAdapter->save($item);
    }

    public function saveDeferred(CacheItemInterface $item): bool
    {
        return $this->redisAdapter->saveDeferred($item);
    }

    public function commit(): bool
    {
        return $this->redisAdapter->commit();
    }
}
