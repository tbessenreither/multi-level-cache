<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Service;

use DateTime;
use Generator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use RuntimeException;
use stdClass;
use Symfony\Component\Stopwatch\Stopwatch;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\KeyGeneratorService;
use Tbessenreither\MultiLevelCache\DataCollector\CacheStatistics;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\Dto\BulkConfig;
use Tbessenreither\MultiLevelCache\Dto\CacheObjectWrapperDto;
use Tbessenreither\MultiLevelCache\Dto\DataCollectorIssueOccurrenceDto;
use Tbessenreither\MultiLevelCache\Enum\BulkListTypeEnum;
use Tbessenreither\MultiLevelCache\Interface\MultiLevelCacheImplementationInterface;
use Tbessenreither\MultiLevelCache\Service\BulkMapperService;
use Tbessenreither\MultiLevelCache\Service\Implementations\InMemoryCacheService;
use Tbessenreither\MultiLevelCache\Service\MultiLevelCacheService;
use Tbessenreither\MultiLevelCache\Tests\CachedServiceGenerator\Service\TestSrc\TestServiceA;
use Tbessenreither\MultiLevelCache\Tests\Service\Implementations\NoopImplementation;
use TypeError;

#[CoversClass(MultiLevelCacheService::class)]
#[CoversClass(CacheObjectWrapperDto::class)]
#[CoversClass(CacheStatistics::class)]
#[UsesClass(InMemoryCacheService::class)]
#[UsesClass(MlcCacheableMethod::class)]
#[UsesClass(MlcCacheableService::class)]
#[UsesClass(MethodCallObject::class)]
#[UsesClass(KeyGeneratorService::class)]
#[UsesClass(BulkConfig::class)]
#[UsesClass(BulkMapperService::class)]
#[UsesClass(DataCollectorIssueOccurrenceDto::class)]
class MultiLevelCacheServiceTest extends TestCase
{
    private CacheObjectWrapperDto $testObj;
    private int $testTtlSeconds = 60;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testObj = new CacheObjectWrapperDto(object: (object) ['data' => 'testdata'], ttlSeconds: $this->testTtlSeconds);
    }

    #[DataProvider('provideConfigurations')]

    public function testAllSetConfigurations(array $optionConfiguration): void
    {
        $this->fixYieldedConfiguration($optionConfiguration);

        $service = new MultiLevelCacheService(
            caches: $optionConfiguration['caches'],
            writeL0OnSet: $optionConfiguration['writeL0OnSet'],
            stopwatch: $optionConfiguration['stopwatchMockObject'],
            cacheDataCollector: $optionConfiguration['dataCollectorMockObject'],
            ttlRandomnessSeconds: $optionConfiguration['ttlRandomnessSeconds'],
        );

        $this->setupTestExpectationsForStopwatch($optionConfiguration, 4);
        $this->setupTestExpectationsForCacheSet($optionConfiguration, 4);

        $service->set('testkey1', $this->testObj->getObject(), $this->testObj->getTtlLeft());
        $service->set('testkey2', $this->testObj->getObject(), $this->testObj->getTtlLeft());
        $service->set('testkey3', $this->testObj->getObject(), $this->testObj->getTtlLeft());
        $service->set('testkey4', $this->testObj->getObject(), $this->testObj->getTtlLeft());
    }

    #[DataProvider('provideConfigurations')]

    public function testAllGetFilledConfigurations(array $optionConfiguration): void
    {
        $this->fixYieldedConfiguration($optionConfiguration);

        $service = new MultiLevelCacheService(
            caches: $optionConfiguration['caches'],
            writeL0OnSet: $optionConfiguration['writeL0OnSet'],
            stopwatch: $optionConfiguration['stopwatchMockObject'],
            cacheDataCollector: $optionConfiguration['dataCollectorMockObject'],
            ttlRandomnessSeconds: $optionConfiguration['ttlRandomnessSeconds'],
        );

        $cacheCount = count($optionConfiguration['caches']);
        $this->setupTestExpectationsForCacheGetFilled($optionConfiguration);

        $service->get('testkey1', function () {
            return $this->testObj->getObject();
        }, $this->testTtlSeconds);
    }

    #[DataProvider('provideConfigurations')]

    public function testAllGetPartiallyFilledConfigurations(array $optionConfiguration): void
    {
        $this->fixYieldedConfiguration($optionConfiguration);

        $service = new MultiLevelCacheService(
            caches: $optionConfiguration['caches'],
            writeL0OnSet: $optionConfiguration['writeL0OnSet'],
            stopwatch: $optionConfiguration['stopwatchMockObject'],
            cacheDataCollector: $optionConfiguration['dataCollectorMockObject'],
            ttlRandomnessSeconds: $optionConfiguration['ttlRandomnessSeconds'],
        );

        $cacheCount = count($optionConfiguration['caches']);
        $this->setupTestExpectationsForCacheGetPartiallyFilled($optionConfiguration);

        $service->get('testkey1', function () {
            return $this->testObj->getObject();
        }, $this->testTtlSeconds);
    }

    #[DataProvider('provideConfigurations')]

    public function testAllGetEmptyConfigurations(array $optionConfiguration): void
    {
        $this->fixYieldedConfiguration($optionConfiguration);

        $service = new MultiLevelCacheService(
            caches: $optionConfiguration['caches'],
            writeL0OnSet: $optionConfiguration['writeL0OnSet'],
            stopwatch: $optionConfiguration['stopwatchMockObject'],
            cacheDataCollector: $optionConfiguration['dataCollectorMockObject'],
            ttlRandomnessSeconds: $optionConfiguration['ttlRandomnessSeconds'],
        );

        $cacheCount = count($optionConfiguration['caches']);
        $this->setupTestExpectationsForCacheGetEmpty($optionConfiguration);

        $service->get('testkey1', function () {
            return $this->testObj->getObject();
        }, $this->testTtlSeconds);
    }

    #[DataProvider('provideConfigurations')]

    public function testAllDeleteConfigurations(array $optionConfiguration): void
    {
        $this->fixYieldedConfiguration($optionConfiguration);

        $service = new MultiLevelCacheService(
            caches: $optionConfiguration['caches'],
            writeL0OnSet: $optionConfiguration['writeL0OnSet'],
            stopwatch: $optionConfiguration['stopwatchMockObject'],
            cacheDataCollector: $optionConfiguration['dataCollectorMockObject'],
            ttlRandomnessSeconds: $optionConfiguration['ttlRandomnessSeconds'],
        );

        $this->setupTestExpectationsForStopwatch($optionConfiguration, 1);
        $this->setupTestExpectationsForCacheDelete($optionConfiguration, 1, 'testkey1');

        $service->delete('testkey1');
    }

    public function testNoCachesThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('At least one cache implementation must be provided');

        new MultiLevelCacheService(
            caches: [],
            writeL0OnSet: true,
            stopwatch: null,
            cacheDataCollector: null,
            ttlRandomnessSeconds: 0,
        );
    }

    public function testExceedingMaxCacheLevelsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Maximum number of cache levels exceeded');

        $caches = [];
        for ($i = 0; $i < MultiLevelCacheService::MAX_CACHE_LEVELS + 1; $i++) {
            /** @var MockObject&MultiLevelCacheImplementationInterface $cacheMock */
            $caches[] = $this->createMock(MultiLevelCacheImplementationInterface::class);
        }

        new MultiLevelCacheService(
            caches: $caches,
            writeL0OnSet: true,
            stopwatch: null,
            ttlRandomnessSeconds: 0,
        );
    }

    #[DataProvider('provideInvalidCacheImplementations')]

    public function testInvalidCacheImplementationsThrowsException(mixed $invalidCacheImplementation): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('All cache implementations must implement MultiLevelCacheImplementationInterface');

        new MultiLevelCacheService(
            caches: $invalidCacheImplementation,
            writeL0OnSet: true,
            stopwatch: null,
            ttlRandomnessSeconds: 0,
        );
    }

    #[DataProvider('provideInvalidStopwatchImplementations')]

    public function testInvalidStopwatchImplementationThrowsException(mixed $invalidStopwatchImplementation): void
    {
        $this->expectException(TypeError::class);

        new MultiLevelCacheService(
            caches: [
                $this->createMock(MultiLevelCacheImplementationInterface::class),
            ],
            writeL0OnSet: true,
            stopwatch: $invalidStopwatchImplementation,
            ttlRandomnessSeconds: 0,
        );
    }

    public function testNegativeTtlRandomnessSecondsThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('ttlRandomnessSeconds must be non-negative');

        new MultiLevelCacheService(
            caches: [
                $this->createMock(MultiLevelCacheImplementationInterface::class),
            ],
            writeL0OnSet: true,
            stopwatch: null,
            ttlRandomnessSeconds: -1,
        );
    }

    public function testGetReturnsNullWhenNoCallableProvidedAndAllCachesMiss(): void
    {
        $key = 'missing_key';

        $cacheMock1 = $this->createMock(MultiLevelCacheImplementationInterface::class);
        $cacheMock2 = $this->createMock(MultiLevelCacheImplementationInterface::class);

        // both caches should be queried and return null
        $cacheMock1
            ->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn(null);

        $cacheMock2
            ->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn(null);

        // no writes or deletes should happen when no callable is provided
        $cacheMock1->expects($this->never())->method('set');
        $cacheMock1->expects($this->never())->method('delete');
        $cacheMock2->expects($this->never())->method('set');
        $cacheMock2->expects($this->never())->method('delete');

        $service = new MultiLevelCacheService(
            caches: [$cacheMock1, $cacheMock2],
            writeL0OnSet: true,
            stopwatch: null,
            cacheDataCollector: null,
            ttlRandomnessSeconds: 0,
        );

        $result = $service->get($key, null, 300);
        $this->assertNull($result);
    }

    public function testGetReturnsBetaDecayedObject(): void
    {
        $key = 'missing_key';

        $ObjectWrapperMock = $this->createMock(CacheObjectWrapperDto::class);
        $ObjectWrapperMock->method('isBetaDecayed')->willReturn(true);

        $cacheMock1 = $this->createMock(MultiLevelCacheImplementationInterface::class);
        $cacheMock2 = $this->createMock(MultiLevelCacheImplementationInterface::class);

        // both caches should be queried and return null
        $cacheMock1
            ->expects($this->once())
            ->method('get')
            ->with($key)
            ->willReturn($ObjectWrapperMock);

        $cacheMock2
            ->expects($this->never())
            ->method('get');

        // no writes or deletes should happen when no callable is provided
        $cacheMock1->expects($this->once())->method('set');
        $cacheMock1->expects($this->never())->method('delete');
        $cacheMock2->expects($this->once())->method('set');
        $cacheMock2->expects($this->never())->method('delete');

        $service = new MultiLevelCacheService(
            caches: [$cacheMock1, $cacheMock2],
            writeL0OnSet: true,
            stopwatch: null,
            cacheDataCollector: null,
            ttlRandomnessSeconds: 0,
        );

        $result = $service->get($key, fn () => 'some string', 300);
        $this->assertEquals('some string', $result);
    }

    public function testGetCallableThrowsExceptionRegistersCacheMissAndRethrows(): void
    {
        $this->expectException(RuntimeException::class);
        $key = 'error_key';

        $cacheMock1 = $this->createMock(MultiLevelCacheImplementationInterface::class);
        $cacheMock2 = $this->createMock(MultiLevelCacheImplementationInterface::class);

        $cacheMock1->expects($this->once())->method('get')->with($key)->willReturn(null);
        $cacheMock2->expects($this->once())->method('get')->with($key)->willReturn(null);

        $cacheMock1->expects($this->never())->method('set');
        $cacheMock2->expects($this->never())->method('set');

        $dataCollectorMock = $this->createMock(MultiLevelCacheDataCollector::class);
        $dataCollectorMock->expects($this->any())->method('isCollecting')->willReturn(true);
        $dataCollectorMock->expects($this->atLeastOnce())->method('registerCacheMiss');

        $service = new MultiLevelCacheService(
            caches: [$cacheMock1, $cacheMock2],
            writeL0OnSet: true,
            stopwatch: null,
            cacheDataCollector: $dataCollectorMock,
            ttlRandomnessSeconds: 0,
        );

        $service->get($key, function () {
            throw new RuntimeException('boom');
        }, 100);
    }

    public function testGetBulkConfigured(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        $expectedResults = [];
        foreach ($keys as $key) {
            $expectedResults[$key] = [
                'key' => $key,
                'value' => 'value for ' . $key,
            ];
        }

        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: [$keys],
        );

        $inMemoryCache = new InMemoryCacheService(5);
        $inMemoryCache->set(
            key: KeyGeneratorService::getKey(
                methodCallObject: $methodCallObject->clone(arguments: ['key2']),
                throw: true,
            ),
            object: new CacheObjectWrapperDto(
                object: [
                    'key' => 'key2',
                    'value' => 'value for key2',
                ],
                ttlSeconds: 300,
            ),
        );


        $service = new MultiLevelCacheService(
            caches: [$inMemoryCache],
            writeL0OnSet: true,
            stopwatch: null,
            cacheDataCollector: null,
            ttlRandomnessSeconds: 0,
        );

        $sourceWasCalled = false;
        $entriesRequestedFromSource = [];

        $results = $service->getBulk(
            keys: $keys,
            callable: function (array $keys) use (&$sourceWasCalled, &$entriesRequestedFromSource) {
                $sourceWasCalled = true;
                $entriesRequestedFromSource = $keys;
                $values = [];
                foreach ($keys as $key) {
                    $values[] = [
                        'key' => $key,
                        'value' => 'value for ' . $key,
                    ];
                }

                return $values;
            },
            methodCallObject: $methodCallObject,
            mlcCacheableMethodAttribute: new MlcCacheableMethod(
                ttlSeconds: 5,
                bulkConfig: new BulkConfig(
                    identifierSelector: 'key',
                    listType: BulkListTypeEnum::ARRAY_ASSOC,
                ),
            ),
        );

        $this->assertTrue($sourceWasCalled, 'The source callable should have been called since bulk is not configured');
        $this->assertEquals(['key1', 'key3'], $entriesRequestedFromSource, 'The keys requested from the source should match the original keys');

        $this->assertEquals($expectedResults, $results, 'The results from the getBulk callable should match the expected results');
    }

    public function testGetBulkMisconfigured(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        $expectedResults = [];
        foreach ($keys as $key) {
            $expectedResults[] = [
                'key' => $key,
                'value' => 'value for ' . $key,
            ];
        }

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->atLeastOnce())
            ->method('error');

        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: [$keys],
        );

        $inMemoryCache = new InMemoryCacheService(5);
        $inMemoryCache->set(
            key: KeyGeneratorService::getKey(
                methodCallObject: $methodCallObject->clone(arguments: ['key2']),
                throw: true,
            ),
            object: new CacheObjectWrapperDto(
                object: [
                    'key' => 'key2',
                    'value' => 'value for key2',
                ],
                ttlSeconds: 300,
            ),
        );

        $service = new MultiLevelCacheService(
            caches: [$inMemoryCache],
            logger: $loggerMock,
        );

        $sourceWasCalled = false;
        $entriesRequestedFromSource = [];

        $results = $service->getBulk(
            keys: $keys,
            callable: function (array $keys) use (&$sourceWasCalled, &$entriesRequestedFromSource) {
                $sourceWasCalled = true;
                $entriesRequestedFromSource = $keys;
                $values = [];
                foreach ($keys as $key) {
                    $values[] = [
                        'key' => $key,
                        'value' => 'value for ' . $key,
                    ];
                }

                return $values;
            },
            methodCallObject: $methodCallObject,
            mlcCacheableMethodAttribute: new MlcCacheableMethod(
                ttlSeconds: 5,
                bulkConfig: null,
            ),
        );

        $this->assertTrue($sourceWasCalled, 'The source callable should have been called since bulk is not configured');
        $this->assertEquals($keys, $entriesRequestedFromSource, 'The keys requested from the source should match the original keys');

        $this->assertEquals($expectedResults, $results, 'The results from the getBulk callable should match the expected results');
    }

    public function testGetBulkWithInvalidSourceResponse(): void
    {
        $keys = ['key1', 'key2', 'key3'];
        $expectedResults = [];
        foreach ($keys as $key) {
            $expectedResults[$key] = 'value for ' . $key;
        }

        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock
            ->expects($this->atLeastOnce())
            ->method('error');

        $methodCallObject = new MethodCallObject(
            class: TestServiceA::class,
            method: 'testMethod',
            arguments: [$keys],
        );

        $service = new MultiLevelCacheService(
            caches: [new InMemoryCacheService(5)],
            logger: $loggerMock,
        );

        $results = $service->getBulk(
            keys: $keys,
            callable: function (array $keys) {
                $response = [];
                foreach ($keys as $key) {
                    $response[$key] = 'value for ' . $key;
                }

                return $response;
            },
            methodCallObject: $methodCallObject,
            mlcCacheableMethodAttribute: new MlcCacheableMethod(
                ttlSeconds: 5,
                bulkConfig: new BulkConfig(
                    identifierSelector: 'key',
                    listType: BulkListTypeEnum::ARRAY_ASSOC,
                ),
            ),
        );

        $this->assertEquals($expectedResults, $results, 'The results should be an empty array when the source response is invalid');
    }

    public function testCacheStringValue(): void
    {
        $cacheKey = 'testkey';
        $cacheValue = 'test_cache_string';

        $cacheService = new MultiLevelCacheService(
            caches: [
                new InMemoryCacheService(
                    5,
                ),
            ],
            writeL0OnSet: true,
            stopwatch: null,
            ttlRandomnessSeconds: 0,
        );

        $cacheService->set(
            key: $cacheKey,
            object: $cacheValue,
            ttlSeconds: 300,
        );

        $cacheService->get(key: $cacheKey);

        $this->assertSame($cacheValue, $cacheService->get(key: $cacheKey));
    }

    public function testInputTypes(): void
    {
        $cacheService = new MultiLevelCacheService(
            caches: [
                new InMemoryCacheService(
                    5,
                ),
            ],
            writeL0OnSet: true,
            stopwatch: null,
            ttlRandomnessSeconds: 0,
        );

        $validInputs = [
            #Objects
            (object) ['property' => 'value'],
            #Strings
            'string value',
            #Int
            123,
            #Float
            45.67,
            #Bool
            true,
            false,
            #Array
            ['array', 'of', 'values'],
            #Null
            null,
        ];

        foreach ($validInputs as $input) {
            $cacheService->set(key: 'key', object: $input, ttlSeconds: 300);
            $this->assertSame($input, $cacheService->get(key: 'key'));
        }
    }

    public function testTryFetchFromCacheLevelWhileReadDisabled(): void
    {
        $inMemoryCache = new InMemoryCacheService(5);

        $service = new MultiLevelCacheService(
            caches: [$inMemoryCache],
            cacheReadDisabled:true,
        );

        $result = $service->get('testkey');

        $this->assertNull($result, 'Result should be null when cache read is disabled');
    }

    public function testGetCacheImplementationForInvalidLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $methodReleflection = new ReflectionMethod(MultiLevelCacheService::class, 'getCacheImplementation');
        $methodReleflection->setAccessible(true);
        $service = new MultiLevelCacheService(
            caches: [new InMemoryCacheService(5)],
        );
        $methodReleflection->invoke($service, 999);
    }

    public function testConstructorHelperSetupDataCollector(): void
    {
        $dataCollectorMock = $this->createMock(MultiLevelCacheDataCollector::class);
        $dataCollectorMock
            ->expects($this->atLeastOnce())
            ->method('isCollecting')
            ->willReturn(true);
        $dataCollectorMock
            ->expects($this->atLeastOnce())
            ->method('addInstance');

        $service = new MultiLevelCacheService(
            caches: [new InMemoryCacheService(5)],
            cacheDataCollector: $dataCollectorMock,
        );

        $methodReflection = new ReflectionMethod(MultiLevelCacheService::class, 'getStatisticsObject');
        $methodReflection->setAccessible(true);
        $statisticsObject = $methodReflection->invoke($service, 0);
        $this->assertInstanceOf(CacheStatistics::class, $statisticsObject, 'The getStatisticsObject method should return an instance of CacheStatistics');
        $this->assertIsArray($statisticsObject->getConfigData(), 'The config data in the statistics object should be an array');
    }

    private function fixYieldedConfiguration(array &$configuration)
    {
        $caches = [];
        for ($i = 0; $i < $configuration['caches']; $i++) {
            /** @var MockObject&MultiLevelCacheImplementationInterface $cacheMock */
            $caches[] = $this->createMock(MultiLevelCacheImplementationInterface::class);
        }
        $configuration['caches'] = $caches;

        if ($configuration['stopwatch'] === true) {
            $configuration['stopwatchMockObject'] = $this->createMock(Stopwatch::class);
        } else {
            $configuration['stopwatchMockObject'] = null;
        }


        $configuration['dataCollectorMockObject'] = null;
        if ($configuration['dataCollector'] !== null) {
            $configuration['dataCollectorMockObject'] = $this->createMock(MultiLevelCacheDataCollector::class);
            if ($configuration['dataCollector'] === true) {
                $configuration['dataCollectorMockObject']
                    ->method('isCollecting')
                    ->willReturn(true);
            } elseif ($configuration['dataCollector'] === false) {
                $configuration['dataCollectorMockObject']
                    ->method('isCollecting')
                    ->willReturn(false);
            }
        }
    }

    private function setupTestExpectationsForStopwatch(array $optionConfiguration, int $count): void
    {
        if ($optionConfiguration['stopwatch'] !== false && $optionConfiguration['dataCollector'] === true) {
            $optionConfiguration['stopwatchMockObject']
                ->expects($this->atLeast($count))
                ->method('start');
            $optionConfiguration['stopwatchMockObject']
                ->expects($this->atLeast($count))
                ->method('stop');
        } else {
            if ($optionConfiguration['stopwatchMockObject'] !== null) {
                $optionConfiguration['stopwatchMockObject']
                    ->expects($this->never())
                    ->method('start');
                $optionConfiguration['stopwatchMockObject']
                    ->expects($this->never())
                    ->method('stop');
            }
        }
    }

    /**
     * @param array{
     *     caches: list<MockObject|MultiLevelCacheImplementationInterface>,
     *     writeL0OnSet: bool,
     *     stopwatch: Stopwatch|null,
     *     ttlRandomnessSeconds: int
     * } $optionConfiguration
     */
    private function setupTestExpectationsForCacheSet(array $optionConfiguration, int $count): void
    {
        $indexOfLastCache = count($optionConfiguration['caches']) - 1;
        $countOfCaches = count($optionConfiguration['caches']);

        foreach ($optionConfiguration['caches'] as $index => $cacheMock) {
            $expectedSetCalls = 0;
            if (
                $index === 0
                && (
                    $optionConfiguration['writeL0OnSet'] === true
                    || (
                        $optionConfiguration['writeL0OnSet'] === true
                        && $countOfCaches === 1
                    )
                )
            ) {
                $expectedSetCalls = $count;
            } elseif ($index === $indexOfLastCache) {
                $expectedSetCalls = $count;
            }

            $cacheMock
                ->expects($this->exactly($expectedSetCalls))
                ->method('set')
                ->with(
                    $this->isString(),
                    $this->isInstanceOf(CacheObjectWrapperDto::class)
                )
                ->willReturnCallback(function (string $key, CacheObjectWrapperDto $cacheObject) use ($optionConfiguration) {
                    $this->assertEquals($this->testObj->getObject(), $cacheObject->getObject());

                    if ($optionConfiguration['ttlRandomnessSeconds'] === 0) {
                        $this->assertEqualsWithDelta(
                            $this->testObj->getTtl(),
                            $cacheObject->getTtl(),
                            1,
                        );
                    }

                    $this->assertGreaterThanOrEqual(
                        $this->testObj->getTtl() - 1,
                        $cacheObject->getTtl(),
                    );
                    $this->assertLessThanOrEqual(
                        $this->testObj->getTtl() + $optionConfiguration['ttlRandomnessSeconds'] + 1,
                        $cacheObject->getTtl(),
                    );
                });

            $cacheMock
                ->expects($this->never())
                ->method('delete');

            $cacheMock
                ->expects($this->never())
                ->method('get');
        }
    }

    /**
     * @param array{
     *     caches: list<MockObject|MultiLevelCacheImplementationInterface>,
     *     writeL0OnSet: bool,
     *     stopwatch: Stopwatch|null,
     *     ttlRandomnessSeconds: int
     * } $optionConfiguration
     */
    private function setupTestExpectationsForCacheGetFilled(array $optionConfiguration): void
    {
        foreach ($optionConfiguration['caches'] as $index => $cacheMock) {
            $expectedGetCalls = 1;
            if ($optionConfiguration['writeL0OnSet'] === true && $index !== 0) {
                $expectedGetCalls = 0;
            }

            $cacheMock
                ->expects($this->exactly($expectedGetCalls))
                ->method('get')
                ->willReturnCallback(function (string $key) use ($optionConfiguration, $index) {
                    if ($optionConfiguration['writeL0OnSet'] === true && $index === 0) {
                        return $this->testObj;
                    }

                    return null;
                });


            $expectedSetCalls = 0;
            if ($optionConfiguration['writeL0OnSet'] !== true && $index === count($optionConfiguration['caches']) - 1) {
                $expectedSetCalls = 1;
            }
            $cacheMock
                ->expects($this->exactly($expectedSetCalls))
                ->method('set');

            $cacheMock
                ->expects($this->never())
                ->method('delete');
        }
    }

    /**
     * @param array{
     *     caches: list<MockObject|MultiLevelCacheImplementationInterface>,
     *     writeL0OnSet: bool,
     *     stopwatch: Stopwatch|null,
     *     ttlRandomnessSeconds: int
     * } $optionConfiguration
     */
    private function setupTestExpectationsForCacheGetPartiallyFilled(array $optionConfiguration): void
    {
        $indexOfLastCache = count($optionConfiguration['caches']) - 1;

        foreach ($optionConfiguration['caches'] as $index => $cacheMock) {
            $cacheMock
                ->expects($this->exactly(1))
                ->method('get')
                ->willReturnCallback(function (string $key) use ($index, $indexOfLastCache) {
                    if ($index === $indexOfLastCache) {
                        return $this->testObj;
                    }
                });

            $expectedSetCalls = 0;
            if (
                count($optionConfiguration['caches']) > 1
                && (
                    $index === 0
                    || $index === $indexOfLastCache - 1
                )
            ) {
                $expectedSetCalls = 1;
            }
            $cacheMock
                ->expects($this->exactly($expectedSetCalls))
                ->method('set')
                ->willReturnCallback(function () use ($index, $expectedSetCalls, $optionConfiguration) {
                    $this->assertTrue($expectedSetCalls === 1, "Cache Level " . $index . " should not have been written to with " . count($optionConfiguration['caches']) . " cache levels and writeL0OnSet=" . ($optionConfiguration['writeL0OnSet'] ? 'true' : 'false'));
                });

            $cacheMock
                ->expects($this->never())
                ->method('delete');
        }
    }

    /**
     * @param array{
     *     caches: list<MockObject|MultiLevelCacheImplementationInterface>,
     *     writeL0OnSet: bool,
     *     stopwatch: Stopwatch|null,
     *     ttlRandomnessSeconds: int
     * } $optionConfiguration
     */
    private function setupTestExpectationsForCacheGetEmpty(array $optionConfiguration): void
    {
        $indexOfLastCache = count($optionConfiguration['caches']) - 1;

        foreach ($optionConfiguration['caches'] as $index => $cacheMock) {
            $cacheMock
                ->expects($this->exactly(1))
                ->method('get')
                ->willReturnCallback(function (string $key) {
                    return null;
                });


            $expectedSetCalls = 0;
            if ($optionConfiguration['writeL0OnSet'] === true) {
                if ($index === 0 || $index === $indexOfLastCache) {
                    $expectedSetCalls = 1;
                }
            } elseif ($optionConfiguration['writeL0OnSet'] === false) {
                if ($index === $indexOfLastCache) {
                    $expectedSetCalls = 1;
                }
            }
            $cacheMock
                ->expects($this->exactly($expectedSetCalls))
                ->method('set')
                ->willReturnCallback(function () use ($index, $expectedSetCalls, $optionConfiguration) {
                    $this->assertTrue($expectedSetCalls === 1, "Cache Level " . $index . " should not have been written to with " . count($optionConfiguration['caches']) . " cache levels and writeL0OnSet=" . ($optionConfiguration['writeL0OnSet'] ? 'true' : 'false'));
                });

            $cacheMock
                ->expects($this->never())
                ->method('delete');
        }
    }

    /**
     * @param array{
     *     caches: list<MockObject|MultiLevelCacheImplementationInterface>,
     *     writeL0OnSet: bool,
     *     stopwatch: Stopwatch|null,
     *     ttlRandomnessSeconds: int
     * } $optionConfiguration
     */
    private function setupTestExpectationsForCacheDelete(array $optionConfiguration, int $count, string $expectedKey): void
    {
        foreach ($optionConfiguration['caches'] as $cacheMock) {
            $cacheMock
                ->expects($this->exactly($count))
                ->method('delete')
                ->with(
                    $this->isString(),
                )
                ->willReturnCallback(function (string $key) use ($expectedKey) {
                    $this->assertIsString($key);
                    $this->assertEquals($expectedKey, $key);
                });

            $cacheMock
                ->expects($this->never())
                ->method('get');

            $cacheMock
                ->expects($this->never())
                ->method('set');
        }
    }

    public static function provideConfigurations(): Generator
    {
        $options = [
            'caches' => [
                1,
                2,
                3,
            ],
            'writeL0OnSet' => [
                true,
                false
            ],
            'stopwatch' => [
                true,
                false,
            ],
            'dataCollector' => [
                null,
                true,
                false,
            ],
            'ttlRandomnessSeconds' => [
                0,
                60,
                PHP_INT_MAX,
            ],
        ];

        foreach ($options['caches'] as $caches) {
            foreach ($options['writeL0OnSet'] as $writeL0OnSet) {
                foreach ($options['stopwatch'] as $stopwatch) {
                    foreach ($options['ttlRandomnessSeconds'] as $ttlRandomnessSeconds) {
                        foreach ($options['dataCollector'] as $dataCollector) {
                            $optionConfiguration = [
                                'caches' => $caches,
                                'writeL0OnSet' => $writeL0OnSet,
                                'stopwatch' => $stopwatch,
                                'ttlRandomnessSeconds' => $ttlRandomnessSeconds,
                                'dataCollector' => $dataCollector,
                            ];
                            yield [
                                $optionConfiguration,
                            ];
                        }
                    }
                }
            }
        }
    }

    public static function provideInvalidCacheImplementations(): Generator
    {
        $invalids = [
            null,
            new stdClass(),
            123,
            'invalid string',


            new class () {
            },
            fn () => null,
            new DateTime(),
            array(),
        ];

        $validImplementation = new NoopImplementation();

        // yield only one invalid
        foreach ($invalids as $invalid) {
            yield [[$invalid]];
        }

        // yield one invalid and one valid
        foreach ($invalids as $invalid) {
            yield [[$invalid, $validImplementation]];
        }

        // yield valid and one invalid
        foreach ($invalids as $invalid) {
            yield [[$validImplementation, $invalid]];
        }

        // yield invalid, valid, invalid
        foreach ($invalids as $invalid) {
            yield [[$invalid, $validImplementation, $invalid]];
        }

        // yield valid, invalid, valid
        foreach ($invalids as $invalid) {
            yield [[$validImplementation, $invalid, $validImplementation]];
        }
    }

    public static function provideInvalidStopwatchImplementations(): Generator
    {
        yield [new stdClass()];
        yield [123];
        yield ['invalid string'];
        yield [


            new class () {
            }
        ];
        yield [fn () => null];
        yield [new DateTime()];
    }

}
