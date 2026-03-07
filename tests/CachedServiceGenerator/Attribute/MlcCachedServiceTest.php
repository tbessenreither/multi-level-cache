<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachedService;

#[CoversClass(MlcCachedService::class)]
class MlcCachedServiceTest extends TestCase
{
    public function testSetupAndGetter(): void
    {
        $cachedService1 = new MlcCachedService(
            originalServiceClass: 'App\\Service\\MyOriginalService',
            allowSync: true,
        );
        $this->assertSame('App\\Service\\MyOriginalService', $cachedService1->getOriginalServiceClass());
        $this->assertTrue($cachedService1->isSyncAllowed());

        $cachedService2 = new MlcCachedService(
            originalServiceClass: 'App\\Service\\AnotherOriginalService',
            allowSync: false,
        );
        $this->assertSame('App\\Service\\AnotherOriginalService', $cachedService2->getOriginalServiceClass());
        $this->assertFalse($cachedService2->isSyncAllowed());
    }

}
