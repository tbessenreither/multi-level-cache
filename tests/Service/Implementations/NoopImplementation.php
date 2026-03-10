<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service\Implementations;

use stdClass;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Interface\CacheInformationInterface;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;

class NoopImplementation implements MultiLevelCacheImplementationInterface, CacheInformationInterface
{
    public function set(string $key, CacheObjectWrapperDto $value): void
    {
    }

    public function get(string $key): CacheObjectWrapperDto
    {
        return new CacheObjectWrapperDto(new stdClass(), 0);
    }

    public function delete(string $key): void
    {
    }

    public function clear(): bool
    {
        return true;
    }

    public function getConfiguration(): array
    {
        return [
            "type" => 'noop',
        ];
    }

    public function getCachedKeys(): ?array
    {
        return null;
    }

}
