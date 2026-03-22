<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Factory;

use Redis;
use RedisCluster;
use RedisException;
use Symfony\Component\Cache\Adapter\RedisAdapter;
use Tbessenreither\MultiLevelCache\Interface\RedisClientProviderInterface;

class RedisClientFactory implements RedisClientProviderInterface
{
    public function __construct(
        private Redis|RedisCluster|null $redisClient = null,
        private ?string $redisDsn = null,
        private ?RedisClientProviderInterface $redisClientProvider = null,
    ) {
        if ($this->redisClient === null && $this->redisClientProvider === null && $this->redisDsn === null) {
            throw new RedisException('No Redis client, Redis DSN or Redis client provider given to RedisClientFactory. Please provide at least one of these to create a Redis client.');
        }
    }

    public function getRedisClient(): Redis|RedisCluster
    {
        if ($this->redisClient === null) {
            if ($this->redisClientProvider !== null) {
                $this->redisClient = $this->redisClientProvider->getRedisClient();
            } elseif ($this->redisDsn !== null) {
                $redisClient = RedisAdapter::createConnection($this->redisDsn);

                if ($redisClient instanceof Redis || $redisClient instanceof RedisCluster) {
                    $this->redisClient = $redisClient;
                } else {
                    throw new RedisException('MLC LazyRedisClientFactory: RedisAdapter did not return a Redis or RedisCluster instance. Other types are not supported at the moment. Please open an issue if you need support for other Redis types.');
                }
            }
        }

        return $this->redisClient;
    }
}
