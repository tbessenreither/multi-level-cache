<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service;

use BadMethodCallException;
use Redis;
use RedisClusterException;
use RedisException;
use Tbessenreither\MultiLevelCache\Exception\CacheConnectionException;
use Tbessenreither\MultiLevelCache\Interface\RedisClientProviderInterface;
use Throwable;

/**
 * Provides all the Methods of \Redis and \RedisCluster, but abstracts the differences between them.
 * Primarily used to make methods like scan and flushDb work on clusters reliable.
 *
 * @mixin Redis
 */
class RedisAbstractionService
{
    public function __construct(
        private RedisClientProviderInterface $redisClientProvider,
    ) {
        if ($this->isConnected() === false) {
            throw new CacheConnectionException('Could not connect to Redis');
        }
    }

    public function isConnected(): bool
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return $this->redisClientProvider->getRedisClient()->isConnected();
        }

        $masters = $this->_masters();

        foreach ($masters as $master) {
            try {
                $this->redisClientProvider->getRedisClient()->ping($master);

                return true;
            } catch (RedisClusterException) {

                continue;
            }
        }

        return false;
    }

    public function scan(?int &$iterator, string $pattern, int $count): array|false
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return $this->redisClientProvider->getRedisClient()->scan($iterator, $pattern, $count);
        }

        $allKeys = [];
        $masters = $this->_masters();

        foreach ($masters as $master) {
            $nodeIterator = 0;

            do {
                $keys = $this->redisClientProvider->getRedisClient()->scan($nodeIterator, $master, $pattern, $count);

                if ($keys !== false) {
                    $allKeys = array_merge($allKeys, $keys);
                }
            } while ($nodeIterator > 0);
        }

        $iterator = 0;

        return empty($allKeys) ? false : array_unique($allKeys);
    }

    public function flushAll(): Redis|bool
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return $this->redisClientProvider->getRedisClient()->flushAll();
        }

        $masters = $this->_masters();

        $flushSuccess = true;
        foreach ($masters as $master) {
            $host = $master[0];
            $port = $master[1];

            $tempClient = new Redis();

            try {
                if ($tempClient->connect($host, $port, 0.5)) {
                    $tempClient->flushAll();
                }
            } catch (RedisException) {
                $flushSuccess = false;
            } finally {
                if ($tempClient->isConnected()) {
                    $tempClient->close();
                }
            }
        }

        return $flushSuccess;
    }

    public function flushDb(?bool $sync = null): Redis|bool
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return $this->redisClientProvider->getRedisClient()->flushDb($sync);
        }

        $masters = $this->_masters();

        $flushSuccess = true;
        foreach ($masters as $master) {
            $host = $master[0];
            $port = $master[1];

            $tempClient = new Redis();

            try {
                if ($tempClient->connect($host, $port, 0.5)) {
                    $tempClient->flushDB();
                }
            } catch (RedisException) {
                $flushSuccess = false;
            } finally {
                if ($tempClient->isConnected()) {
                    $tempClient->close();
                }
            }
        }

        return $flushSuccess;
    }

    public function getHost(): string
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return $this->redisClientProvider->getRedisClient()->getHost();
        }

        $masters = $this->_masters();
        if (!empty($masters)) {
            return $masters[0][0];
        }

        throw new RedisException('No master nodes found in Redis Cluster');
    }

    public function getPort(): int
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return $this->redisClientProvider->getRedisClient()->getPort();
        }

        $masters = $this->_masters();
        if (!empty($masters)) {
            return (int)$masters[0][1];
        }

        throw new RedisException('No master nodes found in Redis Cluster');
    }

    public function _masters(): array
    {
        if ($this->redisClientProvider->getRedisClient() instanceof Redis) {
            return [[$this->redisClientProvider->getRedisClient()->getHost(), $this->redisClientProvider->getRedisClient()->getPort()]];
        } else {
            return $this->redisClientProvider->getRedisClient()->_masters();
        }
    }

    public function hexpire(string $key, int $ttl, array $fields): array|false
    {
        try {
            return $this->redisClientProvider->getRedisClient()->hexpire($key, $ttl, $fields);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * This method delegates calls to the underlying Redis client so we don't need to keep track of methods that are not explicitly defined in this class.
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->redisClientProvider->getRedisClient(), $name)) {
            return $this->redisClientProvider->getRedisClient()->$name(...$arguments);
        } else {
            throw new BadMethodCallException("Method $name does not exist on Redis client");
        }
    }
}
