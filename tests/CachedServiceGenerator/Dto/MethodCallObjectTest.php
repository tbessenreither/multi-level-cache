<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Attribute;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;

#[CoversClass(MethodCallObject::class)]
class MethodCallObjectTest extends TestCase
{
    public function testSetupAndGetter(): void
    {
        $methodCallObject1 = new MethodCallObject(
            class: 'App\\Service\\MyService',
            method: 'myMethod',
            arguments: ['arg1', 123],
        );

        $this->assertSame('App\\Service\\MyService', $methodCallObject1->getClass());
        $this->assertSame('myMethod', $methodCallObject1->getMethod());
        $this->assertSame(['arg1', 123], $methodCallObject1->getArguments());
        $this->assertSame('App\\Service\\MyService:myMethod', $methodCallObject1->getCacheGeneratorCacheKey());

        $methodCallObject2 = new MethodCallObject(
            class: 'App\\Service\\AnotherService',
            method: 'anotherMethod',
            arguments: [],
        );

        $this->assertSame('App\\Service\\AnotherService', $methodCallObject2->getClass());
        $this->assertSame('anotherMethod', $methodCallObject2->getMethod());
        $this->assertSame([], $methodCallObject2->getArguments());
        $this->assertSame('App\\Service\\AnotherService:anotherMethod', $methodCallObject2->getCacheGeneratorCacheKey());
    }

}
