<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;
use Tbessenreither\MultiLevelCache\Exception\CacheMissException;

#[CoversClass(CacheConnectionException::class)]


class CacheMissExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new CacheMissException('Cache miss');
        $this->assertInstanceOf(CacheMissException::class, $exception);
        $this->assertSame('Cache miss', $exception->getMessage());
    }

}
