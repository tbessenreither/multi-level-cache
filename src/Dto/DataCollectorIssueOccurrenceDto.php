<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Dto;

class DataCollectorIssueOccurrenceDto
{
    public function __construct(
        private readonly ?string $affectedCacheGroup = null,
        private readonly array $affectedKeys = [],
        private readonly array $context = [],
    ) {
    }

    public function getAffectedCacheGroup(): ?string
    {
        return $this->affectedCacheGroup;
    }

    public function getAffectedKeys(): array
    {
        return $this->affectedKeys;
    }

    public function getContext(): array
    {
        return $this->context;
    }

    public function getUniqueHash(): string
    {
        return md5($this->affectedCacheGroup . serialize($this->affectedKeys) . serialize($this->context));
    }
}
