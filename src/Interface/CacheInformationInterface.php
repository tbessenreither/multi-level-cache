<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Interface;

interface CacheInformationInterface
{
    /**
     * Returns an associative array of configuration settings, describing the cache setup.
     * @return array<string, int|float|string|bool>
     */
    public function getConfiguration(): array;

    /**
     * Returns a list of all keys currently stored in the cache, or null if not supported.
     * @return array<int, string>|null
     */
    public function getCachedKeys(): ?array;

}
