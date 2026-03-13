<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueOccurrenceDto;

#[CoversClass(DataCollectorIssueOccurrenceDto::class)]

class DataCollectorIssueOccurrenceDtoTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $occurrence = new DataCollectorIssueOccurrenceDto(
            affectedCacheGroup: 'group1',
            affectedKeys: ['key1', 'key2'],
            context: ['additional' => 'info']
        );

        $this->assertEquals('group1', $occurrence->getAffectedCacheGroup());
        $this->assertEquals(['key1', 'key2'], $occurrence->getAffectedKeys());
        $this->assertEquals(['additional' => 'info'], $occurrence->getContext());

        $this->assertIsString($occurrence->getUniqueHash());
        $this->assertNotEmpty($occurrence->getUniqueHash());
    }

    public function testUniqueHashConsistency(): void
    {
        $occurrence1 = new DataCollectorIssueOccurrenceDto(
            affectedCacheGroup: 'group1',
            affectedKeys: ['key1', 'key2'],
            context: ['additional' => 'info']
        );

        $occurrence2 = new DataCollectorIssueOccurrenceDto(
            affectedCacheGroup: 'group1',
            affectedKeys: ['key1', 'key2'],
            context: ['additional' => 'info']
        );

        $this->assertEquals($occurrence1->getUniqueHash(), $occurrence2->getUniqueHash());
    }

}
