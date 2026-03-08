<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service\Implementations;

use Exception;
use Redis;
use RedisCluster;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;
use Tbessenreither\MultiLevelCache\Interface\CacheInformationInterface;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;

/**
 * This should be the prefered way to connect to Redis for caching, as it avoids the overhead of our common cache adapter.
 * It uses direct Redis commands to store and retrieve serialized cache objects.
 * It allows the use of key prefixes without the project-wide prefixing that the common Redis cache adapter enforces.
 * This way we can share Redis Cache entries with other applications if needed.
 */
class DirectRedisCacheService implements MultiLevelCacheImplementationInterface, CacheInformationInterface
{
    public function __construct(
        private Redis|RedisCluster $redisClient,
        private ?string $keyPrefix = null,
    ) {
        if ($this->redisClient instanceof Redis && $this->redisClient->isConnected() === false) {
            throw new CacheConnectionException('Could not connect to Redis');
        }
    }

    public function set(string $key, CacheObjectWrapperDto $object): void
    {
        $this->redisClient->set($this->getPrefixedRedisCacheKey($key), serialize($object), ['ex' => $object->getTtlLeft()]);
    }

    public function get(string $key): ?CacheObjectWrapperDto
    {
        try {
            $serializedData = $this->redisClient->get($this->getPrefixedRedisCacheKey($key));
            if ($serializedData === false) {
                return null;
            }

            $object = @unserialize($serializedData);
            if ($object === false) {
                return null;
            }

            return $object;
        } catch (Exception) {
            return null;
        }
    }

    public function delete(string $key): void
    {
        $this->redisClient->del($this->getPrefixedRedisCacheKey($key));
    }

    public function clear(): bool
    {
        if (empty($this->keyPrefix)) {
            // we can't handle clearing without a prefix to avoid accidental mass deletions
            return false;
        }

        $redisClient = $this->redisClient;
        $deletePattern = $this->keyPrefix . ':*';

        $iterator = null;
        do {
            $keys = $redisClient->scan($iterator, $deletePattern);
            foreach ($keys as $key) {
                $redisClient->del($key);
            }
        } while ($iterator != 0);

        return true;
    }

    public function deleteByPattern(string $pattern): int
    {
        $redisClient = $this->redisClient;
        $deletePattern = $this->getPrefixedRedisCacheKey($pattern);

        $iterator = null;
        $deletedItemCount = 0;
        do {
            $keys = $redisClient->scan($iterator, $deletePattern);
            foreach ($keys as $key) {
                $redisClient->del($key);
                $deletedItemCount++;
            }
        } while ($iterator != 0);

        return $deletedItemCount;
    }

    public function getConfiguration(): array
    {
        if ($this->redisClient instanceof RedisCluster) {
            return [
                'prefix' => $this->keyPrefix,
                'cacheAdapter' => $this->redisClient::class,
                'redisHost' => null,
                'redisPort' => null,
                'serialization' => 'php_serialize',
            ];
        } else {
            return [
                'prefix' => $this->keyPrefix,
                'cacheAdapter' => $this->redisClient::class,
                'redisHost' => $this->redisClient->getHost(),
                'redisPort' => $this->redisClient->getPort(),
                'serialization' => 'php_serialize',
            ];
        }
    }

    private function getPrefixedRedisCacheKey(string $key): string
    {
        $keyTrimmed = trim($key);
        $keyCleaned = preg_replace('/\s+/', '_', $keyTrimmed);
        if (empty($this->keyPrefix)) {
            return $keyCleaned;
        }

        return $this->keyPrefix . ':' . $keyCleaned;
    }

    public function getCachedKeys(): ?array
    {
        return null;
    }

}
