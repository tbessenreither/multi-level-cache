<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service\Implementations;

use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;
use Tbessenreither\MultiLevelCache\Interface\CacheInformationInterface;

/**
 * Simple In-Memory Cache Implementation with size and TTL limits.
 * This adapter is useful for fast, temporary caching within a single request or process.
 * It does not persist data between requests and is not shared across multiple instances.
 * If you have to do a lot of small lookups with repeating data i.e. during data processing in a loop, this cache implementation can help to reduce overhead.
 * As it's very fast this should almost always be the first level cache in a multi-level cache setup.
 * Key eviction will priorotize expired items first, then the least recently used items.
 */
class InMemoryCacheService implements MultiLevelCacheImplementationInterface, CacheInformationInterface
{
    /** @var CacheObjectWrapperDto[] */
    private array $objectCache = [];

    public function __construct(
        private int $maxSize = 100,
    ) {
    }

    public function set(string $key, CacheObjectWrapperDto $object): void
    {
        $this->objectCache[$key] = $object;
        $this->ensureCacheSizeLimit();
    }

    public function get(string $key): ?CacheObjectWrapperDto
    {
        if (!isset($this->objectCache[$key])) {
            return null;
        }

        if ($this->objectCache[$key]->isExpired()) {
            $this->delete($key);
            $this->ensureCacheSizeLimit();

            return null;
        }

        $this->markKeyAsUsed($key);

        return $this->objectCache[$key];
    }

    public function delete(string $key): void
    {
        unset($this->objectCache[$key]);
    }

    public function clear(): bool
    {
        $this->objectCache = [];
        return true;
    }

    public function getAllCacheKeys(): array
    {
        return array_keys($this->objectCache);
    }

    public function getConfiguration(): array
    {
        return [
            'maxSize' => $this->maxSize,
        ];
    }

    public function getCachedKeys(): array
    {
        return $this->getAllCacheKeys();
    }

    private function ensureCacheSizeLimit(): void
    {
        foreach ($this->objectCache as $key => $cacheObject) {
            if (!$cacheObject->isExpired()) {
                continue;
            }
            unset($this->objectCache[$key]);
        }
        while (count($this->objectCache) > $this->maxSize) {
            array_shift($this->objectCache);
        }
    }

    private function markKeyAsUsed(string $key): void
    {
        $object = $this->objectCache[$key];
        unset($this->objectCache[$key]);
        $this->objectCache[$key] = $object;
    }

}
