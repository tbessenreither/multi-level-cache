<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\DataCollector\CacheStatistics;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorCacheInfoDto;

#[CoversClass(DataCollectorCacheInfoDto::class)]


class DataCollectorCacheInfoDtoTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $groupName = 'testGroup';
        $name = 'testName';
        $level = 1;
        $class = 'Test\\Class';
        $statistics = $this->createMock(CacheStatistics::class);

        $dto = new DataCollectorCacheInfoDto($groupName, $name, $level, $class, $statistics);

        // Test getGroupName
        $this->assertSame($groupName, $dto->getGroupName());

        // Test getGroupNameForId
        $this->assertSame('testGroup', $dto->getGroupNameForId());

        // Test getName
        $this->assertSame($name, $dto->getName());

        // Test getNameForId
        $this->assertSame('testName', $dto->getNameForId());

        // Test getLevel
        $this->assertSame($level, $dto->getLevel());

        // Test getClass
        $this->assertSame($class, $dto->getClass());

        // Test getStatistics
        $this->assertSame($statistics, $dto->getStatistics());
    }

}
