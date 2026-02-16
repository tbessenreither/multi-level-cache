<?php declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Dto\MethodCallObject;
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;
use Tbessenreither\MultiLevelCache\Service\MultiLevelCacheService;


class InvalidatorService
{
	private MultiLevelCacheService $multiLevelCacheService;

	public function __construct(
		MultiLevelCacheFactory $multiLevelCacheFactory,
	) {
		$this->multiLevelCacheService = $multiLevelCacheFactory->createDefault2LevelCache();
	}

	public function invalidateCacheForClass(string $classString, string $method): void
	{
		$generateKey = KeyGeneratorService::getKey('mlc_invalidator', new MethodCallObject($classString, $method, []));


	}

}