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

    public function invalidateEverything(): bool
    {
        try {
            $this->deleteByPattern('*');

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function invalidateCacheForNamespace(string $namespace): bool
    {
        $completedWithoutError = true;
        $invalidateKeyPatterns = [
            KeyGeneratorService::namespaceToKeyString($namespace, null, true) . ':*',
            KeyGeneratorService::namespaceToKeyString($namespace, null, false) . ':*',
        ];
        foreach ($invalidateKeyPatterns as $pattern) {
            try {
                $this->deleteByPattern($pattern);
            } catch (Throwable) {
                $completedWithoutError = false;
            }
        }

        return $completedWithoutError;
    }

    public function invalidateCacheForClass(string $classString): bool
    {
        try {
            $generateKey = KeyGeneratorService::getKeyPatternForClass(new MethodCallObject($classString, '', []));

            $this->deleteByPattern($generateKey);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function invalidateCacheForMethod(string $classString, string $method): bool
    {
        try {
            $generateKey = KeyGeneratorService::getKeyPatternForMethod(new MethodCallObject($classString, $method, []));

            $this->deleteByPattern($generateKey);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public function deleteByPattern(string $pattern): bool
    {
        try {
            $this->directRedisCacheService->deleteByPattern($pattern);

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

}
