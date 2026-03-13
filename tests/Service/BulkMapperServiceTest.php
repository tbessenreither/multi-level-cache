<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Enum\BulkListTypeEnum;
use Tbessenreither\MultiLevelCache\Service\BulkMapperService;
use Tbessenreither\MultiLevelCache\Tests\Service\TestClasses\Recursive;

#[CoversClass(BulkMapperService::class)]


class BulkMapperServiceTest extends TestCase
{
    public function testMapByListTypeWithValidInput(): void
    {
        $input = [
            ['id' => 6, 'value' => 'a'],
            ['id' => 2, 'value' => 'b'],
            ['id' => 3, 'value' => 'c'],
        ];
        $expected = [
            6 => ['id' => 6, 'value' => 'a'],
            2 => ['id' => 2, 'value' => 'b'],
            3 => ['id' => 3, 'value' => 'c'],
        ];
        $this->assertEquals($expected, BulkMapperService::mapByListType($input, BulkListTypeEnum::ARRAY_ASSOC, 'id'));

        $this->assertEquals($input, BulkMapperService::mapByListType($input, BulkListTypeEnum::ARRAY_NUMERIC, 'id'));
    }

    public function testMapArrayNumeric(): void
    {
        $input = ['key' => 'a', 'b', 6 => 'c'];
        $expected = ['a', 'b', 'c'];
        $this->assertEquals($expected, BulkMapperService::mapArrayNumeric($input));
    }

    public function testMapArrayAssoc(): void
    {
        $input = [
            ['id' => 6, 'value' => 'a'],
            ['id' => 2, 'value' => 'b'],
            ['id' => 3, 'value' => 'c'],
        ];
        $expected = [
            6 => ['id' => 6, 'value' => 'a'],
            2 => ['id' => 2, 'value' => 'b'],
            3 => ['id' => 3, 'value' => 'c'],
        ];

        $this->assertEquals($expected, BulkMapperService::mapArrayAssoc($input, 'id'));
    }

    public function testMapArrayAssocWithCorruptedValue(): void
    {
        $input = [
            ['id' => 6, 'value' => 'a'],
            'b',
            ['id' => 3, 'value' => 'c'],
        ];
        $expected = [
            6 => ['id' => 6, 'value' => 'a'],
            3 => ['id' => 3, 'value' => 'c'],
        ];
        $this->assertEquals($expected, BulkMapperService::mapArrayAssoc($input, 'id'));
    }

    public function testGetIndexFromObjectResult(): void
    {

        $object = new Recursive(
            id: 6,
            value: 'a',
            nested: new Recursive(
                id: 2,
                value: 'b',
                nested: new Recursive(
                    id: 3,
                    value: 'c',
                ),
            ),
        );

        $this->assertEquals(6, BulkMapperService::getIdentifierFromObjectResult($object, 'getId'));
        $this->assertEquals('a', BulkMapperService::getIdentifierFromObjectResult($object, 'getValue'));
        $this->assertEquals(2, BulkMapperService::getIdentifierFromObjectResult($object, 'getNested.getId'));
        $this->assertEquals('b', BulkMapperService::getIdentifierFromObjectResult($object, 'getNested.getValue'));
        $this->assertEquals('b', BulkMapperService::getIdentifierFromObjectResult($object, 'getNested.getValue.getId'));
        $this->assertEquals(3, BulkMapperService::getIdentifierFromObjectResult($object, 'getNested.getNested.getId'));
        $this->assertEquals('c', BulkMapperService::getIdentifierFromObjectResult($object, 'getNested.getNested.getValue'));
    }

    public function testGetIndexFromObjectResultWithWrongValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $object = new Recursive(id: 1, value: false);
        BulkMapperService::getIdentifierFromObjectResult($object, 'getNested.getvalue');
    }

    public function testGetIndexFromObjectResultWithInvalidSelector(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $object = new Recursive(id: 6, value: 'a');
        BulkMapperService::getIdentifierFromObjectResult($object, '');
    }

    public function testGetIndexFromObjectResultWithEmptyPartSelector(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $object = new Recursive(id: 6, value: 'a');
        BulkMapperService::getIdentifierFromObjectResult($object, 'getNested..getId');
    }
}
