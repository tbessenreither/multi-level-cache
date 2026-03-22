<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Interface;

use Redis;
use RedisCluster;

interface RedisClientProviderInterface
{
    public function getRedisClient(): Redis|RedisCluster;
}
