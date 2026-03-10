<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Stopwatch\Stopwatch;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\KeyGeneratorService;
use Tbessenreither\MultiLevelCache\DataCollector\CacheStatistics;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Enum\BulkListTypeEnum;
use Tbessenreither\MultiLevelCache\Enum\ErrorEnum;
use Tbessenreither\MultiLevelCache\Enum\WarningEnum;
use Tbessenreither\MultiLevelCache\Exception\CacheBetaDecayException;
use Tbessenreither\MultiLevelCache\Interface\CacheInformationInterface;
use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;
use Throwable;

/**
 * The Core Service of our MultiLevelCache
 * It manages all configured cache levels and provides get, set and delete methods.
 * It also integrates with the Stopwatch component for performance monitoring and with a data collector for cache statistics.
 * It also provides a profiler panel integration via the MultiLevelCacheServiceCollector.
 * It supports adding a randomness to the TTL to avoid cache stampedes.
 * It supports an option to write to level 0 cache on set operations to improve cache hit rates.
 * All performance metrics will be turned off on non development environments to avoid overhead.
 */
class MultiLevelCacheService
{
    /**
     * @var positive-int
     */
    public const MAX_CACHE_LEVELS = 5;

    /**
     * @var CacheStatistics[]
     */
    private array $cacheStatistics = [];

    /**
     * @param array<int,MultiLevelCacheImplementationInterface> $caches
     * @param positive-int $ttlRandomnessSeconds amount of random seconds to add to ttl to avoid cache stampedes
     */
    public function __construct(
        private array $caches,
        private readonly bool $writeL0OnSet = true,
        private ?Stopwatch $stopwatch = null,
        private ?MultiLevelCacheDataCollector $cacheDataCollector = null,
        private int $ttlRandomnessSeconds = 0,
        private string $cacheGroupName = '',
        #[Autowire('%env(defined:MLC_DISABLE_READ)%')]
        private bool $cacheReadDisabled = false,
    ) {
        $this->constructorHelperCheckRequirements();
        $this->constructorHelperSetDefaults();
        $this->constructorHelperSetupDataCollector();
    }

    /**
     * stores the object in the lowest level cache
     * if writeL0OnSet is true, it also writes to level 0 cache.
     */
    public function set(string $key, object|string|int|float|bool|array|null $object, int $ttlSeconds): void
    {
        if (is_string($object)) {
            $this->raiseIssue(WarningEnum::WARNING_STORED_STRING_VALUE);
        }
        $this->startStopwatchEvent('set()');
        $cacheObject = new CacheObjectWrapperDto($object, $ttlSeconds + rand(0, $this->ttlRandomnessSeconds));

        $cacheLevels = $this->getCacheLevels();
        $highestLevelCache = end($cacheLevels);
        $this->writeToCacheLevel($highestLevelCache, $key, $cacheObject);

        if ($this->writeL0OnSet && count($cacheLevels) > 1) {
            $this->writeToCacheLevel(0, $key, $cacheObject);
        }

        $this->stopStopwatchEvent('set()');
    }

    /**
     * tries to get the object from all configured caches in order.
     * If not found, it calls the provided getter function to fetch the object.
     * When fetched from the getter, the result is stored in the lowest level cache.
     */
    public function get(string $key, ?callable $callable = null, int $ttlSeconds = 300): object|string|int|float|bool|array|null
    {
        $stopwatchEventName = 'get()';
        $this->startStopwatchEvent($stopwatchEventName);
        try {
            foreach ($this->getCacheLevels() as $cacheLevel) {
                $cacheObjectWrapper = $this->tryFetchFromCacheLevel($cacheLevel, $key);
                if ($cacheObjectWrapper !== null) {
                    $this->stopStopwatchEvent($stopwatchEventName);

                    return $cacheObjectWrapper->getObject();
                }
            }
        } catch (CacheBetaDecayException) {
            // treat beta decay as cache miss and continue to source
            $this->registerBetaDecay($key, $cacheLevel);
        }

        if ($callable !== null) {
            $callableResult = $this->getFromSource($key, $callable, $ttlSeconds);
        } else {
            $callableResult = null;
        }
        $this->stopStopwatchEvent($stopwatchEventName);

        return $callableResult;
    }

