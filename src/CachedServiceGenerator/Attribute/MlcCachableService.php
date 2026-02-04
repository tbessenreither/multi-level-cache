<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\MlcMakeCachedServiceService;
use Attribute;

/**
 * To mark a service as cachable.
 */
#[Attribute(Attribute::TARGET_CLASS)]
class MlcCachableService
{
    private int $defaultTtlSeconds;
    public function __construct(
        ?int $defaultTtlSeconds = null,
    ) {
        $this->defaultTtlSeconds = $defaultTtlSeconds ?? MlcMakeCachedServiceService::DEFAULT_TTL_SECONDS;
    }

    public function getDefaultTtlSeconds(): int
    {
        return $this->defaultTtlSeconds;
    }

}
