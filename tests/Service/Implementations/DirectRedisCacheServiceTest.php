<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service\Implementations;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisCluster;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;
use Tbessenreither\MultiLevelCache\Factory\RedisClientFactory;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;
use Tbessenreither\MultiLevelCache\Service\RedisAbstractionService;
use Tbessenreither\MultiLevelCache\Tests\Factory\RedisClientFactoryTest;

#[CoversClass(DirectRedisCacheService::class)]
#[CoversClass(CacheObjectWrapperDto::class)]
#[CoversClass(RedisAbstractionService::class)]
#[UsesClass(RedisClientFactory::class)]
class DirectRedisCacheServiceTest extends TestCase
{
    private MockObject&Redis $redisClient;

    private CacheObjectWrapperDto $testObj;
    private string $testObjSerialized;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testObj = GetTestObject::getWrappedTestObject('testdata1', 60);
        $this->testObjSerialized = serialize($this->testObj);

        $this->redisClient = $this->createMock(Redis::class);
        $this->redisClient
            ->method('isConnected')
            ->willReturn(true); // Mock Redis connection status
    }

    public function testSet(): void
    {
        $prefix = 'prefix_' . bin2hex(random_bytes(6));
        $key = 'key1';

        $this->setupRedisMockService('set', $prefix . ':' . $key);

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: $prefix,
        );

        $cache->set($key, $this->testObj);
    }

    public function testGet(): void
    {
        $prefix = 'prefix_' . bin2hex(random_bytes(6));
        $key = 'key1';

        $this->setupRedisMockService('get', $prefix . ':' . $key);

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: $prefix,
        );

        $objRetrieved = $cache->get($key);
        $this->assertNotNull($objRetrieved);
        $this->assertEquals($this->testObj, $objRetrieved);
    }

    public function testDelete(): void
    {
        $prefix = 'prefix_' . bin2hex(random_bytes(6));
        $key = 'key1';

        $this->setupRedisMockService('delete', $prefix . ':' . $key);

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: $prefix,
        );

        $cache->delete($key);
    }

    public function testGetReturnsNullWhenSerializedDataIsFalse(): void
    {
        $prefix = 'prefix_' . bin2hex(random_bytes(6));
        $key = 'key1';

        $this->redisClient
            ->expects($this->once())
            ->method('get')
            ->with($prefix . ':' . $key)
            ->willReturn(false);

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: $prefix,
        );

        $result = $cache->get($key);
        $this->assertNull($result);
    }

    public function testGetReturnsNullWhenUnserializeFails(): void
    {
        $prefix = 'prefix_' . bin2hex(random_bytes(6));
        $key = 'key1';

        $this->redisClient
            ->expects($this->once())
            ->method('get')
            ->with($prefix . ':' . $key)
            ->willReturn('invalid_serialized_data');

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: $prefix,
        );

        $result = $cache->get($key);
        $this->assertNull($result);
    }

    public function testGetReturnsNullWhenExceptionIsTriggered(): void
    {
        $prefix = 'prefix_' . bin2hex(random_bytes(6));
        $key = 'key1';

        $this->redisClient
            ->expects($this->once())
            ->method('get')
            ->with($prefix . ':' . $key)
            ->willThrowException(new \Exception('Redis error'));

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: $prefix,
        );

        $result = $cache->get($key);
        $this->assertNull($result);
    }

    public function testConstructorThrowsExceptionWhenRedisNotConnected(): void
    {
        $this->redisClient = $this->createMock(Redis::class);
        $this->redisClient
            ->expects($this->once())
            ->method('isConnected')
            ->willReturn(false); // Ensure Redis client is disconnected

        $this->expectException(CacheConnectionException::class);
        $this->expectExceptionMessage('Could not connect to Redis');

        new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: 'test_prefix',
        );
    }

    public function testGetConfiguration(): void
    {
        $this->redisClient
            ->expects($this->once())
            ->method('getHost')
            ->willReturn('127.0.0.1');
        $this->redisClient
            ->expects($this->once())
            ->method('getPort')
            ->willReturn(6379);

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: 'test_prefix',
        );

        $config = $cache->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('prefix', $config);
        $this->assertArrayHasKey('cacheAdapter', $config);
        $this->assertArrayHasKey('redisHost', $config);
        $this->assertArrayHasKey('redisPort', $config);
        $this->assertArrayHasKey('serialization', $config);
        $this->assertEquals('test_prefix', $config['prefix']);
        $this->assertEquals('php_serialize', $config['serialization']);
        $this->assertEquals('127.0.0.1', $config['redisHost']);
        $this->assertEquals(6379, $config['redisPort']);
    }

    public function testGetConfigurationCluster(): void
    {
        $redisClient = $this->createStub(RedisCluster::class);
        $redisClient->method('_masters')->willReturn([['127.0.0.1', 6379]]);
        $redisClient->method('ping')->willReturn(true);

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($redisClient),
            keyPrefix: 'test_prefix',
        );

        $config = $cache->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('prefix', $config);
        $this->assertArrayHasKey('cacheAdapter', $config);
        $this->assertArrayHasKey('redisHost', $config);
        $this->assertArrayHasKey('redisPort', $config);
        $this->assertArrayHasKey('serialization', $config);
        $this->assertEquals('test_prefix', $config['prefix']);
        $this->assertEquals('php_serialize', $config['serialization']);
        $this->assertEquals('127.0.0.1', $config['redisHost']);
        $this->assertEquals(6379, $config['redisPort']);

    }

    public function testSetUsesUnprefixedKeyWhenKeyPrefixIsEmpty(): void
    {
        $key = 'test_key';
        $this->redisClient
            ->expects($this->once())
            ->method('set')
            ->with(
                $key, // The key should not be prefixed
                $this->testObjSerialized,
                ['ex' => $this->testObj->getTtlLeft()]
            );

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: null, // Null key prefix
        );

        $cache->set($key, $this->testObj);
    }

    public function testClearWithEmptyKeyPrefix(): void
    {
        $this->redisClient
            ->expects($this->never())
            ->method('scan');

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: null, // Null key prefix
        );

        $clearResult = $cache->clear();
        $this->assertFalse($clearResult, 'Clear should return false when key prefix is empty');
    }

    public function testClearWithKeyPrefix(): void
    {
        $this->redisClient
            ->expects($this->atLeastOnce())
            ->method('scan')
            ->willReturnOnConsecutiveCalls(
                ['test_prefix:key1', 'test_prefix:key2'], // First scan returns keys
                [] // Second scan returns no keys, ending the loop
            );
        $this->redisClient
            ->expects($this->exactly(2))
            ->method('del');

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: 'test_prefix',
        );

        $clearResult = $cache->clear();
        $this->assertTrue($clearResult, 'Clear should return true when key prefix is set');
    }

    public function testDeleteByPattern(): void
    {
        $this->redisClient
            ->expects($this->atLeastOnce())
            ->method('scan')
            ->willReturnOnConsecutiveCalls(
                ['test_prefix:key1', 'test_prefix:key2'], // First scan returns keys
                [] // Second scan returns no keys, ending the loop
            );
        $this->redisClient
            ->expects($this->exactly(2))
            ->method('del');

        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: 'test_prefix',
        );

        $clearResult = $cache->deleteByPattern('some_pattern:*');
        $this->assertIsInt($clearResult);
    }

    public function testGetCachedKeys(): void
    {
        $cache = new DirectRedisCacheService(
            redisClientProvider: RedisClientFactoryTest::wrapClient($this->redisClient),
            keyPrefix: 'test_prefix',
        );

        $clearResult = $cache->getCachedKeys();
        $this->assertTrue(is_null($clearResult) || is_array($clearResult));
    }

    private function setupRedisMockService(string $method, mixed $value): void
    {
        $this->redisClient
            ->method('isConnected')
            ->willReturn(true);
        if ($method === 'get') {
            $this->setupRedisMockServiceGet($value);
        } elseif ($method === 'set') {
            $this->setupRedisMockServiceSet($value);
        } elseif ($method === 'delete') {
            $this->setupRedisMockServiceDelete($value);
        }
    }

    private function setupRedisMockServiceGet(mixed $value): void
    {
        $this->redisClient
            ->expects($this->never())
            ->method('set');
        $this->redisClient
            ->expects($this->once())
            ->method('get')
            ->with($value)
            ->willReturn($this->testObjSerialized);
        $this->redisClient
            ->expects($this->never())
            ->method('del');
    }

    private function setupRedisMockServiceSet(mixed $value): void
    {
        $this->redisClient
            ->expects($this->once())
            ->method('set')
            ->with(
                $value,
                $this->testObjSerialized,
                [
                    'ex' => $this->testObj->getTtlLeft(),
                ],
            );
        $this->redisClient
            ->expects($this->never())
            ->method('get');
        $this->redisClient
            ->expects($this->never())
            ->method('del');
    }

    private function setupRedisMockServiceDelete(mixed $value): void
    {
        $this->redisClient
            ->expects($this->never())
            ->method('set');
        $this->redisClient
            ->expects($this->never())
            ->method('get');
        $this->redisClient
            ->expects($this->once())
            ->method('del')
            ->with($value);
    }

}
