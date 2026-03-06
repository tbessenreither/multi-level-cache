<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\Service\CacheKeyGeneratorService;

#[CoversClass(CacheKeyGeneratorService::class)]


class CacheKeyGeneratorServiceTest extends TestCase
{
    public function testFromIriGeneratesCorrectKey(): void
    {
        $iri = 'https://example.com/api/resource';
        $parameters = ['param1' => 'value1', 'param2' => 'value2'];
        $prefix = 'cache_prefix';

        $expectedKey = 'cache_prefix:example.com:api:resource:fb6f325a8bfe122bc1e91874459d33d1';
        $generatedKey = CacheKeyGeneratorService::fromIri($iri, $parameters, $prefix);

        $this->assertEquals($expectedKey, $generatedKey);
    }

    public function testFromIriHandlesEmptyParameters(): void
    {
        $iri = 'https://example.com/api/resource';
        $parameters = [];
        $prefix = null;

        $expectedKey = 'example.com:api:resource:d41d8cd98f00b204e9800998ecf8427e';
        $generatedKey = CacheKeyGeneratorService::fromIri($iri, $parameters, $prefix);

        $this->assertEquals($expectedKey, $generatedKey);
    }

    public function testFromIriHandlesEmptyIri(): void
    {
        $iri = '';
        $parameters = ['param1' => 'value1'];
        $prefix = 'cache_prefix';

        $expectedKey = 'cache_prefix::7d41799fdf4b333c33907ccd00c89ec4';
        $generatedKey = CacheKeyGeneratorService::fromIri($iri, $parameters, $prefix);

        $this->assertEquals($expectedKey, $generatedKey);
    }

    public function testFromIriHandlesNoPrefix(): void
    {
        $iri = 'https://example.com/api/resource';
        $parameters = ['param1' => 'value1'];
        $prefix = null;

        $expectedKey = 'example.com:api:resource:7d41799fdf4b333c33907ccd00c89ec4';
        $generatedKey = CacheKeyGeneratorService::fromIri($iri, $parameters, $prefix);

        $this->assertEquals($expectedKey, $generatedKey);
    }

}
