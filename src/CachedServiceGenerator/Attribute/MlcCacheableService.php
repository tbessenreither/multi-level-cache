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
    public function __construct(
        private ?string $additionalInterface = null,
        private ?string $dataVersion = null,
        private CacheTypeEnum $cacheType = CacheTypeEnum::DEFAULT,
        private int $inMemoryCacheMaxSize = 100,
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

}
