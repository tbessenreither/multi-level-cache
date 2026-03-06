<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service\Implementations;

use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use stdClass;

class GetTestObject
{
    public static function getTestObject(string $testData): stdClass
    {
        $obj = new stdClass();
        $obj->data = $testData;

        return $obj;
    }

    public static function getWrappedTestObject(string $testData, int $ttlSeconds): CacheObjectWrapperDto
    {
        $obj = self::getTestObject($testData);
        return new CacheObjectWrapperDto(object: $obj, ttlSeconds: $ttlSeconds);
    }

}
