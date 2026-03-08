<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Redis;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\KeyGeneratorService;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;
use Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc\TestServiceA;

#[CoversClass(InvalidatorService::class)]
#[UsesClass(MethodCallObject::class)]
#[UsesClass(MlcCacheableService::class)]
#[UsesClass(MlcCacheableMethod::class)]
#[UsesClass(KeyGeneratorService::class)]
#[UsesClass(DirectRedisCacheService::class)]
class InvalidatorServiceTest extends TestCase
{
    public function testClassInvalidation(): void
    {
        $redisStub = $this->createStub(Redis::class);
        $redisStub->method('isConnected')->willReturn(true);
        $redisStub->method('scan')->willReturnOnConsecutiveCalls(
            ['key:1', 'key:2'],
            []
        );
        $directRedisCacheService = new DirectRedisCacheService($redisStub);

        $multiLevelCacheFactoryStub = $this->createStub(MultiLevelCacheFactory::class);
        $multiLevelCacheFactoryStub->method('getImplementationRedisWithPrefix')
            ->willReturn($directRedisCacheService);

        $invalidatorService = new InvalidatorService($multiLevelCacheFactoryStub);
        $result = $invalidatorService->invalidateCacheForClass(TestServiceA::class);
        $this->assertTrue($result);
    }

    public function testClassInvalidationError(): void
    {
        $redisStub = $this->createStub(Redis::class);
        $redisStub->method('isConnected')->willReturn(true);
        $redisStub->method('scan')->willThrowException(new Exception('random error'));
        $directRedisCacheService = new DirectRedisCacheService($redisStub);

        $multiLevelCacheFactoryStub = $this->createStub(MultiLevelCacheFactory::class);
        $multiLevelCacheFactoryStub->method('getImplementationRedisWithPrefix')
            ->willReturn($directRedisCacheService);

        $invalidatorService = new InvalidatorService($multiLevelCacheFactoryStub);
        $result = $invalidatorService->invalidateCacheForClass(TestServiceA::class);
        $this->assertFalse($result);
    }

    public function testMethodInvalidation(): void
    {
        $redisStub = $this->createStub(Redis::class);
        $redisStub->method('isConnected')->willReturn(true);
        $redisStub->method('scan')->willReturnOnConsecutiveCalls(
            ['key:1', 'key:2'],
            []
        );
        $directRedisCacheService = new DirectRedisCacheService($redisStub);

        $multiLevelCacheFactoryStub = $this->createStub(MultiLevelCacheFactory::class);
        $multiLevelCacheFactoryStub->method('getImplementationRedisWithPrefix')
            ->willReturn($directRedisCacheService);

        $invalidatorService = new InvalidatorService($multiLevelCacheFactoryStub);
        $result = $invalidatorService->invalidateCacheForMethod(TestServiceA::class, 'testMethod');
        $this->assertTrue($result);
    }

    public function testMethodInvalidationError(): void
    {
        $redisStub = $this->createStub(Redis::class);
        $redisStub->method('isConnected')->willReturn(true);
        $redisStub->method('scan')->willThrowException(new Exception('random error'));
        $directRedisCacheService = new DirectRedisCacheService($redisStub);

        $multiLevelCacheFactoryStub = $this->createStub(MultiLevelCacheFactory::class);
        $multiLevelCacheFactoryStub->method('getImplementationRedisWithPrefix')
            ->willReturn($directRedisCacheService);

        $invalidatorService = new InvalidatorService($multiLevelCacheFactoryStub);
        $result = $invalidatorService->invalidateCacheForMethod(TestServiceA::class, 'testMethod');
        $this->assertFalse($result);
    }
}
