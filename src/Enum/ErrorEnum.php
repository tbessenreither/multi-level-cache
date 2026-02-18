<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Enum;

use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;

enum ErrorEnum: string implements DataCollectorIssueEnumInterface
{
    case ERROR_Example = 'Oh no! An example error occurred. Please check your configuration and try again.';

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
