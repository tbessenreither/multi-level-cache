<?php declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use InvalidArgumentException;
use ReflectionClass;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;


class KeyGeneratorService
{
	/**
	 * @var array<string, callable>
	 */
	private array $keyGeneratorCache = [];
	/**
	 * @var array<string, string>
	 */
	private array $cacheKeyPrefixCache = [];

	public static function getKey(MethodCallObject $methodCallObject): string
	{
		$cacheGeneratorCacheKey = $methodCallObject->getCacheGeneratorCacheKey();

		if (!isset(self::$keyGeneratorCache[$cacheGeneratorCacheKey])) {
			$reflectionClass = new ReflectionClass($methodCallObject->getClass());

			if (!$reflectionClass->hasMethod($methodCallObject->getMethod())) {
				throw new InvalidArgumentException("Method {$methodCallObject->getMethod()} does not exist in class {$methodCallObject->getClass()}");
			}

			$methodReflection = $reflectionClass->getMethod($methodCallObject->getMethod());

			$mlcCachableMethodAttribute = MlcCachableMethod::fromReflectionMethod($methodReflection);

			if ($mlcCachableMethodAttribute->hasKeyGenerator()) {
				self::$keyGeneratorCache[$cacheGeneratorCacheKey] = $mlcCachableMethodAttribute->getKeyGeneratorCallable();
			} else {
				self::$keyGeneratorCache[$cacheGeneratorCacheKey] = [self::class, 'defaultKeyGenerator'];
			}
		}

		return call_user_func(self::$keyGeneratorCache[$cacheGeneratorCacheKey], [$methodCallObject]);
	}

	public static function getKeyForMethod(MethodCallObject $methodCallObject): callable
	{
		$generatedFullKey = self::getKey($methodCallObject);
		$splitKeyParts = explode(':', $generatedFullKey);
		array_pop($splitKeyParts);

		return implode(':', $splitKeyParts) . ':*';
	}

	public static function getKeyForClass(MethodCallObject $methodCallObject): callable
	{
		$generatedFullKey = self::getKey($methodCallObject);
		$splitKeyParts = explode(':', $generatedFullKey);
		array_pop($splitKeyParts);
		array_pop($splitKeyParts);

		return implode(':', $splitKeyParts) . ':*';
	}

	private static function defaultKeyGenerator(MethodCallObject $methodCallObject): string
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

		return $cacheKeyPrefix . ':' . $methodCallObject->getMethod() . ':' . sha1(serialize($methodCallObject->getArguments()));
	}

}