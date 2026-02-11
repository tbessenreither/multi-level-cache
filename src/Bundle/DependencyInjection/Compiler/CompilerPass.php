<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;


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

		$this->processMultiLevelCacheFactory($container);
	}

	private function getRootDir(): string
	{
		return rtrim(dirname(__DIR__, 3), '/');
	}

	private function processMultiLevelCacheFactory(ContainerBuilder $container): void
	{
		if (!$container->hasDefinition(MultiLevelCacheFactory::class)) {
			$definition = new Definition(MultiLevelCacheFactory::class);
			$definition->setAutowired(true);
			$definition->setAutoconfigured(true);
			$definition->setPublic(true);
			$container->setDefinition(MultiLevelCacheFactory::class, $definition);
		} else {
			$container->getDefinition(MultiLevelCacheFactory::class)->setPublic(true);
		}
	}

}
