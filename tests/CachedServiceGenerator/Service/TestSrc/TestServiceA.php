<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;

#[MlcCacheableService(dataVersion: 'v0.0.0')]
class TestServiceA
{
    public static bool $sourceWasCalled = false;
    public static array $entriesRequestedFromSource = [];

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

    public static function bulkTestFunction(array $keys)
    {
        self::$sourceWasCalled = true;
        self::$entriesRequestedFromSource = $keys;
        $values = [];
        foreach ($keys as $key) {
            $values[] = [
                'key' => $key,
                'value' => 'value for ' . $key,
            ];
        }

        return $values;
    }

    public static function bulkTestFunctionWithIntIdentifier(array $keys)
    {
        self::$sourceWasCalled = true;
        self::$entriesRequestedFromSource = $keys;
        $values = [];
        $i = 1;
        foreach ($keys as $key) {
            $values[] = [
                'key' => $i++,
                'value' => 'value for ' . $key,
            ];
        }

        return $values;
    }

    public static function bulkTestFunctionWithWrongResponse(array $keys)
    {
        $response = [];
        foreach ($keys as $key) {
            $response[$key] = 'value for ' . $key;
        }

        return $response;
    }

    public static function resetStatic(): void
    {
        self::$sourceWasCalled = false;
        self::$entriesRequestedFromSource = [];
    }
}
