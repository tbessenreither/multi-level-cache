<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Attribute;
use ReflectionClass;
use Tbessenreither\MultiLevelCache\Enum\CacheTypeEnum;

/**
 * To mark a service as cacheable.
 */
#[Attribute(Attribute::TARGET_CLASS)]


class MlcCacheableService
{
    public const int DEFAULT_MAX_SIZE_IN_MEMORY_CACHE = 100;

    /**
     * @param string|null $additionalInterface This interface will be added to the Cached Service class, so you can use it for type hinting. It must be a string with the full namespace of the interface.
     * @param string|null $dataVersion This is used to differentiate cache keys when the data structure changes. You can use it to force cache invalidation when you deploy a new version of your code that is not compatible with the old cached data.
     * @param CacheTypeEnum $cacheType What cache preset to use
     * @param int $inMemoryCacheMaxSize The maximum number of items to store in the in-memory cache. This is only relevant if the cache type includes an in-memory cache layer. The default value is 100.
     * @param string|null $additionalCacheKeyGetter A method name as string that will return serializable content that's added during cache key generation. (everything important that isn't passed to the method as an argument)
     */
    public function __construct(
        private ?string $additionalInterface = null,
        private ?string $dataVersion = null,
        private CacheTypeEnum $cacheType = CacheTypeEnum::DEFAULT,
        private int $inMemoryCacheMaxSize = self::DEFAULT_MAX_SIZE_IN_MEMORY_CACHE,
        private ?string $additionalCacheKeyGetter = null,
    ) {
    }

    public static function fromReflectionClass(ReflectionClass $class): self
    {
        $attributes = $class->getAttributes(self::class);
        if (empty($attributes)) {
            return new self();
        }

        return $attributes[0]->newInstance();
    }

    public function getAdditionalInterface(): ?string
    {
        return $this->additionalInterface;
    }

    public function getDataVersion(): ?string
    {
        return $this->dataVersion;
    }

    public function getCacheType(): CacheTypeEnum
    {
        return $this->cacheType;
    }

    public function getInMemoryCacheMaxSize(): int
    {
        return $this->inMemoryCacheMaxSize;
    }

    public function getAdditionalCacheKeyGetter(): ?string
    {
        return $this->additionalCacheKeyGetter;
    }

}
