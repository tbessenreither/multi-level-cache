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
        return $this->deleteByPattern('*');
    }

    public function invalidateCacheForNamespace(string $namespace): bool
    {
        $completedWithoutError = true;
        $invalidateKeyPatterns = [
            KeyGeneratorService::namespaceToKeyString($namespace, null, true) . ':*',
            KeyGeneratorService::namespaceToKeyString($namespace, null, false) . ':*',
        ];
        foreach ($invalidateKeyPatterns as $pattern) {

            $result = $this->deleteByPattern($pattern);
            if ($result === false) {
                $completedWithoutError = false;
            }
        }

        return $completedWithoutError;
    }

    public function invalidateCacheForClass(string $classString): bool
    {
        $generateKey = KeyGeneratorService::getKeyPatternForClass(new MethodCallObject($classString, '', []));

        return $this->deleteByPattern($generateKey);
    }

    public function invalidateCacheForMethod(string $classString, string $method): bool
    {
        $generateKey = KeyGeneratorService::getKeyPatternForMethod(new MethodCallObject($classString, $method, []));

        return $this->deleteByPattern($generateKey);
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
