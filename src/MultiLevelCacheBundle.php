<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;
use Tbessenreither\MultiLevelCache\DataCollector\MultiLevelCacheDataCollector;
use Tbessenreither\MultiLevelCache\DependencyInjection\Compiler\CompilerPass;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;

class MultiLevelCacheBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if ($container->isCompiled()) {
            return;
        }

        $container->addCompilerPass(new CompilerPass());

        $dataCollectorDefinition = $this->processClass($container, MultiLevelCacheDataCollector::class);
        $dataCollectorDefinition->addTag('data_collector', [
            'id' => MultiLevelCacheDataCollector::NAME,
            'template' => MultiLevelCacheDataCollector::TEMPLATE,
            'priority' => 334,
        ]);
        $dataCollectorDefinition->setArgument('$appEnv', "%env(APP_ENV)%");
        $dataCollectorDefinition->setArgument('$enhancedDataCollection', '%env(bool:defined:MLC_COLLECT_ENHANCED_DATA)%');
        $container->setAlias(
            MultiLevelCacheDataCollector::NAME,
            MultiLevelCacheDataCollector::class,
        )->setPublic(true);


        $this->processClass($container, MultiLevelCacheFactory::class);
        $this->processClass($container, InvalidatorService::class);
    }

    private function processClass(ContainerBuilder $container, string $classInstance): Definition
    {
        if (!$container->has($classInstance)) {
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
