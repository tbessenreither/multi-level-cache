<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Enum;

use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;

enum InfoEnum: string implements DataCollectorIssueEnumInterface
{
    case STORED_STRING_VALUE = 'You stored a string value in an object cache. This is inefficient and may lead to issues. Consider caching the deserialized object instead.';
    case LOW_HITRATE_ON_CACHE_LEVEL = 'At least one of your cache levels shows a low hit rate. This may indicate that the cache is not effectively storing frequently accessed data. Consider reviewing your caching strategy and configuration for this level.';

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->value;
    }

    public function getType(): string
    {
        return 'info';
    }

    public function getBadgeClass(): string
    {
        return 'badge-info';
    }

    public function getStatusClass(): string
    {
        return 'log-status-info';
    }

}
