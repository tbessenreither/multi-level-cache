<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Throwable;

class KeyGeneratorService
{
    /**
     * @var array<string, string>
     */
    private static array $cacheKeyPrefixCache = [];
    /**
     * @var array<string, string>
     */
    private static array $dataVersionCache = [];

    public static function getKey(MethodCallObject $methodCallObject, bool $throw = true): string
    {
        return self::defaultKeyGenerator($methodCallObject);
    }

    public static function getKeyPatternForMethod(MethodCallObject $methodCallObject): string
    {
        $generatedFullKey = self::getKey($methodCallObject);
        $splitKeyParts = explode(':', $generatedFullKey);
        array_pop($splitKeyParts);
        array_pop($splitKeyParts);

        return implode(':', $splitKeyParts) . ':*';
    }

    public static function getKeyPatternForClass(MethodCallObject $methodCallObject): string
    {
        $generatedFullKey = self::getKey($methodCallObject, false);
        $splitKeyParts = explode(':', $generatedFullKey);
        array_pop($splitKeyParts);
        array_pop($splitKeyParts);
        array_pop($splitKeyParts);

        return implode(':', $splitKeyParts) . ':*';
    }

    public static function defaultKeyGenerator(MethodCallObject $methodCallObject): string
    {
        if (!isset(self::$cacheKeyPrefixCache[$methodCallObject->getClass()])) {
            $cachedClassString = $methodCallObject->getClass() . 'Cached';
            $cachedServiceReflection = new ReflectionClass($cachedClassString);
            $cacheKeyPrefix = $cachedServiceReflection->getConstant('CACHE_KEY_PREFIX');
            if ($cacheKeyPrefix === false) {
                throw new InvalidArgumentException("CACHE_KEY_PREFIX constant not found in class {$cachedClassString}");
            }
            self::$cacheKeyPrefixCache[$methodCallObject->getClass()] = $cacheKeyPrefix;
        } else {
            $cacheKeyPrefix = self::$cacheKeyPrefixCache[$methodCallObject->getClass()];
        }

        $dataVersion = self::getDataVersion($methodCallObject);

        return $cacheKeyPrefix . ':' . $methodCallObject->getMethod() . ':DataVersion_' . $dataVersion . ':' . sha1(serialize($methodCallObject->getArguments()));
    }

    private static function getDataVersion(MethodCallObject $methodCallObject): string
    {
        $class = $methodCallObject->getClass();
        $method = $methodCallObject->getMethod();

        $cacheKey = $class . '::' . $method;

        if (isset(self::$dataVersionCache[$cacheKey])) {
            return self::$dataVersionCache[$cacheKey];
        }

        $dataVersionString = 'NA';

        try {
            $reflectionClass = new ReflectionClass($methodCallObject->getClass());
            $mlcCacheableServiceAttribute = MlcCacheableService::fromReflectionClass(class: $reflectionClass);

            $methodReflection = $reflectionClass->getMethod($methodCallObject->getMethod());
            $mlcCacheableMethodAttribute = MlcCacheableMethod::fromReflectionMethod(method: $methodReflection, throw: true);

            if ($mlcCacheableMethodAttribute->getDataVersion() !== null) {
                $dataVersionString = $mlcCacheableMethodAttribute->getDataVersion();
            } elseif ($mlcCacheableServiceAttribute->getDataVersion() !== null) {
                $dataVersionString = $mlcCacheableServiceAttribute->getDataVersion();
            } else {
                throw new RuntimeException('No data version found for method ' . $methodCallObject->getMethod() . ' in class ' . $methodCallObject->getClass());
            }
        } catch (Throwable) {
            $dataVersionString = 'NA';
        }

        $dataVersionString = str_replace(':', '_', $dataVersionString);

        self::$dataVersionCache[$cacheKey] = $dataVersionString;

        return $dataVersionString;
    }

}
