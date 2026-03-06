<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CacheObjectWrapperDto::class)]


class CacheObjectWrapperDtoTest extends TestCase
{
    public function testGettersAndSetters(): void
    {
        $object = (object) ['key' => 'value'];
        $ttl = 60;
        $dto = new CacheObjectWrapperDto($object, $ttl);

        // Test getObject
        $this->assertSame($object, $dto->getObject());

        // Test getTtl
        $this->assertSame($ttl, $dto->getTtl());

        // Test getTtlLeft (should be close to $ttl initially)
        $this->assertGreaterThanOrEqual($ttl - 1, $dto->getTtlLeft());
        $this->assertLessThanOrEqual($ttl, $dto->getTtlLeft());

        // Test getAge (should be close to 0 initially)
        $this->assertGreaterThanOrEqual(0, $dto->getAge());
        $this->assertLessThanOrEqual(1, $dto->getAge());

        // Test isExpired (should be false initially)
        $this->assertFalse($dto->isExpired());

        // Test getSerializedObject
        $this->assertSame(serialize($object), $dto->getSerializedObject());

        // Test fromSerialized
        $serialized = serialize($dto);
        $deserialized = CacheObjectWrapperDto::fromSerialized($serialized);
        $this->assertEquals($dto, $deserialized);

        // Test exception in fromSerialized
        $this->expectException(InvalidArgumentException::class);
        CacheObjectWrapperDto::fromSerialized(serialize('invalid data'));
    }

    public function testBetaDecayProbability100(): void
    {
        $object = (object) ['key' => 'value'];
        $dto = new CacheObjectWrapperDto($object, 0, 0, 10); // Start beta decay immediately

        $this->assertTrue($dto->isBetaDecayed());
    }

    public function testBetaDecayProbability(): void
    {
        $object = (object) ['key' => 'value'];
        $dto = new CacheObjectWrapperDto($object, 4, 0); // Start beta decay at the end
        sleep(1);
        $probability = $dto->getBetaDecayProbabilityInPercent();
        $this->assertEqualsWithDelta(37, $probability, 13, 'Beta decay probability should be 25%-50%. Actual: ' . $probability);
    }

}
