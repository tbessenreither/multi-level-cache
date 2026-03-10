<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\DataCollector;

use InvalidArgumentException;

class CacheStatistics
{
    public const TYPE_READ = 'read';
    public const TYPE_WRITE = 'write';
    public const TYPE_DELETE = 'delete';

    private int $hits = 0;
    private int $misses = 0;
    private int $betaDecays = 0;
    private int $writes = 0;
    private int $reads = 0;
    private int $deletes = 0;
    private float $runtimeSecondsRead = 0.0;
    private float $runtimeSecondsWrite = 0.0;
    private float $runtimeSecondsDelete = 0.0;
    private ?float $runtimeTracker = null;
    // @var array<string, int|float|string|bool>
    private array $configData = [];
    // @var string[]|null
    private ?array $cachedKeys = null;

    /**
     * Format $this->enhancedUsageData[$key] = ['hits' => int, 'misses' => int];
     * @var array<string, array<string, int>>
     */
    private array $enhancedUsageData = [];

    public function getHits(): int
    {
        return $this->hits;
    }

    public function addHit(): void
    {
        $this->hits++;
    }

    public function getMisses(): int
    {
        return $this->misses;
    }

    public function addMiss(): void
    {
        $this->misses++;
    }

    public function getBetaDecays(): int
    {
        return $this->betaDecays;
    }

    public function addBetaDecay(): void
    {
        $this->betaDecays++;
    }

    public function getWrites(): int
    {
        return $this->writes;
    }

    public function getReads(): int
    {
        return $this->reads;
    }

    public function getDeletes(): int
    {
        return $this->deletes;
    }

    public function getTotal(): int
    {
        return $this->getReads() + $this->getWrites() + $this->getDeletes();
    }

    public function getHitRate(): float
    {
        $total = $this->getHits() + $this->getMisses();

        return $total === 0 ? 0.0 : $this->getHits() / $total;
    }

    public function getHitRatePercent(): float
    {
        return $this->getHitRate() * 100;
    }

    public function getRuntimeSecondsRead(): ?float
    {
        return $this->runtimeSecondsRead;
    }

    public function getRuntimeSecondsWrite(): ?float
    {
        return $this->runtimeSecondsWrite;
    }

    public function getRuntimeSecondsDelete(): ?float
    {
        return $this->runtimeSecondsDelete;
    }

    public function getRuntimeSecondsTotal(): ?float
    {
        return $this->getRuntimeSecondsRead() + $this->getRuntimeSecondsWrite() + $this->getRuntimeSecondsDelete();
    }

    public function startTrackingRuntime(): void
    {
        $this->runtimeTracker = microtime(true);
    }

    public function setConfigData(string $key, int|float|string|bool|null $value): void
    {
        $this->configData[$key] = $value;
    }

    /**
     * @param array<string, int|float|string|bool> $data
     */
    public function setConfigDataArray(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->setConfigData($key, $value);
        }
    }

    /**
     * @return array<string, int|float|string|bool>
     */
    public function getConfigData(): array
    {
        return $this->configData;
    }

    public function stopTrackingRuntime(string $type): void
    {
        if ($this->runtimeTracker === null) {
            return;
        }
        $elapsed = microtime(true) - $this->runtimeTracker;
        $this->runtimeTracker = null;
        if ($type === self::TYPE_WRITE) {
            $this->runtimeSecondsWrite += $elapsed;
            $this->writes++;
        } elseif ($type === self::TYPE_READ) {
            $this->runtimeSecondsRead += $elapsed;
            $this->reads++;
        } elseif ($type === self::TYPE_DELETE) {
            $this->runtimeSecondsDelete += $elapsed;
            $this->deletes++;
        } else {
            throw new InvalidArgumentException("Invalid runtime type: $type");
        }
    }

    public function logEnhancedUsageData(string $key, bool $isHit): void
    {
        if (!isset($this->enhancedUsageData[$key])) {
            $this->enhancedUsageData[$key] = ['hits' => 0, 'misses' => 0];
        }
        if ($isHit) {
            $this->enhancedUsageData[$key]['hits']++;
        } else {
            $this->enhancedUsageData[$key]['misses']++;
        }
    }

    /**
     * @return array<string, array<string, int>>
     */
    public function getEnhancedUsageData(): array
    {
        //return the array but sorted by number of hits and missses descending and then misses descending but don't modify the original array
        $sorted = $this->enhancedUsageData;
        uasort($sorted, static function (array $a, array $b) {
            $aTotal = $a['hits'] + $a['misses'];
            $bTotal = $b['hits'] + $b['misses'];
            if ($aTotal === $bTotal) {
                return $b['misses'] <=> $a['misses'];
            }

            return $bTotal <=> $aTotal;
        });

        return $sorted;
    }

    /**
     * @param array<int, string>|null $cachedKeys
     */
    public function setCachedKeys(?array $cachedKeys): void
    {
        foreach ($cachedKeys ?? [] as $key) {
            $this->addCachedKey($key);
        }
    }

    public function addCachedKey(string $key): void
    {
        if ($this->cachedKeys === null) {
            $this->cachedKeys = [];
        }

        $this->cachedKeys[$key] = $key;
    }

    public function deleteCachedKey(string $key): void
    {
        if ($this->cachedKeys === null) {
            return;
        }

        unset($this->cachedKeys[$key]);
    }

    /**
     * @return array<int, string>|null
     */
    public function getCachedKeys(): ?array
    {
        if ($this->cachedKeys === null) {
            return null;
        }

        return array_unique(array_values($this->cachedKeys));
    }

}
