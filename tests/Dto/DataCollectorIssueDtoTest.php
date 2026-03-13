<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueDto;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueOccurrenceDto;
use Tbessenreither\MultiLevelCache\Enum\WarningEnum;

#[CoversClass(DataCollectorIssueDto::class)]
#[UsesClass(WarningEnum::class)]
#[UsesClass(DataCollectorIssueOccurrenceDto::class)]

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

        $object->addOccurrence(new DataCollectorIssueOccurrenceDto(
            affectedCacheGroup: 'group1',
            affectedKeys: ['key1', 'key2'],
            context: ['additional' => 'info']
        ));

        $this->assertEquals('Test Name', $object->getName());
        $this->assertEquals('Test Value', $object->getDescription());
        $this->assertEquals('error', $object->getType());
        $this->assertEquals('badge-danger', $object->getBadgeClass());
        $this->assertEquals('status-failed', $object->getStatusClass());

        $occurrences = $object->getOccurrences();
        $this->assertCount(1, $occurrences);
        $this->assertEquals('group1', $occurrences[0]->getAffectedCacheGroup());
        $this->assertEquals(['key1', 'key2'], $occurrences[0]->getAffectedKeys());
        $this->assertEquals(['additional' => 'info'], $occurrences[0]->getContext());
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
