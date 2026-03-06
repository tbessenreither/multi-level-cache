<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Redis;
use ReflectionClass;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;
use Tbessenreither\MultiLevelCache\Service\Implementations\InMemoryCacheService;
use Tbessenreither\MultiLevelCache\Service\MultiLevelCacheService;
use Symfony\Component\Stopwatch\Stopwatch;
use Tbessenreither\MultiLevelCache\Enum\CacheTypeEnum;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;

#[CoversClass(MultiLevelCacheFactory::class)]
#[CoversClass(InMemoryCacheService::class)]
#[CoversClass(DirectRedisCacheService::class)]
#[CoversClass(MultiLevelCacheService::class)]
#[CoversClass(CacheObjectWrapperDto::class)]


class MultiLevelCacheFactoryTest extends TestCase
{
    public function testGetImplementationInMemoryReturnsInMemoryService(): void
    {
        $factory = $this->makeFactoryWithInjectedDeps();
        $inMemory = $factory->getImplementationInMemory(50);
        $this->assertInstanceOf(InMemoryCacheService::class, $inMemory);
    }

    public function testGetImplementationRedisReturnsDirectRedisService(): void
    {
        $redisMock = $this->createStub(Redis::class);
        $redisMock->method('isConnected')->willReturn(true);
        $factory = $this->makeFactoryWithInjectedDeps($redisMock);

        $directRedis = $factory->getImplementationRedis($redisMock, 'prefix');
        $this->assertInstanceOf(DirectRedisCacheService::class, $directRedis);
    }

    public function testCreateInMemoryOnlyCacheReturnsWorkingService(): void
    {
        $factory = $this->makeFactoryWithInjectedDeps();
        $service = $factory->createInMemoryOnlyCache(inMemoryCacheMaxSize: 10, writeL0OnSet: true, cacheGroupName: 'test');
        $this->assertInstanceOf(MultiLevelCacheService::class, $service);

        $service->set('k1', (object) ['v' => 'x'], 60);
        $result = $service->get('k1', null, 60);
        $this->assertIsObject($result);
        $this->assertEquals('x', $result->v);
    }

    public function testCreateDefault2LevelCacheReturnsService(): void
    {
        $redisMock = $this->createStub(Redis::class);
        $redisMock->method('isConnected')->willReturn(true);
        $factory = $this->makeFactoryWithInjectedDeps($redisMock);

        $twoLevel = $factory->createDefault2LevelCache(inMemoryCacheMaxSize: 5, redisKeyPrefix: 'pfx', writeL0OnSet: false, cacheGroupName: 'g');
        $this->assertInstanceOf(MultiLevelCacheService::class, $twoLevel);
    }

    public function testConstructorThrowsWhenRedisNotConnected(): void
    {
        $this->expectException(CacheConnectionException::class);
        $stopwatchMock = $this->createStub(Stopwatch::class);
        $collectorMock = $this->createStub(MultiLevelCacheDataCollector::class);

        new MultiLevelCacheFactory(redisDsn: 'redis://127.0.0.1:9999', stopwatch: $stopwatchMock, cacheDataCollector: $collectorMock);
    }

    public function testGeneratorByCacheTypeEnum(): void
    {
        $redisMock = $this->createStub(Redis::class);
        $redisMock->method('isConnected')->willReturn(true);
        $factory = $this->makeFactoryWithInjectedDeps($redisMock);

        foreach (CacheTypeEnum::cases() as $case) {
            $service = $factory->createByType(
                type: $case,
                inMemoryCacheMaxSize: 10,
                redisKeyPrefix: 'test',
                writeL0OnSet: true,
                cacheGroupName: 'test'
            );
            $this->assertInstanceOf(MultiLevelCacheService::class, $service);
        }
    }

    private function makeFactoryWithInjectedDeps(?Redis $redisMock = null): MultiLevelCacheFactory
    {
        $redisMock ??= $this->createStub(Redis::class);
        $stopwatchMock = $this->createStub(Stopwatch::class);
        $collectorMock = $this->createStub(MultiLevelCacheDataCollector::class);

        $factory =


            new class () extends MultiLevelCacheFactory {
                public function __construct()
                {
                }
            };

        $rc = new ReflectionClass(MultiLevelCacheFactory::class);

        $prop = $rc->getProperty('redisClient');
        $prop->setAccessible(true);
        $prop->setValue($factory, $redisMock);

        $prop = $rc->getProperty('redisDsn');
        $prop->setAccessible(true);
        $prop->setValue($factory, 'redis://localhost');

        $prop = $rc->getProperty('stopwatch');
        $prop->setAccessible(true);
        $prop->setValue($factory, $stopwatchMock);

        $prop = $rc->getProperty('cacheDataCollector');
        $prop->setAccessible(true);
        $prop->setValue($factory, $collectorMock);

        $prop = $rc->getProperty('cacheReadDisabled');
        $prop->setAccessible(true);
        $prop->setValue($factory, false);

        return $factory;
    }
}
