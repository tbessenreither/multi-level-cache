<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;

class CompilerPass implements CompilerPassInterface
{
    private const TEMPLATE_DIR = 'Templates';

    public function process(ContainerBuilder $container): void
    {
        $this->processTwig($container);

        $this->processClass($container, MultiLevelCacheFactory::class);
        $this->processClass($container, InvalidatorService::class);
        $dataCollectorDefinition = $this->processClass($container, MultiLevelCacheDataCollector::class);
        $dataCollectorDefinition->setArgument('$appEnv', "%env(APP_ENV)%");
        $dataCollectorDefinition->setArgument('$enhancedDataCollection', '%env(bool:defined:MLC_COLLECT_ENHANCED_DATA)%');
        $container->setAlias(
            MultiLevelCacheDataCollector::NAME,
            MultiLevelCacheDataCollector::class,
        )->setPublic(true);
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

    private function processClass(ContainerBuilder $container, string $classInstance): Definition
    {
        if (!$container->hasDefinition($classInstance)) {
            $definition = new Definition($classInstance);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $definition->setPublic(true);
            $container->setDefinition($classInstance, $definition);

            return $definition;
        } else {
            $definition = $container->getDefinition($classInstance);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $definition->setPublic(true);
            return $definition;

        }
    }

}
