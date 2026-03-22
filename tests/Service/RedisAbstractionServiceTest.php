<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service;

use BadMethodCallException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisCluster;
use RedisClusterException;
use RedisException;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;
use Tbessenreither\MultiLevelCache\Factory\RedisClientFactory;
use Tbessenreither\MultiLevelCache\Service\RedisAbstractionService;
use Tbessenreither\MultiLevelCache\Tests\Factory\RedisClientFactoryTest;

#[CoversClass(RedisAbstractionService::class)]
#[UsesClass(RedisClientFactory::class)]
class RedisAbstractionServiceTest extends TestCase
{
    public function testConstructorRedisConnected(): void
    {
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($this->getRedisConnected()));

        $this->assertTrue($service->isConnected());
    }

    public function testConstructorRedisClusterConnected(): void
    {
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($this->getRedisClusterConnected()));

        $this->assertTrue($service->isConnected());
    }

    public function testConstructorRedisNotConnected(): void
    {
        $this->expectException(CacheConnectionException::class);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($this->getRedisNotConnected()));
        $service->get('somekey'); // Trigger lazy connection check
    }

    public function testConstructorRedisClusterNotConnected(): void
    {
        $this->expectException(CacheConnectionException::class);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($this->getRedisClusterNotConnected()));
        $service->get('somekey'); // Trigger lazy connection check
    }

    public function testConstructorRedisClusterNotResponding(): void
    {
        $this->expectException(CacheConnectionException::class);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($this->getRedisClusterNotResponding()));
        $service->get('somekey'); // Trigger lazy connection check
    }

    public function testScanRedis(): void
    {
        $expectedKeys = ['key1', 'key2'];

        $redisClient = $this->getRedisConnected();
        $redisClient->method('scan')->willReturn($expectedKeys);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));
        $itterator = null;
        $result = $service->scan($itterator, '*', 10);

        $this->assertEquals($expectedKeys, $result);
    }

    public function testScanRedisCluster(): void
    {
        $expectedKeys = ['key1', 'key2'];
        $redisClient = $this->getRedisClusterConnected();
        $redisClient->method('scan')->willReturn($expectedKeys);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));
        $itterator = null;
        $result = $service->scan($itterator, '*', 10);

        $this->assertEquals($expectedKeys, $result);
    }

    public function testFlushAllRedis(): void
    {
        $redisClient = $this->getRedisConnected(true);
        $redisClient->expects($this->once())->method('flushAll');

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));
        $service->flushAll();
    }

    /**
     * This test is borked.
     * Because RedisCluster needs to open connections to all masters and run flush there.
     * And we don't have a real Redis Server running and no way of injecting a mock here.
     * Still, this is testing the case of "no reachable masters" with "no exception thrown" and "false returned" which is the expected behavior.
     */
    public function testFlushAllRedisClusterWithNoReplyOfMasters(): void
    {
        $redisClient = $this->getRedisClusterConnected(true);
        $redisClient->expects($this->never())->method('flushAll');

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));
        $result = $service->flushAll();
        $this->assertFalse($result);
    }

    public function testFlushDbRedis(): void
    {
        $redisClient = $this->getRedisConnected(true);
        $redisClient->expects($this->once())->method('flushDb');

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));
        $service->flushDb();
    }

    /**
    * This test is borked.
    * Because RedisCluster needs to open connections to all masters and run flush there.
    * And we don't have a real Redis Server running and no way of injecting a mock here.
    * Still, this is testing the case of "no reachable masters" with "no exception thrown" and "false returned" which is the expected behavior.
    */
    public function testFlushDbRedisClusterWithNoReplyOfMasters(): void
    {
        $redisClient = $this->getRedisClusterConnected(true);
        $redisClient->expects($this->never())->method('flushDb');

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));
        $result = $service->flushDb();
        $this->assertFalse($result);
    }

    public function testGetHostAndPortRedis(): void
    {
        $redisClient = $this->getRedisConnected(true);
        $redisClient->expects($this->once())->method('getHost')->willReturn('127.0.0.1');
        $redisClient->expects($this->once())->method('getPort')->willReturn(6379);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $host = $service->getHost();
        $port = $service->getPort();

        $this->assertEquals('127.0.0.1', $host);
        $this->assertEquals(6379, $port);
    }

    public function testGetHostAndPortRedisCluster(): void
    {
        $redisClient = $this->getRedisClusterConnected(true);
        $redisClient->expects($this->atLeastOnce())->method('_masters')->willReturn([['127.0.0.1', 6379]]);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $host = $service->getHost();
        $port = $service->getPort();
        $this->assertEquals('127.0.0.1', $host);
        $this->assertEquals(6379, $port);
    }

    public function testGetHostRedisClusterWithNoMasters(): void
    {
        $this->expectException(RedisException::class);

        $redisClient = $this->createStub(RedisCluster::class);
        $callNumber = 0;
        $redisClient->method('_masters')->willReturnCallback(function () use (&$callNumber) {
            if (++$callNumber === 1) {
                return [['127.0.0.1', 6379]];
            }

            return [];
        });
        $redisClient->method('ping')->willReturn(true);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $service->getHost();
    }

    public function testGetPortRedisClusterWithNoMasters(): void
    {
        $this->expectException(RedisException::class);

        $redisClient = $this->createStub(RedisCluster::class);
        $callNumber = 0;
        $redisClient->method('_masters')->willReturnCallback(function () use (&$callNumber) {
            if (++$callNumber === 1) {
                return [['127.0.0.1', 6379]];
            }

            return [];
        });
        $redisClient->method('ping')->willReturn(true);

        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $service->getPort();
    }

    public function testGetMastersRedis(): void
    {
        $redisClient = $this->getRedisConnected(true);
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $masters = $service->_masters();

        $this->assertEquals([['127.0.0.1', 6379]], $masters);
    }

    public function testGetMastersRedisCluster(): void
    {
        $redisClient = $this->getRedisClusterConnected(true);
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $masters = $service->_masters();

        $this->assertEquals([['127.0.0.1', 6379]], $masters);
    }

    public function testHexpire(): void
    {
        $redisClient = $this->getRedisConnected(true);
        $expectedResponse = ['field1' => true, 'field2' => true];
        $redisClient->expects($this->once())->method('hexpire')->with('key', 3600, ['field1', 'field2'])->willReturn($expectedResponse);
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $result = $service->hexpire('key', 3600, ['field1', 'field2']);
        $this->assertEquals($expectedResponse, $result);
    }

    public function testHexpireNonexistant(): void
    {
        $redisClient = $this->getRedisConnected(true);
        $redisClient->method('hexpire')->willThrowException(new BadMethodCallException('hexpire method does not exist'));
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $result = $service->hexpire('key', 3600, ['field1', 'field2']);
        $this->assertFalse($result);
    }

    public function testCallValid(): void
    {
        $method = 'getPort';
        $redisClient = $this->getRedisConnected(true);
        $redisClient->expects($this->once())->method($method)->willReturn(6379);
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($redisClient));

        $result = $service->__call($method, []);
        $this->assertEquals(6379, $result);
    }

    public function testCallInvalid(): void
    {
        $this->expectException(BadMethodCallException::class);
        $service = new RedisAbstractionService(RedisClientFactoryTest::wrapClient($this->getRedisConnected()));

        $service->__call('nonExistentMethod', []);
    }


    // Helper methods to create stubs and mocks for Redis and RedisCluster in various states

    private function getRedisConnected(bool $mock = false): (Redis&Stub)|(Redis&MockObject)
    {
        if ($mock) {
            $client = $this->createMock(Redis::class);
        } else {
            $client = $this->createStub(Redis::class);
        }
        $client->method('isConnected')->willReturn(true);
        $client->method('getHost')->willReturn('127.0.0.1');
        $client->method('getPort')->willReturn(6379);

        return $client;
    }

    private function getRedisClusterConnected(bool $mock = false): (RedisCluster&Stub)|(RedisCluster&MockObject)
    {
        if ($mock) {
            $client = $this->createMock(RedisCluster::class);
        } else {
            $client = $this->createStub(RedisCluster::class);
        }
        $client->method('_masters')->willReturn([['127.0.0.1', 6379]]);
        $client->method('ping')->willReturn(true);

        return $client;
    }

    private function getRedisNotConnected(bool $mock = false): (Redis&Stub)|(Redis&MockObject)
    {
        if ($mock) {
            $client = $this->createMock(Redis::class);
        } else {
            $client = $this->createStub(Redis::class);
        }
        $client->method('isConnected')->willReturn(false);

        return $client;
    }

    private function getRedisClusterNotConnected(bool $mock = false): (RedisCluster&Stub)|(RedisCluster&MockObject)
    {
        if ($mock) {
            $client = $this->createMock(RedisCluster::class);
        } else {
            $client = $this->createStub(RedisCluster::class);
        }
        $client->method('_masters')->willReturn([]);
        $client->method('ping')->willReturn(false);

        return $client;
    }

    private function getRedisClusterNotResponding(bool $mock = false): (RedisCluster&Stub)|(RedisCluster&MockObject)
    {
        if ($mock) {
            $client = $this->createMock(RedisCluster::class);
        } else {
            $client = $this->createStub(RedisCluster::class);
        }
        $client->method('_masters')->willReturn([['127.0.0.1', 6379]]);
        $client->method('ping')->willThrowException(new RedisClusterException('Connection error'));

        return $client;
    }
}
