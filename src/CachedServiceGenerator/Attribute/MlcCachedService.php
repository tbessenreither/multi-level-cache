<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
class MlcCachedService
{
    /**
     * Summary of __construct
     * @param string $originalServiceClass the original service class
     * @param bool $allowSync if true the update commands are allowed to overwrite this service. Set to false if you made manual changes. Keeping the contract up to date is then your responsibility.
     */
    public function __construct(
        private string $originalServiceClass,
        private bool $allowSync = true,
    ) {
    }

    public function getOriginalServiceClass(): string
    {
        return $this->originalServiceClass;
    }

    public function isSyncAllowed(): bool
    {
        return $this->allowSync;
    }
}
