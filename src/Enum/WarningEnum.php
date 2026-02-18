<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Enum;

use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;

enum WarningEnum: string implements DataCollectorIssueEnumInterface
{
    case WARNING_STORED_STRING_VALUE = 'You stored a string value in an object cache. This is inefficient and may lead to issues. Consider caching the deserialized object instead.';
    case WARNING_CACHE_READ_DISABLED = 'Cache read operations are currently disabled via the MLC_DISABLE_READ Environment Variable. This will impact performance.';

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
        return 'warning';
    }

    public function getBadgeClass(): string
    {
        return 'badge-warning';
    }

    public function getStatusClass(): string
    {
        return 'log-status-warning';
    }

}
