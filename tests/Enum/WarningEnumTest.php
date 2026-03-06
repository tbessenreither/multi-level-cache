<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueDto;
use Tbessenreither\MultiLevelCache\Enum\WarningEnum;

#[CoversClass(WarningEnum::class)]
#[UsesClass(DataCollectorIssueDto::class)]


final class WarningEnumTest extends TestCase
{
    public function testCasesProvideExpectedFields(): void
    {
        foreach (WarningEnum::cases() as $case) {
            $this->assertNotEmpty($case->getName());
            $this->assertNotEmpty($case->getDescription());
            $this->assertSame('warning', $case->getType());
            $this->assertNotEmpty($case->getBadgeClass());
            $this->assertNotEmpty($case->getStatusClass());
        }
    }

}
