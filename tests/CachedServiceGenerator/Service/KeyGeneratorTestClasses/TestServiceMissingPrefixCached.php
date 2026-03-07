<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\KeyGeneratorTestClasses;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachedService;

#[MlcCachedService(
    originalServiceClass: TestServiceMissingPrefix::class,
    allowSync: true,
)]
class TestServiceMissingPrefixCached
{
    public function testMethod(): string
    {
        return 'ok';
    }
}
