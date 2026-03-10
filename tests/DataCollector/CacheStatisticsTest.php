<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Dto;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tbessenreither\MultiLevelCache\DataCollector\CacheStatistics;

#[CoversClass(CacheStatistics::class)]


class CacheStatisticsTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $stats = new CacheStatistics();

        $this->assertEquals(0, $stats->getHits());
        $this->assertEquals(0, $stats->getMisses());
        $this->assertEquals(0.0, $stats->getHitRate());
        $this->assertEquals(0.0, $stats->getHitRatePercent());

        $this->assertEquals(0, $stats->getWrites());
        $this->assertEquals(0, $stats->getReads());
        $this->assertEquals(0, $stats->getDeletes());

        $this->assertEquals(0, $stats->getTotal());

        $this->assertEquals(0.0, $stats->getRuntimeSecondsRead());
        $this->assertEquals(0.0, $stats->getRuntimeSecondsWrite());
        $this->assertEquals(0.0, $stats->getRuntimeSecondsDelete());
        $this->assertEquals(0.0, $stats->getRuntimeSecondsTotal());

        $this->assertEquals([], $stats->getConfigData());
    }

    public function testHitAndMissCounting(): void
    {
        $stats = new CacheStatistics();

        $stats->addHit();
        $stats->addHit();
        $stats->addMiss();

        $stats->addBetaDecay();

        $this->assertEquals(2, $stats->getHits());
        $this->assertEquals(1, $stats->getMisses());
        $this->assertEquals(2 / 3, $stats->getHitRate());
        $this->assertEquals((2 / 3) * 100, $stats->getHitRatePercent());

        $this->assertEquals(1, $stats->getBetaDecays());
    }

    public function testRuntimeAccumulation(): void
    {
        $stats = new CacheStatistics();

        $deltaMsAllowed = 15;

        $tests = [
            CacheStatistics::TYPE_WRITE => 3,
            CacheStatistics::TYPE_READ => 4,
            CacheStatistics::TYPE_DELETE => 1,
        ];

        $times = [
            'total' => [
                'count' => 0,
                'expected' => 0.0,
            ],
        ];

        foreach ($tests as $type => $count) {
            $times[$type] = [
                'count' => $count,
                'expected' => 0.0,
            ];
            for ($i = 0; $i < $count; $i++) {
                $durationMs = 10;
                $this->trackHelper($stats, $type, $durationMs);
                $times[$type]['expected'] += $durationMs;
            }
            $times['total']['count'] += $count;
            $times['total']['expected'] += $times[$type]['expected'];
        }

        foreach ($times as $type => $test) {
            $method = 'getRuntimeSeconds' . ucfirst($type);
            $runtimeInMs = $stats->{$method}() * 1000;

            $this->assertEqualsWithDelta(
                $test['expected'],
                $runtimeInMs,
                $deltaMsAllowed,
                "Runtime out of bounds. method $method() - runtime $runtimeInMs ms - expected " . $test['expected'] . " +- $deltaMsAllowed "
            );

            $totalMethod = 'get' . ucfirst($type);
            if ($type !== 'total') {
                $totalMethod = $totalMethod . 's';
            }
            $this->assertEquals(
                $test['count'],
                $stats->{$totalMethod}(),
                "Count for type $type does not match"
            );
        }
    }

    public function testInvalidRuntimeType(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $stats = new CacheStatistics();
        $stats->startTrackingRuntime();
        $stats->stopTrackingRuntime('invalid_type');
    }

    public function testStopTrackingWithoutStart(): void
    {
        $stats = new CacheStatistics();

        // Should not throw an exception
        $stats->stopTrackingRuntime(CacheStatistics::TYPE_READ);

        $this->assertEquals(0.0, $stats->getRuntimeSecondsRead());
        $this->assertEquals(0, $stats->getReads());
    }

    public function testConfigDataStorage(): void
    {
        $stats = new CacheStatistics();

        $configData = [
            'cache_enabled' => true,
            'max_items' => 1000,
            'default_ttl' => 3600,
            'cache_provider' => 'redis',
            'compression' => false,
            'null_value' => null,
        ];

        $stats->setConfigDataArray($configData);

        $this->assertEquals($configData, $stats->getConfigData());
    }

    public function testTrackEnhancedUsageData(): void
    {
        $stats = new CacheStatistics();

        $stats->logEnhancedUsageData('key1', true);
        $stats->logEnhancedUsageData('key2', true);
        $stats->logEnhancedUsageData('key2', true);
        $stats->logEnhancedUsageData('key2', false);
        $stats->logEnhancedUsageData('key3', true);
        $stats->logEnhancedUsageData('key3', false);
        $stats->logEnhancedUsageData('key3', false);

        $expectedData = [
            'key1' => ['hits' => 1, 'misses' => 0],
            'key2' => ['hits' => 2, 'misses' => 1],
            'key3' => ['hits' => 1, 'misses' => 2],
        ];

        $this->assertEquals($expectedData, $stats->getEnhancedUsageData());
    }

    public function testCachedKey(): void
    {
        $stats = new CacheStatistics();

        $stats->addCachedKey('key1');
        $stats->addCachedKey('key2');
        $stats->addCachedKey('key3');
        $stats->addCachedKey('key4');

        $this->assertEquals(['key1', 'key2', 'key3', 'key4'], $stats->getCachedKeys());

        $stats->deleteCachedKey('key2');
        $this->assertEquals(['key1', 'key3', 'key4'], $stats->getCachedKeys());

        $stats->setCachedKeys(['newKey1', 'newKey2']);
        $this->assertEquals(['key1', 'key3', 'key4', 'newKey1', 'newKey2'], $stats->getCachedKeys());
    }

    public function testCachedKeyWithNull(): void
    {
        $stats = new CacheStatistics();

        $this->assertNull($stats->getCachedKeys());
    }

    private function trackHelper(CacheStatistics $stats, string $type, float $durationMs): void
    {
        $stats->startTrackingRuntime();
        usleep((int) ($durationMs * 1000));
        $stats->stopTrackingRuntime($type);
    }

}
