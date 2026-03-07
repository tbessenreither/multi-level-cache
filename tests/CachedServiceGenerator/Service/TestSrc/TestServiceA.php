<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;

#[MlcCacheableService(dataVersion: 'v0.0.0')]
class TestServiceA
{
    public function testMethod(): string
    {
        return 'ok';
    }

    public function testMethodWithArgument(string $string): string
    {
        return 'ok';
    }

    #[MlcCacheableMethod(ttlSeconds: 3600)]
    public function cacheableTestMethod(): string
    {
        return 'ok';
    }

    #[MlcCacheableMethod(ttlSeconds: 3600, dataVersion: 'v1.0.0')]
    public function cacheableTestMethodWithDataVersion(): string
    {
        return 'ok';
    }
}
