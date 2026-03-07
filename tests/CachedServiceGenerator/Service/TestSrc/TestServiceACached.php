<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachedService;

#[MlcCachedService(
    originalServiceClass: TestServiceACached::class,
    allowSync: true,
)]
class TestServiceACached
{
    public const CACHE_KEY_PREFIX = 'test_service_a';
    public function testMethod(): string
    {
        return 'ok';
    }
}
