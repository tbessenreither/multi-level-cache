<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MakeCachedServiceService;
use Attribute;
use ReflectionClass;

/**
 * To mark a service as cacheable.
 */
#[Attribute(Attribute::TARGET_CLASS)]


class MlcCacheableService
{
    private int $defaultTtlSeconds;

    public function __construct(
        ?int $defaultTtlSeconds = null,
        private ?string $additionalInterface = null,
        private ?string $dataVersion = null,
    ) {
        $this->defaultTtlSeconds = $defaultTtlSeconds ?? MakeCachedServiceService::DEFAULT_TTL_SECONDS;
    }

    public static function fromReflectionClass(ReflectionClass $class): ?self
    {
        $attributes = $class->getAttributes(self::class);
        if (empty($attributes)) {
            return null;
        }

        return $attributes[0]->newInstance();
    }

    public function getDefaultTtlSeconds(): int
    {
        return $this->defaultTtlSeconds;
    }

    public function getAdditionalInterface(): ?string
    {
        return $this->additionalInterface;
    }

    public function getDataVersion(): ?string
    {
        return $this->dataVersion;
    }

}
