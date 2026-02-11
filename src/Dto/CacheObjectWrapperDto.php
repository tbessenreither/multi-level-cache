<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Dto;

use InvalidArgumentException;

class CacheObjectWrapperDto
{
    private int $createdAt;
    private int $betaDecayStartInSeconds = 0;
    private int $betaDecayTtlOffset = 0;

    /**
     * @param int<0,100> $betaDecayStartInPercent
     */
    public function __construct(
        private readonly object|string|int|float|bool $object,
        private readonly int $ttlSeconds,
        private int $betaDecayStartInPercent = 75
    ) {
        $this->createdAt = time();
        $betaDecayStartInPercent = max(0, min(100, $betaDecayStartInPercent));
        $this->betaDecayStartInSeconds = (int) round(($this->getTtl() * $betaDecayStartInPercent) / 100);
        $this->betaDecayTtlOffset = $this->getTtl() - $this->betaDecayStartInSeconds;
    }

    public function isExpired(): bool
    {
        return $this->getTtlLeft() <= 0;
    }

    public function isBetaDecayed(): bool
    {
        return rand(0, 100) < $this->getBetaDecayProbabilityInPercent();
    }

    public function getAge(): int
    {
        return time() - $this->createdAt;
    }

    public function getSerializedObject(): string
    {
        return serialize($this->object);
    }

    public static function fromSerialized(string $serializedData): self
    {
        $object = unserialize($serializedData);
        if (!$object instanceof self) {
            throw new InvalidArgumentException('Invalid serialized data for CacheObjectWrapper');
        }

        return $object;
    }

    public function getObject(): object|string|int|float|bool
    {
        return $this->object;
    }

    public function getTtl(): int
    {
        return $this->ttlSeconds;
    }

    public function getTtlLeft(): int
    {
        $elapsed = time() - $this->createdAt;
        $ttlLeft = $this->ttlSeconds - $elapsed;
        return max(0, $ttlLeft);
    }

    /**
     * Calculate the probability of beta decay in percent.
     * It starts at 0 until BETA_DECAY_START seconds have passed,
     * then linearly increases to 100 at the TTL time.
     * The value is clamped between 0 and 100.
     *
     * @return int
     */
    public function getBetaDecayProbabilityInPercent(): int
    {
        if ($this->getAge() < $this->betaDecayStartInSeconds) {
            return 0;
        } elseif ($this->getAge() >= $this->getTtl()) {
            return 100;
        } else {
            return (int) round((($this->getAge() - $this->betaDecayStartInSeconds) / $this->betaDecayTtlOffset) * 100);
        }
    }

}
