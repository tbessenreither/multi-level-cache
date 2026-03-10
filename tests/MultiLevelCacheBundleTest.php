<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\DependencyInjection\Compiler\CompilerPass;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\MultiLevelCacheBundle;

#[CoversClass(MultiLevelCacheBundle::class)]

class MultiLevelCacheBundleTest extends TestCase
{
    private ContainerBuilder&MockObject $containerBuilder;

    public function setUp(): void
    {
        parent::setUp();
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

    }
    public function testContainerWhenNotCompiled(): void
    {
        $compilerPass = new MultiLevelCacheBundle();

        $this->containerBuilder
            ->expects($this->once())
            ->method('isCompiled')
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->once())
            ->method('addCompilerPass')
            ->with(static::isInstanceOf(CompilerPass::class));

        $compilerPass->build($this->containerBuilder);
    }
    public function testContainerWhenCompiled(): void
    {
        $compilerPass = new MultiLevelCacheBundle();

        $this->containerBuilder
            ->expects($this->once())
            ->method('isCompiled')
            ->willReturn(true);

        $this->containerBuilder
            ->expects($this->never())
            ->method('addCompilerPass');

        $compilerPass->build($this->containerBuilder);
    }

    #[DataProvider('classesAreRegisteredDataProvider')]
    public function testClassesAreRegistered(bool $hasDefinition): void
    {
        $expectedClasses = self::expectedClassesProvider();

        $compilerPass = new MultiLevelCacheBundle();

        $this->containerBuilder
            ->expects($this->once())
            ->method('isCompiled')
            ->willReturn(false);

        $this->containerBuilder
            ->expects($this->atLeast(count($expectedClasses)))
            ->method('has')
            ->willReturn($hasDefinition);

        $submittedClasses = [];

        if (!$hasDefinition) {
            $this->containerBuilder
                ->expects($this->atLeast(count($expectedClasses)))
                ->method('setDefinition')
                ->willReturnCallback(function (string $id, Definition $definition) use (&$submittedClasses) {
                    $submittedClasses[] = $id;

                    return $definition;
                });
        } else {
            $definitionMock = $this->createMock(Definition::class);
            $definitionMock->expects($this->atLeast(count($expectedClasses)))
            ->method('setAutowired');
            $definitionMock->expects($this->atLeast(count($expectedClasses)))
            ->method('setAutoconfigured');
            $definitionMock->expects($this->atLeast(count($expectedClasses)))
            ->method('setPublic');

            $this->containerBuilder
                ->expects($this->atLeast(count($expectedClasses)))
                ->method('getDefinition')
                ->willReturnCallback(function (string $id) use ($definitionMock, &$submittedClasses): MockObject&Definition {
                    $submittedClasses[] = $id;

                    return $definitionMock;
                });
        }

        $compilerPass->build($this->containerBuilder);
        foreach ($expectedClasses as $expectedClass) {
            $this->assertContains($expectedClass, $submittedClasses, "Expected class $expectedClass was not processed by Compiler Pass.");
        }
    }

    public static function classesAreRegisteredDataProvider(): Generator
    {
        yield 'Classes are not registered' => [false];
        yield 'Classes are already registered' => [true];
    }

    public static function expectedClassesProvider(): array
    {
        return [
            MultiLevelCacheFactory::class,
            InvalidatorService::class,
            MultiLevelCacheDataCollector::class,
        ];
    }

}
