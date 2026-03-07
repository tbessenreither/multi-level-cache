<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\DependencyInjection\Compiler\CompilerPass;

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

}
