<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Attribute;
use InvalidArgumentException;
use ReflectionMethod;
use Tbessenreither\MultiLevelCache\Dto\BulkConfig;

#[Attribute(Attribute::TARGET_METHOD)]
class MlcCacheableMethod
{
    /**
     * @param int $ttlSeconds Classic TTL
     * @param string|null $dataVersion This is used to differentiate cache keys when the data structure changes. You can use it to force cache invalidation when you deploy a new version of your code that is not compatible with the old cached data.
     * @param BulkConfig|null $bulkConfig Configuration for bulk operations. See Documentation for details
     * @param string|null $additionalCacheKeyGetter A method name as string that will return serializable content that's added during cache key generation. (everything important that isn't passed to the method as an argument)
     * @throws InvalidArgumentException if ttlSeconds is not a positive integer
     */
    public function __construct(
        private int $ttlSeconds,
        private ?string $dataVersion = null,
        private ?BulkConfig $bulkConfig = null,
        private ?string $additionalCacheKeyGetter = null,
    ) {
        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException('TTL must be a positive integer.');
        }
    }

    public static function fromReflectionMethod(ReflectionMethod $method, bool $throw = true): ?self
    {
        $attributes = $method->getAttributes(self::class);
        if (empty($attributes)) {
            if ($throw) {
                throw new InvalidArgumentException("Method {$method->getName()} is not marked as MlcCacheableMethod");
            }

            return null;
        }

        return $attributes[0]->newInstance();
    }

    public function getTtlSeconds(): int
    {
        return $this->ttlSeconds;
    }

    public function getDataVersion(): ?string
    {
        return $this->dataVersion;
    }

    public function getBulkConfig(): ?BulkConfig
    {
        return $this->bulkConfig;
    }

    public function getAdditionalCacheKeyGetter(): mixed
    {
        return $this->additionalCacheKeyGetter;
    }
}
