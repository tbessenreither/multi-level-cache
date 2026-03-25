<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service;

use BadMethodCallException;
use Redis;
use RedisCluster;
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
    private ?bool $isCluster = null;
    private bool $connectionChecked = false;
    private ?bool $isConnected = null;

    public function __construct(
        private RedisClientProviderInterface $redisClientProvider,
    ) {
    }

    public function isConnected(): bool
    {
        if (!$this->isCluster()) {
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
        if (!$this->isCluster()) {
            return $this->getClient()->scan($iterator, $pattern, $count);
        }

        $allKeys = [];
        $masters = $this->_masters();

        foreach ($masters as $master) {
            $nodeIterator = 0;

            do {
                $keys = $this->getClient()->scan($nodeIterator, $master, $pattern, $count);

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
        if (!$this->isCluster()) {
            return $this->getClient()->flushAll();
        }

        return $this->flushCluster(true);
    }

    public function flushDb(?bool $sync = null): Redis|bool
    {
        if (!$this->isCluster()) {
            return $this->getClient()->flushDb($sync);
        }

        return $this->flushCluster(false);
    }

    public function getHost(): string
    {
        if (!$this->isCluster()) {
            return $this->getClient()->getHost();
        }

        $masters = $this->_masters();
        if (!empty($masters)) {
            return $masters[0][0];
        }

        throw new RedisException('No master nodes found in Redis Cluster');
    }

    public function getPort(): int
    {
        if (!$this->isCluster()) {
            return $this->getClient()->getPort();
        }

        $masters = $this->_masters();
        if (!empty($masters)) {
            return (int)$masters[0][1];
        }

        throw new RedisException('No master nodes found in Redis Cluster');
    }

    public function _masters(): array
    {
        if (!$this->isCluster()) {
            return [[$this->getClient()->getHost(), $this->getClient()->getPort()]];
        } else {
            return $this->getClient()->_masters();
        }
    }

    public function hexpire(string $key, int $ttl, array $fields): array|false
    {
        try {
            return $this->getClient()->hexpire($key, $ttl, $fields);
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * This method delegates calls to the underlying Redis client so we don't need to keep track of methods that are not explicitly defined in this class.
     */
    public function __call($name, $arguments)
    {
        if (method_exists($this->getClient(), $name)) {
            return $this->getClient()->$name(...$arguments);
        } else {
            throw new BadMethodCallException("Method $name does not exist on Redis client");
        }
    }

    private function isCluster(): bool
    {
        if ($this->isCluster === null) {
            $this->isCluster = $this->getClient() instanceof RedisCluster;
        }

        return $this->isCluster;
    }

    private function getClient(): Redis|RedisCluster
    {
        if (!$this->connectionChecked) {
            $this->connectionChecked = true;
            $this->isConnected = $this->isConnected();
        }
        if ($this->isConnected === false) {
            throw new CacheConnectionException('Could not connect to Redis server.');
        }

        return $this->redisClientProvider->getRedisClient();
    }

    private function flushCluster(bool $all): Redis|bool
    {
        if (!$this->isCluster()) {
            throw new BadMethodCallException('flushCluster can only be called on a Redis Cluster client');
        }

        $masters = $this->_masters();

        $flushSuccess = true;
        foreach ($masters as $master) {
            $host = $master[0];
            $port = $master[1];

            $tempClient = new Redis();

            try {
                if ($tempClient->connect($host, $port, 0.5)) {
                    if ($all) {
                        $tempClient->flushAll();
                    } else {
                        $tempClient->flushDb();
                    }
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
        if ($all) {
            return $this->flushAll();
        } else {
            return $this->flushDb();
        }
    }
}