    public function getBulk(array $keys, ?callable $callable = null, MethodCallObject $methodCallObject, MlcCacheableMethod $mlcCacheableMethodAttribute): array
    {
        $this->raiseIssue(WarningEnum::WARNING_EXPERIMENTAL_FEATURE_BULK);

        if ($mlcCacheableMethodAttribute->getBulkConfig() === null) {
            $this->raiseIssue(ErrorEnum::WARNING_BULK_CONFIG_MISSING);

            return $this->get(KeyGeneratorService::getKey($methodCallObject), $callable, $mlcCacheableMethodAttribute->getTtlSeconds());
        }

        $responses = [];
        $requestsToSource = [];

        // First deconstruct into cached and non-cached requests
        foreach ($keys as $identifier) {
            $key = KeyGeneratorService::getKey($this->cloneBulkMethodCallObjectWithNewIdentifier($methodCallObject, $identifier));

            $cachedResponse = $this->get($key);
            if ($cachedResponse !== null) {
                $responses[] = $cachedResponse;
            } else {
                $requestsToSource[] = $identifier;
            }
        }

        // do one bulk call to source for all non-cached requests and cache them individually
        if (!empty($requestsToSource)) {
            $responsesFromSource = $this->getFromSource(
                key: $requestsToSource,
                callable: $callable,
                ttlSeconds: $mlcCacheableMethodAttribute->getTtlSeconds(),
            );

            foreach ($responsesFromSource as $response) {
                if (!is_array($response) && !is_object($response)) {
                    throw new RuntimeException('Bulk methods must return arrays of objects or arrays.');
                }

                $identifierForResponse = BulkMapperService::getIdentifierFromObjectResult($response, $mlcCacheableMethodAttribute->getBulkConfig()->getIdentifierSelector());
                $key = KeyGeneratorService::getKey($this->cloneBulkMethodCallObjectWithNewIdentifier($methodCallObject, $identifierForResponse));
                $this->set(
                    key: $key,
                    object: $response,
                    ttlSeconds: $mlcCacheableMethodAttribute->getTtlSeconds(),
                );

                $responses[] = $response;
            }
        }

        // Finally, map responses back to expected format
        if (BulkListTypeEnum::ARRAY_NUMERIC === $mlcCacheableMethodAttribute->getBulkConfig()->getListType()) {
            return BulkMapperService::mapArrayNumeric($responses);
        } elseif (BulkListTypeEnum::ARRAY_ASSOC === $mlcCacheableMethodAttribute->getBulkConfig()->getListType()) {
            return BulkMapperService::mapArrayAssoc($responses, $mlcCacheableMethodAttribute->getBulkConfig()->getIdentifierSelector());
        }

        throw new RuntimeException('Unsupported list type ' . $mlcCacheableMethodAttribute->getBulkConfig()->getListType()->name);
    }

    /**
     * deletes the object from all configured caches.
     */
    public function delete(string $key): void
    {
        $this->startStopwatchEvent('delete()');
        foreach ($this->getCacheLevels() as $cacheLevel) {
            $this->deleteFromCacheLevel($cacheLevel, $key);
        }
        $this->stopStopwatchEvent('delete()');
    }

    public function raiseIssue(DataCollectorIssueEnumInterface $issue): void
    {
        $this->cacheDataCollector?->raiseIssue($issue);
    }

    private function constructorHelperCheckRequirements(): void
    {
        if (empty($this->caches)) {
            throw new InvalidArgumentException('At least one cache implementation must be provided');
        } elseif (count($this->caches) > self::MAX_CACHE_LEVELS) {
            throw new InvalidArgumentException('Maximum number of cache levels exceeded: ' . self::MAX_CACHE_LEVELS);
        }

        if ($this->ttlRandomnessSeconds < 0) {
            throw new InvalidArgumentException('ttlRandomnessSeconds must be non-negative');
        }

        foreach ($this->caches as $level => $cache) {
            if (!$cache instanceof MultiLevelCacheImplementationInterface) {
                throw new InvalidArgumentException('All cache implementations must implement MultiLevelCacheImplementationInterface&TraceableAdapter');
            }
        }
    }

