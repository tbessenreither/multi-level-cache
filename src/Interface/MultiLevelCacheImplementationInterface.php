<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Interface;

use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;

interface MultiLevelCacheImplementationInterface
{
    /**
     * Set a value in the cache.
     */
    public function set(string $key, CacheObjectWrapperDto $object): void;

    /**
     * Get a value with key $key from the cache.
     */
    public function get(string $key): ?CacheObjectWrapperDto;

    /**
     * Delete a value with key $key from the cache.
     */
    public function delete(string $key): void;

    /**
     * Clear the entire cache. Depending on the implementation and setup some adapters may not be able to clear the data.
     */
    public function clear(): bool;

}
