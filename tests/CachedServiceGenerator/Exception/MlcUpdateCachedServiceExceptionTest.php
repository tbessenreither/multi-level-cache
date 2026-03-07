<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Attribute;

use Exception;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Exception\MlcUpdateCachedServiceException;

#[CoversClass(MlcUpdateCachedServiceException::class)]
class MlcUpdateCachedServiceExceptionTest extends TestCase
{
    public function testSetupAndGetter(): void
    {
        $exception1 = new MlcUpdateCachedServiceException(
            type: 'type1',
            status: 'status1',
            message: 'An error occurred while updating the cached service.',
        );

        $this->assertSame('type1', $exception1->getType());
        $this->assertSame('status1', $exception1->getStatus());
        $this->assertSame('An error occurred while updating the cached service.', $exception1->getMessage());
        $this->assertNull($exception1->getPrevious());

        $previousException = new Exception('Previous exception message');
        $exception2 = new MlcUpdateCachedServiceException(
            type: 'type2',
            status: 'status2',
            message: 'Another error occurred while updating the cached service.',
            previous: $previousException,
        );

        $this->assertSame('type2', $exception2->getType());
        $this->assertSame('status2', $exception2->getStatus());
        $this->assertSame('Another error occurred while updating the cached service.', $exception2->getMessage());
        $this->assertSame($previousException, $exception2->getPrevious());
    }

}
