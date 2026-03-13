<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Enum\CacheTypeEnum;

#[CoversClass(CacheTypeEnum::class)]
final class CacheTypeEnumTest extends TestCase
{
    public function testCasesProvideExpectedFields(): void
    {
        $existingValues = [];
        foreach (CacheTypeEnum::cases() as $case) {
            $this->assertNotEmpty($case->name);
            $this->assertNotEmpty($case->value);
            $this->assertNotContains($case->value, $existingValues, "Duplicate value found: {$case->value}");
            $existingValues[] = $case->value;
        }
    }

}
