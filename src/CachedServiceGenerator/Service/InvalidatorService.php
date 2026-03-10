<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;
use Throwable;

class InvalidatorService
{
    private DirectRedisCacheService $directRedisCacheService;

    public function __construct(
        private MultiLevelCacheFactory $multiLevelCacheFactory,
    ) {
        $this->directRedisCacheService = $multiLevelCacheFactory->getImplementationRedisWithPrefix('mlc');
    }

    public function invalidateCacheForClass(string $classString): bool
    {
        try {
            $generateKey = KeyGeneratorService::getKeyPatternForClass(new MethodCallObject($classString, '', []));

            $this->directRedisCacheService->deleteByPattern($generateKey);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function invalidateCacheForMethod(string $classString, string $method): bool
    {
        try {
            $generateKey = KeyGeneratorService::getKeyPatternForMethod(new MethodCallObject($classString, $method, []));

            $this->directRedisCacheService->deleteByPattern($generateKey);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

}
