<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class CompilerPass implements CompilerPassInterface
{
    private const TEMPLATE_DIR = 'Templates';

    public function process(ContainerBuilder $container): void
    {
        $this->processTwig($container);
    }

    private function processTwig(ContainerBuilder $container): void
    {
        if (!$container->has('twig')) {
            return;
        }

        $definition = $container->getDefinition('twig.loader.native_filesystem');

        $rootDir = $this->getRootDir();

        $definition->addMethodCall('addPath', [
            $rootDir . '/' . self::TEMPLATE_DIR,
            'TbessenreitherMultiLevelCache',
        ]);
    }

    private function getRootDir(): string
    {
        return rtrim(dirname(__DIR__, 2), '/');
    }

}
