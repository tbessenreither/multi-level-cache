<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Exception\CacheBetaDecayException;

#[CoversClass(CacheBetaDecayException::class)]


class CacheBetaDecayExceptionTest extends TestCase
{
    public function testExceptionMessage(): void
    {
        $exception = new CacheBetaDecayException('Test message');
        $this->assertInstanceOf(CacheBetaDecayException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

}
