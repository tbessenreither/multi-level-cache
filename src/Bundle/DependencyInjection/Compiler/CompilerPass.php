<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Bundle\DependencyInjection\Compiler;

use Redis;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;


class CompilerPass implements CompilerPassInterface
{
	private const TEMPLATE_DIR = 'Templates';

	public function process(ContainerBuilder $container): void
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

		$this->processClass($container, MultiLevelCacheFactory::class);
		$this->processClass($container, InvalidatorService::class);
	}

	private function getRootDir(): string
	{
		return rtrim(dirname(__DIR__, 3), '/');
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
			$definition->setPublic(true);
			return $definition;

		}
	}

}
