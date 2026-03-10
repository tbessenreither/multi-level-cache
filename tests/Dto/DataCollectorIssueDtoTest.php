<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueDto;
use Tbessenreither\MultiLevelCache\Enum\WarningEnum;

#[CoversClass(DataCollectorIssueDto::class)]
#[UsesClass(WarningEnum::class)]


class DataCollectorIssueDtoTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $object = new DataCollectorIssueDto(
            name: 'Test Name',
            value: 'Test Value',
            type: 'error',
            badgeClass: 'badge-danger',
            statusClass: 'status-failed',
        );

        $this->assertEquals('Test Name', $object->getName());
        $this->assertEquals('Test Value', $object->getDescription());
        $this->assertEquals('error', $object->getType());
        $this->assertEquals('badge-danger', $object->getBadgeClass());
        $this->assertEquals('status-failed', $object->getStatusClass());
    }

    public function testFromEnum(): void
    {
        $enum = WarningEnum::WARNING_STORED_STRING_VALUE;

        $object = DataCollectorIssueDto::fromEnum($enum);

        $this->assertEquals($enum->getName(), $object->getName());
        $this->assertEquals($enum->getDescription(), $object->getDescription());
        $this->assertEquals($enum->getType(), $object->getType());
        $this->assertEquals($enum->getBadgeClass(), $object->getBadgeClass());
        $this->assertEquals($enum->getStatusClass(), $object->getStatusClass());
    }

}
