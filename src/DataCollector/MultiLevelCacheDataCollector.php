<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\DataCollector;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpKernel\DataCollector\DataCollectorInterface;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorCacheInfoDto;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueDto;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueOccurrenceDto;
use Tbessenreither\MultiLevelCache\Enum\InfoEnum;
use Tbessenreither\MultiLevelCache\Interface\DataCollectorIssueEnumInterface;
use Throwable;

class MultiLevelCacheDataCollector extends DataCollector implements DataCollectorInterface
{
    private const int LOW_HITRATE_NOTICE_THRESHOLD = 9;

    private bool $isInitialized = false;
    private bool $processedAdditionalIssues = false;
    public const NAME = 'tbessenreither.multi_level_cache_service_collector';
    public const TEMPLATE = '@TbessenreitherMultiLevelCache/Profiler/multi_level_cache_service_collector.html.twig';

    public function __construct(
        #[Autowire('%env(string:APP_ENV)%')]
        private readonly string $appEnv,
        #[Autowire('%env(defined:MLC_COLLECT_ENHANCED_DATA)%')]
        private readonly bool $enhancedDataCollection = false,
    ) {
        $this->data['collectedIssues'] = [];
        $this->isInitialized = true;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function addInstance(string $groupName, string $name, int $cacheLevel, string $class, CacheStatistics $statistics): void
    {
        if (!$this->isCollecting()) {
            return;
        }

        if (!isset($this->data['grouped_instances'][$groupName])) {
            $this->data['grouped_instances'][$groupName] = [];
        }

        $this->data['grouped_instances'][$groupName][$cacheLevel] = new DataCollectorCacheInfoDto(
            groupName: $groupName,
            name: $name,
            level: $cacheLevel,
            class: $class,
            statistics: $statistics,
        );
    }

    public function isActive(): bool
    {
        return !empty($this->data['grouped_instances']);
    }

    public function getGroupedInstances(): array
    {
        return $this->data['grouped_instances'] ?? [];
    }

    public function collect(Request $request, Response $response, ?Throwable $exception = null): void
    {
    }

    public function isCollecting(): bool
    {
        if (!$this->isInitialized) {
            return false;
        }

        return $this->appEnv === 'dev';
    }

    public function isEnhancedDataCollectionEnabled(): bool
    {
        if (!$this->isInitialized) {
            return false;
        }

        return $this->enhancedDataCollection;
    }

    public function registerCacheHit(string $cacheGroupName, int $cacheLevel, string $key): void
    {
        if (!$this->isCollecting()) {
            return;
        }
        $statisticsObject = $this->getStatisticsObject($cacheGroupName, $cacheLevel);
        $statisticsObject?->addHit();

        if (!$this->isEnhancedDataCollectionEnabled()) {
            return;
        }
        $statisticsObject?->logEnhancedUsageData(
            key: $key,
            isHit: true,
        );
    }

    public function registerCacheMiss(string $cacheGroupName, int $cacheLevel, string $key): void
    {
        if (!$this->isCollecting()) {
            return;
        }
        $statisticsObject = $this->getStatisticsObject($cacheGroupName, $cacheLevel);
        $statisticsObject?->addMiss();

        if (!$this->isEnhancedDataCollectionEnabled()) {
            return;
        }
        $statisticsObject?->logEnhancedUsageData(
            key: $key,
            isHit: false,
        );
    }

    public function registerBetaDecay(string $cacheGroupName, int $cacheLevel, string $key): void
    {
        if (!$this->isCollecting()) {
            return;
        }
        $statisticsObject = $this->getStatisticsObject($cacheGroupName, $cacheLevel);
        $statisticsObject?->addBetaDecay();
    }

    public function getLevels(): array
    {
        $levels = [];
        foreach ($this->getGroupedInstances() as $groupName => $instancesByLevel) {
            $levels = array_keys($instancesByLevel);
            $levels = array_merge($levels, $levels);
        }
        $levels = array_unique($levels);
        sort($levels);

        return $levels;
    }

    public function getAdapters(): array
    {
        $adapters = [];
        foreach ($this->getGroupedInstances() as $groupName => $instancesByLevel) {
            foreach ($instancesByLevel as $instance) {
                $adapters[] = $instance->getName();
            }
        }
        $adapters = array_unique($adapters);

        return $adapters;
    }

    public function getSummary(?string $adapterName = null): array
    {
        $summaryGroups = [
            'io' => [
                'writes' => [
                    'value' => 0,
                    'decimals' => 0,
                    'unit' => '',
                ],
                'read hits' => [
                    'value' => 0,
                    'decimals' => 0,
                    'unit' => '',
                ],
                'read misses' => [
                    'value' => 0,
                    'decimals' => 0,
                    'unit' => '',
                ],
                'beta misses' => [
                    'value' => 0,
                    'decimals' => 0,
                    'unit' => '',
                ],
                'hit rate' => [
                    'value' => 0.0,
                    'decimals' => 0,
                    'unit' => '%',
                ],
                'deletes' => [
                    'value' => 0,
                    'decimals' => 0,
                    'unit' => '',
                ],
            ],
            'timings' => [
                'time write' => [
                    'value' => 0.0,
                    'decimals' => 2,
                    'unit' => 's',
                ],
                'time read' => [
                    'value' => 0.0,
                    'decimals' => 2,
                    'unit' => 's',
                ],
                'time delete' => [
                    'value' => 0.0,
                    'decimals' => 2,
                    'unit' => 's',
                ],
                'time total' => [
                    'value' => 0.0,
                    'decimals' => 2,
                    'unit' => 's',
                ],
            ],
        ];

        foreach ($this->getGroupedInstances() as $instancesByLevel) {
            foreach ($instancesByLevel as $instance) {
                if ($adapterName !== null && $instance->getName() !== $adapterName) {
                    continue;
                }

                /**
                 * @var DataCollectorCacheInfoDto $instance
                 */
                $stats = $instance->getStatistics();
                $summaryGroups['io']['writes']['value'] += $stats->getWrites();
                $summaryGroups['io']['read hits']['value'] += $stats->getHits();
                $summaryGroups['io']['read misses']['value'] += $stats->getMisses();
                $summaryGroups['io']['beta misses']['value'] += $stats->getBetaDecays();
                $summaryGroups['io']['deletes']['value'] += $stats->getDeletes();
                $summaryGroups['timings']['time write']['value'] += $stats->getRuntimeSecondsWrite();
                $summaryGroups['timings']['time read']['value'] += $stats->getRuntimeSecondsRead();
                $summaryGroups['timings']['time delete']['value'] += $stats->getRuntimeSecondsDelete();
                $summaryGroups['timings']['time total']['value'] += $stats->getRuntimeSecondsTotal();
            }
        }

        $totalHits = $summaryGroups['io']['read hits']['value'];
        $totalMisses = $summaryGroups['io']['read misses']['value'];
        $total = $totalHits + $totalMisses;
        $hitRatePercent = $total === 0 ? 0.0 : ($totalHits / $total) * 100;
        $summaryGroups['io']['hit rate']['value'] = $hitRatePercent;

        return $summaryGroups;
    }

    public function raiseIssue(DataCollectorIssueEnumInterface $issue, ?DataCollectorIssueOccurrenceDto $occurrence = null, bool $skipCollectingChecks = false): void
    {
        if (
            !$skipCollectingChecks
            && !$this->isCollecting()
        ) {
            return;
        }

        $issue = $this->getCollectedIssue($issue);

        if (($skipCollectingChecks || $this->isEnhancedDataCollectionEnabled()) && $occurrence) {
            $issue->addOccurrence($occurrence);
        }
    }

    /**
     * @return array<string, DataCollectorIssueDto>
     */
    public function getIssues(): array
    {
        return $this->data['collectedIssues'] ?? [];
    }

    public function hasIssues(): bool
    {
        $this->processAdditionalIssues();

        return !empty($this->data['collectedIssues']);
    }

    private function getStatisticsObject(string $cacheGroupName, int $cacheLevel): ?CacheStatistics
    {
        /**
         * @var DataCollectorCacheInfoDto|null $dataCollectorCacheInfoDto
         */
        $dataCollectorCacheInfoDto = $this->data['grouped_instances'][$cacheGroupName][$cacheLevel] ?? null;
        $fetchedObject = $dataCollectorCacheInfoDto?->getStatistics();

        if ($fetchedObject === null) {
            $fetchedObject = new CacheStatistics();
            if (
                !isset($this->data['grouped_instances'][$cacheGroupName])
                || !isset($this->data['grouped_instances'][$cacheGroupName][$cacheLevel])
            ) {
                return null;
            }
        }

        return $fetchedObject;
    }


    private function getCollectedIssue(DataCollectorIssueEnumInterface $issue): DataCollectorIssueDto
    {
        if (!isset($this->data['collectedIssues'][$issue->getName()])) {
            $this->data['collectedIssues'][$issue->getName()] = DataCollectorIssueDto::fromEnum($issue);
        }

        return $this->data['collectedIssues'][$issue->getName()];

    }

    /**
     * This method processes the collected data and raises issues based on it's findings.
     * As it's only ever ran during the rendering of the profiler page and does not create additional data it can skip the isCollecting and isEnhancedDataCollectionEnabled checks.
     */
    //Todo: This method needs coverage!
    private function processAdditionalIssues(): void
    {
        if ($this->processedAdditionalIssues === true) {
            return;
        }
        $this->processedAdditionalIssues = true;

        foreach ($this->getGroupedInstances() as $instancesByLevel) {
            /**
             * @var DataCollectorCacheInfoDto $instance
             */
            foreach ($instancesByLevel as $instance) {
                $stats = $instance->getStatistics();

                if (
                    ($stats->getHits() === 0 && $stats->getMisses() > self::LOW_HITRATE_NOTICE_THRESHOLD)
                    || ($stats->getMisses() > 10 && $stats->getHitRate() < 0.05)
                ) {
                    $this->raiseIssue(
                        issue: InfoEnum::LOW_HITRATE_ON_CACHE_LEVEL,
                        occurrence: new DataCollectorIssueOccurrenceDto(
                            affectedCacheGroup: $instance->getGroupName(),
                            affectedKeys: [],
                            context: [
                                'cache_level' => $instance->getLevel(),
                                'hit_rate_percent' => number_format($stats->getHitRate() * 100, 2),
                                'hits' => $stats->getHits(),
                                'misses' => $stats->getMisses(),
                                'noticeThreshold' => self::LOW_HITRATE_NOTICE_THRESHOLD,
                            ],
                        ),
                        skipCollectingChecks: true,
                    );
                }
            }
        }
    }

}
