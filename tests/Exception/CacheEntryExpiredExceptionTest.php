<?php

declare(strict_types=1);

namespace Tests\Tbessenreither\MultiLevelCache\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Exception\CacheEntryExpiredException;

#[CoversClass(CacheEntryExpiredException::class)]


class CacheEntryExpiredExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new CacheEntryExpiredException('Entry expired');
        $this->assertInstanceOf(CacheEntryExpiredException::class, $exception);
        $this->assertSame('Entry expired', $exception->getMessage());
    }

}
