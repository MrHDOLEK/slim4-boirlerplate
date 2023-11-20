<?php

declare(strict_types=1);

namespace Tests\Infrastructure\Persistence\Redis;

use App\Infrastructure\Persistence\Redis\RedisDoctrineCacheAdapter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\Cache\Adapter\RedisAdapter;

class RedisDoctrineCacheAdapterTest extends TestCase
{
    private MockObject|RedisAdapter $redisAdapterMock;
    private RedisDoctrineCacheAdapter $redisDoctrineCacheAdapter;

    protected function setUp(): void
    {
        $this->redisAdapterMock = $this->createMock(RedisAdapter::class);
        $this->redisDoctrineCacheAdapter = new RedisDoctrineCacheAdapter($this->redisAdapterMock);
    }

    public function testGetItemsSuccess(): void
    {
        $keys = ["key1", "key2"];
        $items = [$this->createMock(CacheItemInterface::class), $this->createMock(CacheItemInterface::class)];

        $this->redisAdapterMock
            ->method("getItems")
            ->with($keys)
            ->willReturn($items);

        $this->assertSame($items, $this->redisDoctrineCacheAdapter->getItems($keys));
    }

    public function testHasItemSuccess(): void
    {
        $key = "test_key";

        $this->redisAdapterMock
            ->method("hasItem")
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->hasItem($key));
    }

    public function testClearSuccess(): void
    {
        $this->redisAdapterMock
            ->method("clear")
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->clear());
    }

    public function testDeleteItemSuccess(): void
    {
        $key = "test_key";

        $this->redisAdapterMock
            ->method("deleteItem")
            ->with($key)
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->deleteItem($key));
    }

    public function testDeleteItemsSuccess(): void
    {
        $keys = ["key1", "key2"];

        $this->redisAdapterMock
            ->method("deleteItems")
            ->with($keys)
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->deleteItems($keys));
    }

    public function testSaveSuccess(): void
    {
        $cacheItemMock = $this->createMock(CacheItemInterface::class);

        $this->redisAdapterMock
            ->method("save")
            ->with($cacheItemMock)
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->save($cacheItemMock));
    }

    public function testSaveDeferredSuccess(): void
    {
        $cacheItemMock = $this->createMock(CacheItemInterface::class);

        $this->redisAdapterMock
            ->method("saveDeferred")
            ->with($cacheItemMock)
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->saveDeferred($cacheItemMock));
    }

    public function testCommitSuccess(): void
    {
        $this->redisAdapterMock
            ->method("commit")
            ->willReturn(true);

        $this->assertTrue($this->redisDoctrineCacheAdapter->commit());
    }
}
