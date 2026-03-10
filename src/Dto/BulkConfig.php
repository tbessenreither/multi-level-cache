<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Dto;

use Tbessenreither\MultiLevelCache\Enum\BulkListTypeEnum;

class BulkConfig
{
    public function __construct(
        private string $identifierSelector,
        private BulkListTypeEnum $listType = BulkListTypeEnum::ARRAY_NUMERIC,
    ) {
    }

    public function getListType(): BulkListTypeEnum
    {
        return $this->listType;
    }

    public function getIdentifierSelector(): string
    {
        return $this->identifierSelector;
    }
}
