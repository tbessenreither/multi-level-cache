<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Attribute;

use Closure;
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
        $this->assertNull($methodCallObject1->getAdditionalCacheKey());
        $this->assertEquals('App_Service_MyService', $methodCallObject1->getCachePrefix());
        $this->assertInstanceOf(Closure::class, $methodCallObject1->getCallable());

        $methodCallObject2 = new MethodCallObject(
            class: 'App\\Service\\AnotherService',
            method: 'anotherMethod',
            arguments: [],
            additionalCacheKey: 'customKey',
        );

        $this->assertSame('App\\Service\\AnotherService', $methodCallObject2->getClass());
        $this->assertSame('anotherMethod', $methodCallObject2->getMethod());
        $this->assertSame([], $methodCallObject2->getArguments());
        $this->assertSame('App\\Service\\AnotherService:anotherMethod', $methodCallObject2->getCacheGeneratorCacheKey());
        $this->assertSame('customKey', $methodCallObject2->getAdditionalCacheKey());
    }

    public function testClone(): void
    {
        $original = new MethodCallObject(
            class: 'App\\Service\\MyService',
            method: 'myMethod',
            arguments: ['arg1', 123],
        );

        $cloneComplete = $original->clone();
        $this->assertEquals($original, $cloneComplete);

        $cloneClass = $original->clone(class: 'App\\Service\\NewService');
        $this->assertEquals('App\\Service\\NewService', $cloneClass->getClass());
        $this->assertEquals($original->getMethod(), $cloneClass->getMethod());
        $this->assertEquals($original->getArguments(), $cloneClass->getArguments());

        $cloneMethod = $original->clone(method: 'myNewMethod');
        $this->assertEquals('myNewMethod', $cloneMethod->getMethod());
        $this->assertEquals($original->getClass(), $cloneMethod->getClass());
        $this->assertEquals($original->getArguments(), $cloneMethod->getArguments());

        $cloneArguments = $original->clone(arguments: ['newArg']);
        $this->assertEquals(['newArg'], $cloneArguments->getArguments());
        $this->assertEquals($original->getClass(), $cloneArguments->getClass());
        $this->assertEquals($original->getMethod(), $cloneArguments->getMethod());

    }

}
