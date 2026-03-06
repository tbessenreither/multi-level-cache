<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;

class InvalidatorService
{
    private DirectRedisCacheService $directRedisCacheService;

    public function __construct(
        private MultiLevelCacheFactory $multiLevelCacheFactory,
    ) {
        $this->directRedisCacheService = $multiLevelCacheFactory->getImplementationRedisWithPrefix('mlc');
    }

    public function invalidateCacheForClass(string $classString): void
    {
        $generateKey = KeyGeneratorService::getKeyPatternForClass(new MethodCallObject($classString, '', []));

        $this->directRedisCacheService->deleteByPattern($generateKey);
    }

    public function invalidateCacheForMethod(string $classString, string $method): void
    {
        $generateKey = KeyGeneratorService::getKeyPatternForMethod(new MethodCallObject($classString, $method, []));

        $this->directRedisCacheService->deleteByPattern($generateKey);
    }

}
