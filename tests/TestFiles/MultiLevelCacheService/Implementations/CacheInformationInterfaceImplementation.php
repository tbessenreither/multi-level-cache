<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\MultiLevelCacheService\Implementations;

use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Interface\CacheInformationInterface;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;

class CacheInformationInterfaceImplementation implements MultiLevelCacheImplementationInterface, CacheInformationInterface
{
    public function getCachedKeys(): null
    {
        return null;
    }

    public function getConfiguration(): array
    {
        return ['type' => 'dummy'];
    }

    public function clear(): bool
    {
        return true;
    }

    public function delete(string $key): void
    {
        // Not implemented for this test
    }

    public function get(string $key): ?CacheObjectWrapperDto
    {
        return null;
    }

    public function set(string $key, CacheObjectWrapperDto $object): void
    {
        // Not implemented for this test
    }

}
