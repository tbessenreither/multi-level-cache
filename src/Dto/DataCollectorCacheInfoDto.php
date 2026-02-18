<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Dto;

use Tbessenreither\MultiLevelCache\DataCollector\CacheStatistics;

class DataCollectorCacheInfoDto
{
    private array $stringReplacementsForId = ['\\' => '_', '_' => '-', ':' => '_', '.' => '-', '/' => '_', ' ' => '-', '[]' => 'Array'];

    /**
     * @param positive-int $level
     * @param class-string $class
     */
    public function __construct(
        private string $groupName,
        private string $name,
        private int $level,
        private string $class,
        private CacheStatistics $statistics,
    ) {
    }

    public function getGroupName(): string
    {
        return $this->groupName;
    }

    public function getGroupNameForId(): string
    {
        return str_replace(array_keys($this->stringReplacementsForId), array_values($this->stringReplacementsForId), $this->groupName);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getNameForId(): string
    {
        return str_replace(array_keys($this->stringReplacementsForId), array_values($this->stringReplacementsForId), $this->name);
    }

    public function getLevel(): int
    {
        return $this->level;
    }

    public function getClass(): string
    {
        return $this->class;
    }

    public function getStatistics(): CacheStatistics
    {
        return $this->statistics;
    }

}
