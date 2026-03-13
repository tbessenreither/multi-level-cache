<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Enum;

use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;

enum ErrorEnum: string implements DataCollectorIssueEnumInterface
{
    case ERROR_Example = 'Oh no! An example error occurred. Please check your configuration and try again.';
    case ERROR_BULK_CONFIG_MISSING = 'There seems to be an outdated Cached Service that tries to use bulk caching but is missing the required BulkConfig. Please update your cached services to resolve this issue.';
    case ERROR_BULK_OPERATION_FAILED_INVALID_RESPONSE = 'The bulk operation failed because the response from the source was invalid. Please check the source and ensure it returns a valid response for bulk operations.';

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
        return 'error';
    }

    public function getBadgeClass(): string
    {
        return 'badge-error';
    }

    public function getStatusClass(): string
    {
        return 'log-status-error';
    }

}
