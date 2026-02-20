<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MakeCachedServiceService;
use Attribute;

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
    ) {
        $this->defaultTtlSeconds = $defaultTtlSeconds ?? MakeCachedServiceService::DEFAULT_TTL_SECONDS;
    }

    public function getDefaultTtlSeconds(): int
    {
        return $this->defaultTtlSeconds;
    }

    public function getAdditionalInterface(): ?string
    {
        return $this->additionalInterface;
    }

}