    private function constructorHelperSetDefaults(): void
    {
        if (empty($this->cacheGroupName)) {
            $this->cacheGroupName = substr(md5(spl_object_hash($this)), -6);
        }
    }

    private function constructorHelperSetupDataCollector(): void
    {
        if ($this->cacheDataCollector !== null && $this->cacheDataCollector->isCollecting()) {
            foreach ($this->caches as $level => $cache) {
                $this->cacheStatistics[$level] = new CacheStatistics();
                if ($cache instanceof CacheInformationInterface) {
                    $this->cacheStatistics[$level]->setConfigDataArray($cache->getConfiguration());
                }
                $this->cacheDataCollector->addInstance(
                    groupName: $this->cacheGroupName,
                    name: (new ReflectionClass($this->caches[$level]))->getShortName(),
                    cacheLevel: $level,
                    class: $this->caches[$level]::class,
                    statistics: $this->cacheStatistics[$level],
                );
            }
            $sourceStatisticsLevel = count($this->caches);
            $this->cacheStatistics[$sourceStatisticsLevel] = new CacheStatistics();
            $this->cacheDataCollector->addInstance(
                groupName: $this->cacheGroupName,
                name: 'Source',
                cacheLevel: count($this->caches),
                class: 'callable/closure',
                statistics: $this->cacheStatistics[$sourceStatisticsLevel],
            );
        }
    }

    private function tryFetchFromCacheLevel(int $cacheLevel, string $key): ?CacheObjectWrapperDto
    {
        if ($this->cacheReadDisabled()) {
            $this->raiseIssue(WarningEnum::WARNING_CACHE_READ_DISABLED);

            return null;
        }

        $cachedObject = $this->getFromCacheLevel($cacheLevel, $key);
        if ($cachedObject === null) {
            $this->registerCacheMiss($key, $cacheLevel);

            return null;
        } else {
            $this->registerCacheHit($key, $cacheLevel);
        }

        if ($cachedObject->isBetaDecayed()) {
            throw new CacheBetaDecayException('Cache object has beta decayed');
        }

        // populate higher level cache and level 0 if necessary
        if ($cacheLevel > 0) {
            $this->writeToCacheLevel($cacheLevel - 1, $key, $cachedObject);
        }
        if ($cacheLevel > 1) {
            $this->writeToCacheLevel(0, $key, $cachedObject);
        }

        if (is_string($cachedObject->getObject())) {
            $this->raiseIssue(WarningEnum::WARNING_STORED_STRING_VALUE);
        }

        return $cachedObject;
    }

    /**
     * returns all cache levels.
     *
     * @return array<int,int>
     */
    private function getCacheLevels(): array
    {
        return array_keys($this->caches);
    }

    /**
     * @param positive-int $cacheLevel
     */
    private function getCacheImplementation(int $cacheLevel): MultiLevelCacheImplementationInterface
    {
        if (!isset($this->caches[$cacheLevel])) {
            throw new InvalidArgumentException('Invalid cache level: ' . $cacheLevel);
        }

        return $this->caches[$cacheLevel];
    }

    /**
     * @param positive-int $cacheLevel
     */
    private function getStatisticsObject(int $cacheLevel): ?CacheStatistics
    {
        if (!isset($this->cacheStatistics[$cacheLevel])) {
            return null;
        }

        return $this->cacheStatistics[$cacheLevel];
    }

    /**
     * @param positive-int $cacheLevel
     */
    private function getFromCacheLevel(int $cacheLevel, string $key): ?CacheObjectWrapperDto
    {
        $this->startStopwatchEvent("getFromCacheLevel($cacheLevel)");
        $this->getStatisticsObject($cacheLevel)?->startTrackingRuntime();

        $cachedObject = $this->getCacheImplementation($cacheLevel)->get($key);

        $this->getStatisticsObject($cacheLevel)?->stopTrackingRuntime(CacheStatistics::TYPE_READ);
        $this->stopStopwatchEvent("getFromCacheLevel($cacheLevel)");

        return $cachedObject;
    }

