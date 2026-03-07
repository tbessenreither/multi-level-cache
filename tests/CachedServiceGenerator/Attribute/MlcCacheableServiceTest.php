<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\Enum\CacheTypeEnum;

#[CoversClass(MlcCacheableService::class)]
#[MlcCacheableService(
    additionalInterface: 'App\\Interface\\MyAdditionalInterface',
    dataVersion: 'v1',
    cacheType: CacheTypeEnum::IN_MEMORY,
    inMemoryCacheMaxSize: 200,
)]
class MlcCacheableServiceTest extends TestCase
{
    public function testSetupAndGetter(): void
    {

        $cacheableService1 = new MlcCacheableService(
            additionalInterface: 'App\\Interface\\MyAdditionalInterface',
            dataVersion: 'v1',
            cacheType: CacheTypeEnum::IN_MEMORY,
            inMemoryCacheMaxSize: 200,
        );
        $this->assertSame('App\\Interface\\MyAdditionalInterface', $cacheableService1->getAdditionalInterface());
        $this->assertSame('v1', $cacheableService1->getDataVersion());
        $this->assertSame(CacheTypeEnum::IN_MEMORY, $cacheableService1->getCacheType());
        $this->assertSame(200, $cacheableService1->getInMemoryCacheMaxSize());

        $cacheableService2 = new MlcCacheableService(
            additionalInterface: 'App\\Interface\\AnotherAdditionalInterface2',
            dataVersion: 'v2',
            cacheType: CacheTypeEnum::DEFAULT,
            inMemoryCacheMaxSize: 123,
        );
        $this->assertSame('App\\Interface\\AnotherAdditionalInterface2', $cacheableService2->getAdditionalInterface());
        $this->assertSame('v2', $cacheableService2->getDataVersion());
        $this->assertSame(CacheTypeEnum::DEFAULT, $cacheableService2->getCacheType());
        $this->assertSame(123, $cacheableService2->getInMemoryCacheMaxSize());
    }

    public function testFromReflectionClassWithoutAttribute(): void
    {
        $reflectionClass = new ReflectionClass(MlcCacheableMethodTest::class);
        $cacheableServiceAttribute = MlcCacheableService::fromReflectionClass($reflectionClass);
        $this->assertInstanceOf(MlcCacheableService::class, $cacheableServiceAttribute);
    }

    public function testFromReflectionClassWithAttribute(): void
    {
        $reflectionClass = new ReflectionClass(MlcCacheableServiceTest::class);
        $cacheableServiceAttribute = MlcCacheableService::fromReflectionClass($reflectionClass);
        $this->assertSame('App\\Interface\\MyAdditionalInterface', $cacheableServiceAttribute->getAdditionalInterface());
        $this->assertSame('v1', $cacheableServiceAttribute->getDataVersion());
        $this->assertSame(CacheTypeEnum::IN_MEMORY, $cacheableServiceAttribute->getCacheType());
        $this->assertSame(200, $cacheableServiceAttribute->getInMemoryCacheMaxSize());
    }

}
