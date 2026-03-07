<?php

declare(strict_types=1);

namespace Tests\Tbessenreither\MultiLevelCache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tbessenreither\MultiLevelCache\DependencyInjection\Compiler\CompilerPass;
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

}
