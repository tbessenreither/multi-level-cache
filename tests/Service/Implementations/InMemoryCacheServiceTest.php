<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service\Implementations;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Service\Implementations\InMemoryCacheService;

#[CoversClass(InMemoryCacheService::class)]
#[CoversClass(CacheObjectWrapperDto::class)]


class InMemoryCacheServiceTest extends TestCase
{
    public function testDefaultBehavior(): void
    {
        $cache = new InMemoryCacheService(maxSize: 2);

        $obj1 = GetTestObject::getWrappedTestObject('data1', 10);
        $cache->set('key1', $obj1);
        $this->assertSame($obj1->getObject(), $cache->get('key1')->getObject());


        $obj2 = GetTestObject::getWrappedTestObject('data2', -1);
        $cache->set('key2', $obj2);
        $this->assertNull($cache->get('key2'));

        $this->assertEquals(['key1'], $cache->getAllCacheKeys());

        $cache->delete('key1');
        $this->assertNull($cache->get('key1'));
        $this->assertEquals([], $cache->getAllCacheKeys());
    }

    public function testOverflowBehavior(): void
    {
        $cache = new InMemoryCacheService(maxSize: 2);

        $obj1 = GetTestObject::getWrappedTestObject('data1', 10);
        $cache->set('key1', $obj1);

        $obj2 = GetTestObject::getWrappedTestObject('data2', 10);
        $cache->set('key2', $obj2);

        $obj3 = GetTestObject::getWrappedTestObject('data3', 10);
        $cache->set('key3', $obj3);

        // key1 should be evicted as it is the oldest
        $this->assertNull($cache->get('key1'));
        $this->assertSame($obj2, $cache->get('key2'));
        $this->assertSame($obj3, $cache->get('key3'));

        $this->assertEquals(['key2', 'key3'], $cache->getAllCacheKeys());


        $cacheSize3 = new InMemoryCacheService(maxSize: 3);
        $cacheSize3->set('keyA', GetTestObject::getWrappedTestObject('dataA', 10));
        $cacheSize3->set('keyB', GetTestObject::getWrappedTestObject('dataB', 10));
        $cacheSize3->set('keyC', GetTestObject::getWrappedTestObject('dataC', 10));
        $cacheSize3->set('keyD', GetTestObject::getWrappedTestObject('dataD', 10));

        $this->assertEquals(['keyB', 'keyC', 'keyD'], $cacheSize3->getAllCacheKeys());
    }

    public function testGetDeletesExpiredObject(): void
    {
        $cache = new InMemoryCacheService(maxSize: 2);
        $key = 'expired_key';

        $testObj = $this->createMock(CacheObjectWrapperDto::class);
        // First call during set() / ensureCacheSizeLimit -> not expired
        // Second call during get() -> expired
        $testObj
            ->expects($this->exactly(2))
            ->method('isExpired')
            ->willReturnOnConsecutiveCalls(false, true);

        $cache->set($key, $testObj);

        // Call get to trigger isExpired and ensure delete is called
        $result = $cache->get($key);

        // ensure the expired branch returns null and the cache was cleaned up
        $this->assertNull($result);
        $this->assertEmpty($cache->getAllCacheKeys());
    }

    public function testGetConfiguration(): void
    {
        $cache = new InMemoryCacheService(maxSize: 2);
        $config = $cache->getConfiguration();

        $this->assertIsArray($config);
        $this->assertArrayHasKey('maxSize', $config);
        $this->assertEquals(2, $config['maxSize']);
    }

    public function testGetCachedKeys(): void
    {
        $cache = new InMemoryCacheService(maxSize: 2);
        $key1 = 'key1';
        $key2 = 'key2';

        $testObj = $this->createMock(CacheObjectWrapperDto::class);
        $cache->set($key1, $testObj);
        $cache->set($key2, $testObj);

        $cachedKeys = $cache->getCachedKeys();

        $this->assertCount(2, $cachedKeys);
        $this->assertContains($key1, $cachedKeys);
        $this->assertContains($key2, $cachedKeys);
    }

    public function testClear(): void
    {
        $cache = new InMemoryCacheService(maxSize: 2);
        $cache->set('key1', GetTestObject::getWrappedTestObject('data1', 10));

        $cache->clear();

        $this->assertNull($cache->get('key1'));
        $this->assertEmpty($cache->getAllCacheKeys());
    }

}
