<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\BulkConfig;
use Tbessenreither\MultiLevelCache\Enum\BulkListTypeEnum;

#[CoversClass(BulkConfig::class)]


class BulkConfigTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $bulkConfig = new BulkConfig('id', BulkListTypeEnum::ARRAY_ASSOC);

        $this->assertSame('id', $bulkConfig->getIdentifierSelector());
        $this->assertSame(BulkListTypeEnum::ARRAY_ASSOC, $bulkConfig->getListType());
    }

}
