<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Attribute;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\Dto\BulkConfig;
use Tbessenreither\MultiLevelCache\Enum\BulkListTypeEnum;

#[CoversClass(MlcCacheableMethod::class)]
#[UsesClass(BulkConfig::class)]
class MlcCacheableMethodTest extends TestCase
{
    public function testSetupAndGetter(): void
    {
        $cacheableMethod = new MlcCacheableMethod(3600, );
        $this->assertSame(3600, $cacheableMethod->getTtlSeconds());
        $this->assertNull($cacheableMethod->getDataVersion());

        $cacheableMethodWithVersion = new MlcCacheableMethod(3600, 'v1');
        $this->assertSame(3600, $cacheableMethodWithVersion->getTtlSeconds());
        $this->assertSame('v1', $cacheableMethodWithVersion->getDataVersion());

        $reflectionClass = new ReflectionClass(MlcCacheableMethodTest::class);
        $reflectionMethod = $reflectionClass->getMethod('hereForTestingGetByReflection');
        $attributeFromReflectionMethod = MlcCacheableMethod::fromReflectionMethod($reflectionMethod, false);
        $this->assertSame(3600, $attributeFromReflectionMethod->getTtlSeconds());
        $this->assertSame('v1', $attributeFromReflectionMethod->getDataVersion());

        $reflectionMethodWithoutAttribute = $reflectionClass->getMethod('hereForTestingGetByReflectionWithoutAttribute');
        $attributeFromReflectionMethodWithoutAttribute = MlcCacheableMethod::fromReflectionMethod($reflectionMethodWithoutAttribute, false);
        $this->assertNull($attributeFromReflectionMethodWithoutAttribute);

        $cacheableMethodWithTtlOne = new MlcCacheableMethod(1);
        $this->assertSame(1, $cacheableMethodWithTtlOne->getTtlSeconds());

        $bulkConfig = new BulkConfig('id', BulkListTypeEnum::ARRAY_ASSOC);
        $cacheableMEthodWithBulkConfig = new MlcCacheableMethod(3600, null, $bulkConfig);
        $this->assertSame(3600, $cacheableMEthodWithBulkConfig->getTtlSeconds());
        $this->assertInstanceOf(BulkConfig::class, $cacheableMEthodWithBulkConfig->getBulkConfig());
        $this->assertSame($bulkConfig, $cacheableMEthodWithBulkConfig->getBulkConfig());
    }

    public function testInvalidTtlNegative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MlcCacheableMethod(-1);
    }

    public function testInvalidTtlZero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new MlcCacheableMethod(0);
    }

    public function testFromReflectionMethodWithoutAttributeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $reflectionClass = new ReflectionClass(MlcCacheableMethodTest::class);
        $reflectionMethodWithoutAttribute = $reflectionClass->getMethod('hereForTestingGetByReflectionWithoutAttribute');
        MlcCacheableMethod::fromReflectionMethod($reflectionMethodWithoutAttribute, true);
    }

    #[MlcCacheableMethod(ttlSeconds: 3600, dataVersion: 'v1')]
    private function hereForTestingGetByReflection(): void
    {
        // This method exists solely to be tested via reflection in the CachedServiceGeneratorTest.
    }

    private function hereForTestingGetByReflectionWithoutAttribute(): void
    {
        // This method exists solely to be tested via reflection in the CachedServiceGeneratorTest.
    }

}
