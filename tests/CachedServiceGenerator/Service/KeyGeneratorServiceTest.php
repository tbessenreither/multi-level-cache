<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\KeyGeneratorService;
use Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\KeyGeneratorTestClasses\TestServiceMissingPrefix;
use Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc\TestServiceA;
use Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc\TestServiceB;

#[CoversClass(KeyGeneratorService::class)]
#[UsesClass(MlcCacheableService::class)]
#[UsesClass(MlcCacheableMethod::class)]
#[UsesClass(MethodCallObject::class)]
class KeyGeneratorServiceTest extends TestCase
{
    public function testGetKeyDefault(): void
    {
        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: []
        );

        $key = KeyGeneratorService::getKey($methodCallObject);
        $this->assertIsString($key);
        $this->assertStringStartsWith('test_service_a:testMethod:', $key);
        $this->assertStringContainsString('NA', $key);
    }
    public function testGetKeyWithARgument(): void
    {
        $methodCallObject1 = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethodWithArgument',
            arguments: ['string_a']
        );

        $key1 = KeyGeneratorService::getKey($methodCallObject1);
        $this->assertIsString($key1);
        $this->assertStringStartsWith('test_service_a:testMethodWithArgument:', $key1);

        $methodCallObject2 = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethodWithArgument',
            arguments: ['string_b']
        );

        $key2 = KeyGeneratorService::getKey($methodCallObject2);
        $this->assertIsString($key2);
        $this->assertStringStartsWith('test_service_a:testMethodWithArgument:', $key2);

        $this->assertNotEquals($key1, $key2);
    }

    public function testGetKeyWithDataVersion(): void
    {
        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'cacheableTestMethodWithDataVersion',
            arguments: []
        );

        $key = KeyGeneratorService::getKey($methodCallObject);
        $this->assertIsString($key);
        $this->assertStringStartsWith('test_service_a:cacheableTestMethodWithDataVersion:', $key);
        $this->assertStringContainsString('v1.0.0', $key);
    }

    public function testGetKeyWithDefaultDataVersion(): void
    {
        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'cacheableTestMethod',
            arguments: []
        );

        $key = KeyGeneratorService::getKey($methodCallObject);
        $this->assertIsString($key);
        $this->assertStringStartsWith('test_service_a:cacheableTestMethod:', $key);
        $this->assertStringContainsString('v0.0.0', $key);
    }

    public function testGetKeyPatternForMethod(): void
    {
        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: []
        );

        $keyPattern = KeyGeneratorService::getKeyPatternForMethod($methodCallObject);
        $this->assertIsString($keyPattern);
        $this->assertEquals('test_service_a:testMethod:*', $keyPattern);
    }

    public function testGetKeyPatternForClass(): void
    {
        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: []
        );

        $keyPattern = KeyGeneratorService::getKeyPatternForClass($methodCallObject);
        $this->assertIsString($keyPattern);
        $this->assertEquals('test_service_a:*', $keyPattern);
    }

    public function testDefaultKeyGeneratorReflectionException(): void
    {
        $this->expectException(ReflectionException::class);
        $methodCallObject = new MethodCallObject(
            class: TestServiceB::class,
            method: 'testMethod',
            arguments: []
        );

        $keyPattern = KeyGeneratorService::defaultKeyGenerator($methodCallObject);

    }

    public function testKeyGeneratorPrefixCache(): void
    {
        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: []
        );

        // Call getKey twice and check that the cache is used
        $key1 = KeyGeneratorService::getKey($methodCallObject);
        $key2 = KeyGeneratorService::getKey($methodCallObject);

        $this->assertEquals($key1, $key2);
    }

    public function testDefaultKeyGeneratorMissingPrefix(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $methodCallObject = new MethodCallObject(
            class: TestServiceMissingPrefix::class,
            method: 'testMethod',
            arguments: []
        );

        $keyPattern = KeyGeneratorService::defaultKeyGenerator($methodCallObject);

    }

    public function testAdditionalCacheKeyBehaviour(): void
    {
        $methodCallObject1 = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: [],
        );

        $methodCallObject2 = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: [],
            additionalCacheKey: 'customKey',
        );

        $methodCallObject3 = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: [],
            additionalCacheKey: 'customKey2',
        );

        $key1 = KeyGeneratorService::getKey($methodCallObject1);
        $key2 = KeyGeneratorService::getKey($methodCallObject2);
        $key3 = KeyGeneratorService::getKey($methodCallObject3);
        $this->assertNotEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
        $this->assertNotEquals($key2, $key3);
    }
}
