<?php

declare(strict_types=1);

namespace Tests\Tbessenreither\MultiLevelCache\DependencyInjection\Compiler;

use Generator;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\DependencyInjection\Compiler\CompilerPass;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;

#[CoversClass(CompilerPass::class)]

class CompilerPassTest extends TestCase
{
    private ContainerBuilder&MockObject $containerBuilder;

    public function setUp(): void
    {
        parent::setUp();
        $this->containerBuilder = $this->createMock(ContainerBuilder::class);

    }
    public function testContainerHasTwig(): void
    {
        $compilerPass = new CompilerPass();

        $this->containerBuilder
            ->expects($this->once())
            ->method('has')
                ->with('twig')
                ->willReturn(false);

        $definitionMock = $this->createMock(Definition::class);
        $definitionMock
            ->expects($this->never())
            ->method('addMethodCall');

        $this->containerBuilder
            ->expects($this->never())
            ->method('getDefinition')
            ->with('twig.loader.native_filesystem')
            ->willReturn($definitionMock);

        $compilerPass->process($this->containerBuilder);
    }
    public function testContainerDoesntHaveTwig(): void
    {
        $compilerPass = new CompilerPass();

        $this->containerBuilder
            ->expects($this->once())
            ->method('has')
                ->with('twig')
                ->willReturn(true);

        $definitionMock = $this->createMock(Definition::class);
        $definitionMock
            ->expects($this->once())
            ->method('addMethodCall')
            ->with('addPath');

        $this->containerBuilder
            ->expects($this->once())
            ->method('getDefinition')
            ->with('twig.loader.native_filesystem')
            ->willReturn($definitionMock);

        $compilerPass->process($this->containerBuilder);
    }

    #[DataProvider('classesAreRegisteredDataProvider')]
    public function testClassesAreRegistered(bool $hasDefinition): void
    {
        $expectedClasses = self::expectedClassesProvider();

        $compilerPass = new CompilerPass();

        $this->containerBuilder
            ->expects($this->once())
            ->method('has')
                ->with('twig')
                ->willReturn(false);

        $this->containerBuilder
            ->expects($this->atLeast(count($expectedClasses)))
            ->method('hasDefinition')
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
                ->willReturnCallback(function (string $id) use ($definitionMock, &$submittedClasses) {
                    $submittedClasses[] = $id;
                    return $definitionMock;
                });
        }

        $compilerPass->process($this->containerBuilder);
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