    private function getFromSource(string|array $key, callable $callable, int $ttlSeconds): object|string|int|float|bool|array|null
    {
        $writeToCache = true;
        $statsKey = $key;
        if (is_array($key)) {
            $writeToCache = false;
            $statsKey = 'bulk';
        }
        $sourceStatisticsLevel = count($this->caches);
        $this->startStopwatchEvent('getFromSource()');
        $this->getStatisticsObject($sourceStatisticsLevel)?->startTrackingRuntime();

        try {
            $callableResult = $callable($key);
            if ($writeToCache) {
                $this->set(
                    key: $key,
                    object: $callableResult,
                    ttlSeconds: $ttlSeconds,
                );
            }
        } catch (Throwable $throwable) {
            $this->registerCacheMiss($statsKey, $sourceStatisticsLevel);

            throw $throwable;
        } finally {
            $this->getStatisticsObject($sourceStatisticsLevel)?->stopTrackingRuntime(CacheStatistics::TYPE_READ);
            $this->stopStopwatchEvent('getFromSource()');
        }

        $this->registerCacheHit($statsKey, $sourceStatisticsLevel);

        return $callableResult;
    }

    /**
     * @param positive-int $cacheLevel
     */
    private function writeToCacheLevel(int $cacheLevel, string $key, CacheObjectWrapperDto $object): void
    {
        $this->startStopwatchEvent("writeToCacheLevel($cacheLevel)");
        $this->getStatisticsObject($cacheLevel)?->startTrackingRuntime();

        $this->getCacheImplementation($cacheLevel)->set($key, $object);

        $this->getStatisticsObject($cacheLevel)?->stopTrackingRuntime(CacheStatistics::TYPE_WRITE);
        $this->stopStopwatchEvent("writeToCacheLevel($cacheLevel)");

        $this->getStatisticsObject($cacheLevel)?->addCachedKey($key);
    }

    /**
     * @param positive-int $cacheLevel
     */
    private function deleteFromCacheLevel(int $cacheLevel, string $key): void
    {
        $this->startStopwatchEvent("deleteFromCacheLevel($cacheLevel)");
        $this->getStatisticsObject($cacheLevel)?->startTrackingRuntime();

        $this->getCacheImplementation($cacheLevel)->delete($key);

        $this->getStatisticsObject($cacheLevel)?->stopTrackingRuntime(CacheStatistics::TYPE_WRITE);
        $this->stopStopwatchEvent("deleteFromCacheLevel($cacheLevel)");

        $this->getStatisticsObject($cacheLevel)?->deleteCachedKey($key);
    }

    private function startStopwatchEvent(string $name): void
    {
        if ($this->stopwatch !== null && $this->cacheDataCollector?->isCollecting()) {
            $this->stopwatch->start($this->cacheGroupName . ':' . $name, 'MultiLevelCacheService');
        }
    }

    private function stopStopwatchEvent(string $name): void
    {
        if ($this->stopwatch !== null && $this->cacheDataCollector?->isCollecting()) {
            $this->stopwatch->stop($this->cacheGroupName . ':' . $name);
        }
    }

    private function registerCacheHit(string $key, int $cacheLevel): void
    {
        $this->cacheDataCollector?->registerCacheHit($this->cacheGroupName, $cacheLevel, $key);
    }

    private function registerCacheMiss(string $key, int $cacheLevel): void
    {
        $this->cacheDataCollector?->registerCacheMiss($this->cacheGroupName, $cacheLevel, $key);
    }

    private function registerBetaDecay(string $key, int $cacheLevel): void
    {
        $this->cacheDataCollector?->registerBetaDecay($this->cacheGroupName, $cacheLevel, $key);
    }

    private function cacheReadDisabled(): bool
    {
        return $this->cacheReadDisabled;
    }

    private function cloneBulkMethodCallObjectWithNewIdentifier(MethodCallObject $methodCallObject, string $newIdentifier): MethodCallObject
    {
        $arguments = $methodCallObject->getArguments();
        $arguments[0] = $newIdentifier;

        return $methodCallObject->clone(arguments: $arguments);
    }

}
