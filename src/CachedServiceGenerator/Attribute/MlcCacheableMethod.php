<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute;

use Attribute;
use InvalidArgumentException;
use ReflectionMethod;

#[Attribute(Attribute::TARGET_METHOD)]


class MlcCacheableMethod
{
    public function __construct(
        private int $ttlSeconds,
        private ?string $dataVersion = null,
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

}
