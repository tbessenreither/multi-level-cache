<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Factory;

use Redis;
use RedisCluster;
use SensitiveParameter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Stopwatch\Stopwatch;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\Enum\CacheTypeEnum;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;
use Tbessenreither\MultiLevelCache\Interface\RedisClientProviderInterface;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;
use Tbessenreither\MultiLevelCache\Service\Implementations\InMemoryCacheService;
use Tbessenreither\MultiLevelCache\Service\MultiLevelCacheService;
use Throwable;

/**
 * This Factory has methods to generate the most commonly used cache configurations for you.
 * No need to setup everything by yourself
 * It will also handle the Redis connection setup and error handling for you.
 */
class MultiLevelCacheFactory
{
    private RedisClientProviderInterface $redisClientProvider;

    public function __construct(
        #[Autowire('%env(REDIS_DSN)%')]
        #[SensitiveParameter]
        readonly ?string $redisDsn = null,
        private readonly ?Stopwatch $stopwatch = null,
        private readonly ?MultiLevelCacheDataCollector $cacheDataCollector = null,
        #[Autowire('%env(defined:MLC_DISABLE_READ)%')]
        private bool $cacheReadDisabled = false,
        #[Autowire(service: 'tbessenreither.multi_level_cache.redis_client_provider')]
        Redis|RedisCluster|RedisClientProviderInterface|null $redisClient = null,
    ) {
        try {
            $this->redisClientProvider = new RedisClientFactory(
                redisClient: $redisClient instanceof Redis || $redisClient instanceof RedisCluster ? $redisClient : null,
                redisDsn: $redisDsn,
                redisClientProvider: $redisClient instanceof RedisClientProviderInterface ? $redisClient : null,
            );
        } catch (Throwable $e) {
            throw new CacheConnectionException('Invalid Redis configuration given.', 0, $e);
        }
    }

    public function createByType(
        CacheTypeEnum $type,
        int $inMemoryCacheMaxSize = 100,
        string $redisKeyPrefix = 'mlc',
        bool $writeL0OnSet = false,
        string $cacheGroupName = '',
    ): MultiLevelCacheService {
        return match ($type) {
            CacheTypeEnum::IN_MEMORY => $this->createInMemoryOnlyCache($inMemoryCacheMaxSize, $writeL0OnSet, $cacheGroupName),
            CacheTypeEnum::REDIS => $this->createRedisOnlyCache($redisKeyPrefix, $writeL0OnSet, $cacheGroupName),
            CacheTypeEnum::DEFAULT => $this->createDefault2LevelCache($inMemoryCacheMaxSize, $redisKeyPrefix, $writeL0OnSet, $cacheGroupName),
        };
    }

    public function createDefault2LevelCache(
        int $inMemoryCacheMaxSize = 100,
        string $redisKeyPrefix = 'mlc',
        bool $writeL0OnSet = false,
        string $cacheGroupName = '',
    ): MultiLevelCacheService {
        return new MultiLevelCacheService(
            caches: [
                $this->getImplementationInMemory($inMemoryCacheMaxSize),
                $this->getImplementationRedis($this->redisClientProvider, $redisKeyPrefix),
            ],
            writeL0OnSet: $writeL0OnSet,
            stopwatch: $this->stopwatch,
            cacheDataCollector: $this->cacheDataCollector,
            cacheGroupName: $cacheGroupName,
            cacheReadDisabled: $this->cacheReadDisabled,
        );
    }

    public function createInMemoryOnlyCache(
        int $inMemoryCacheMaxSize = 100,
        bool $writeL0OnSet = false,
        string $cacheGroupName = '',
    ): MultiLevelCacheService {
        return new MultiLevelCacheService(
            caches: [
                $this->getImplementationInMemory($inMemoryCacheMaxSize),
            ],
            writeL0OnSet: $writeL0OnSet,
            stopwatch: $this->stopwatch,
            cacheDataCollector: $this->cacheDataCollector,
            cacheGroupName: $cacheGroupName,
            cacheReadDisabled: $this->cacheReadDisabled,
        );
    }

    public function createRedisOnlyCache(
        string $redisKeyPrefix = 'mlc',
        bool $writeL0OnSet = false,
        string $cacheGroupName = '',
    ): MultiLevelCacheService {
        return new MultiLevelCacheService(
            caches: [
                $this->getImplementationRedis($this->redisClientProvider, $redisKeyPrefix),
            ],
            writeL0OnSet: $writeL0OnSet,
            stopwatch: $this->stopwatch,
            cacheDataCollector: $this->cacheDataCollector,
            cacheGroupName: $cacheGroupName,
            cacheReadDisabled: $this->cacheReadDisabled,
        );
    }

    public function getImplementationInMemory(int $maxSize): InMemoryCacheService
    {
        return new InMemoryCacheService(
            maxSize: $maxSize,
        );
    }

    public function getImplementationRedisWithPrefix(string $keyPrefix): DirectRedisCacheService
    {
        return $this->getImplementationRedis($this->redisClientProvider, $keyPrefix);
    }

    public function getImplementationRedis(Redis|RedisCluster|RedisClientProviderInterface $redisClient, string $keyPrefix): DirectRedisCacheService
    {
        if ($redisClient instanceof RedisClientProviderInterface) {
            $redisClientProvider = $redisClient;
        } elseif ($redisClient instanceof Redis || $redisClient instanceof RedisCluster) {
            $redisClientProvider = new RedisClientFactory(redisClient: $redisClient);
        }

        return new DirectRedisCacheService(
            redisClientProvider: $redisClientProvider,
            keyPrefix: $keyPrefix,
        );
    }

}
