<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;

#[CoversClass(CacheConnectionException::class)]


class CacheConnectionExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new CacheConnectionException('Connection failed');
        $this->assertInstanceOf(CacheConnectionException::class, $exception);
        $this->assertSame('Connection failed', $exception->getMessage());
    }

}
