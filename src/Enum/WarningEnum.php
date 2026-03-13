<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Enum;

use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;

enum WarningEnum: string implements DataCollectorIssueEnumInterface
{
    case WARNING_CACHE_READ_DISABLED = 'Cache read operations are currently disabled via the MLC_DISABLE_READ Environment Variable. This will impact performance.';
    case WARNING_EXPERIMENTAL_FEATURE_BULK = 'You are using an experimental feature. Please be aware that Bulk Caching is still in early stages of development.';
    case DEPRECATION_WARNING = 'You are using a deprecated feature. Please update your cached services, check the documentation, and update your code accordingly.';

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
