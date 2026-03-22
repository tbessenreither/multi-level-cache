<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Tests\Factory;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Redis;
use RedisCluster;
use RedisException;
use Tbessenreither\MultiLevelCache\Factory\RedisClientFactory;
use Tbessenreither\MultiLevelCache\Interface\RedisClientProviderInterface;

#[CoversClass(RedisClientFactory::class)]


class RedisClientFactoryTest extends TestCase
{
    public function testConstructorWithoutValidClients(): void
    {
        $this->expectException(RedisException::class);
        new RedisClientFactory();
    }

    public function testGetRedisClientWithProvidedClient(): void
    {
        $redisClient = new Redis();
        $factory = new RedisClientFactory(redisClient: $redisClient);
        $this->assertSame($redisClient, $factory->getRedisClient());
    }

    public function testGetRedisClientWithProvidedDsn(): void
    {
        $this->expectException(InvalidArgumentException::class);
        // This test assumes that a Redis server is running on localhost:6379
        $factory = new RedisClientFactory(redisDsn: 'redis://localhost:6379');
        $client = $factory->getRedisClient();
    }

    public function testGetRedisClientWithProvidedClientProvider(): void
    {
        $redisClient = new Redis();
        $clientProvider = new class ($redisClient) implements RedisClientProviderInterface {
            public function __construct(private Redis|RedisCluster $client)
            {
            }

            public function getRedisClient(): Redis|RedisCluster
            {
                return $this->client;
            }
        };

        $factory = new RedisClientFactory(redisClientProvider: $clientProvider);
        $this->assertSame($redisClient, $factory->getRedisClient());
    }

    public static function wrapClient(Redis|RedisCluster $client): RedisClientFactory
    {
        return new RedisClientFactory(redisClient: $client);
    }
}
