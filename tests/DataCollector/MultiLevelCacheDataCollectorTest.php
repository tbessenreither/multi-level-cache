<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\DataCollector;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tbessenreither\MultiLevelCache\DataCollector\CacheStatistics;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorCacheInfoDto;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueDto;
use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;
use stdClass;

#[CoversClass(MultiLevelCacheDataCollector::class)]
#[UsesClass(CacheStatistics::class)]
#[UsesClass(DataCollectorCacheInfoDto::class)]
#[UsesClass(DataCollectorIssueDto::class)]


class MultiLevelCacheDataCollectorTest extends TestCase
{
    public function testAddInstanceAndRegisterMethods(): void
    {
        $collector = new MultiLevelCacheDataCollector('dev', true);

        $stats = new CacheStatistics();

        $collector->addInstance(
            groupName: 'group1',
            name: 'adapterA',
            cacheLevel: 0,
            class: stdClass::class,
            statistics: $stats,
        );

        $this->assertTrue($collector->isActive());

        $grouped = $collector->getGroupedInstances();
        $this->assertArrayHasKey('group1', $grouped);

        $adapters = $collector->getAdapters();
        $this->assertContains('adapterA', $adapters);

        // register a hit and a miss and a beta decay and ensure stats updated
        $collector->registerCacheHit('group1', 0, 'key1');
        $this->assertEquals(1, $stats->getHits());

        $collector->registerCacheMiss('group1', 0, 'key1');
        $this->assertEquals(1, $stats->getMisses());

        $collector->registerBetaDecay('group1', 0, 'key1');
        $this->assertEquals(1, $stats->getBetaDecays());

        // enhanced usage data should be present for key1 (1 hit, 1 miss)
        $enhanced = $stats->getEnhancedUsageData();
        $this->assertArrayHasKey('key1', $enhanced);
        $this->assertEquals(['hits' => 1, 'misses' => 1], $enhanced['key1']);
    }

    public function testGetLevelsSummaryAndAdaptersAggregation(): void
    {
        $collector = new MultiLevelCacheDataCollector('dev', false);

        $s1 = new CacheStatistics();
        $s2 = new CacheStatistics();

        // instance at level 0
        $collector->addInstance('g', 'A', 0, stdClass::class, $s1);
        // instance at level 2
        $collector->addInstance('g', 'B', 2, stdClass::class, $s2);

        // record hits/misses and a write runtime to s2
        $collector->registerCacheHit('g', 0, 'k');
        $collector->registerCacheMiss('g', 2, 'k2');

        // simulate a write runtime on s2
        $s2->startTrackingRuntime();
        usleep(5000); // small sleep to accumulate some time
        $s2->stopTrackingRuntime(CacheStatistics::TYPE_WRITE);

        $levels = $collector->getLevels();
        // should contain 0 and 2
        $this->assertContains(0, $levels);
        $this->assertContains(2, $levels);

        $adapters = $collector->getAdapters();
        $this->assertContains('A', $adapters);
        $this->assertContains('B', $adapters);

        $summary = $collector->getSummary();

        // IO values aggregated
        $this->assertGreaterThanOrEqual(1, $summary['io']['read hits']['value']);
        $this->assertGreaterThanOrEqual(1, $summary['io']['read misses']['value']);
        $this->assertGreaterThanOrEqual(0, $summary['timings']['time write']['value']);
        $this->assertGreaterThanOrEqual(0.0, $summary['timings']['time total']['value']);
    }

    public function testRaiseIssueAndCollectingBehaviour(): void
    {
        $collectorDev = new MultiLevelCacheDataCollector('dev', false);

        $issue =


            new class () implements DataCollectorIssueEnumInterface {
                public function getName(): string
                {
                    return 'TEST_ISSUE';
                }

                public function getDescription(): string
                {
                    return 'some value';
                }

                public function getType(): string
                {
                    return 'warning';
                }

                public function getBadgeClass(): string
                {
                    return 'badge';
                }

                public function getStatusClass(): string
                {
                    return 'status';
                }
            };

        $collectorDev->raiseIssue($issue);
        $this->assertTrue($collectorDev->hasIssues());
        $issues = $collectorDev->getIssues();
        $this->assertArrayHasKey('TEST_ISSUE', $issues);

        // in non-dev environment nothing should be collected
        $collectorProd = new MultiLevelCacheDataCollector('prod', false);
        $collectorProd->raiseIssue($issue);
        $this->assertFalse($collectorProd->hasIssues());
        $this->assertEmpty($collectorProd->getIssues());
    }

    public function testGetStatisticsObjectWithInvalidGroup(): void
    {
        $collector = new MultiLevelCacheDataCollector('dev', false);

        $ref = new ReflectionClass($collector);
        $method = $ref->getMethod('getStatisticsObject');
        $method->setAccessible(true);
        $stats = $method->invokeArgs($collector, ['nonexistent_group', 0]);

        $this->assertNull($stats);
    }

    public function testGetStatisticsObjectWithInvalidLevel(): void
    {
        $collector = new MultiLevelCacheDataCollector('dev', false);
        $collector->addInstance(
            groupName: 'valid_group',
            name: 'adapterA',
            cacheLevel: 0,
            class: stdClass::class,
            statistics: new CacheStatistics(),
        );

        $ref = new ReflectionClass($collector);
        $method = $ref->getMethod('getStatisticsObject');
        $method->setAccessible(true);
        $stats = $method->invokeArgs($collector, ['valid_group', 7]);

        $this->assertNull($stats);
    }

}
