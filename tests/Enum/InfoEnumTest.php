<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Enum\InfoEnum;

#[CoversClass(InfoEnum::class)]
final class InfoEnumTest extends TestCase
{
    public function testCasesProvideExpectedFields(): void
    {
        foreach (InfoEnum::cases() as $case) {
            $this->assertNotEmpty($case->getName());
            $this->assertNotEmpty($case->getDescription());
            $this->assertSame('info', $case->getType());
            $this->assertNotEmpty($case->getBadgeClass());
            $this->assertNotEmpty($case->getStatusClass());
        }
    }

}
