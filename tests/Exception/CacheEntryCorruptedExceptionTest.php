<?php

declare(strict_types=1);

namespace Tests\Tbessenreither\MultiLevelCache\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Exception\CacheEntryCorruptedException;

#[CoversClass(CacheEntryCorruptedException::class)]


class CacheEntryCorruptedExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new CacheEntryCorruptedException('Corrupted entry');
        $this->assertInstanceOf(CacheEntryCorruptedException::class, $exception);
        $this->assertSame('Corrupted entry', $exception->getMessage());
    }

}
