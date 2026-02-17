# MultiLevelCache Symfony Bundle

A high-performance, multi-level caching system for Symfony applications. This bundle provides a flexible, extensible, and developer-friendly way to combine multiple cache backends (memory, Redis, file, etc.) for optimal speed and reliability.

---

## Features
- Multi-level cache (memory, Redis, file, etc.)
- Pluggable cache implementations
- Cache statistics and profiling (Symfony Profiler integration)
- Beta decay and TTL randomization to prevent stampedes
- Easy integration with Symfony Dependency Injection
- Extensible via interfaces and factories
- Exception handling and diagnostics
- Autogeneration of cached version of services
- Cache invalidation by class/method patterns for cached services

---

## Requirements
- PHP >= 8.4
- Symfony >= 7.4
- Redis (optional, for Redis cache level)

---

## Installation

Install via Composer from GitHub:

1. Add the repository to your `composer.json`:
   ```json
   {
     "repositories": [
       {
         "type": "vcs",
         "url": "https://github.com/tbessenreither/multi-level-cache"
       }
     ]
   }
   ```
2. Require the package:
   ```bash
   composer require tbessenreither/multi-level-cache
   ```
3. Enable the Bundle in `config/bundles.php`:
   ```php
   Tbessenreither\MultiLevelCache\Bundle\MultiLevelCacheBundle::class => ['all' => true],
   ```
4. Configure Environment Variables as needed:
   - `REDIS_DSN` (if using Redis)
   - `MLC_DISABLE_READ` (optional, disables cache reads)
   - `MLC_COLLECT_ENHANCED_DATA` (optional, enables enhanced data collection but has performance impact)

---

## Usage

You can use the multi-level cache in two ways:

### 1. Setup

#### 1.1 Manual Setup in Your Service/Controller

You can instantiate and configure the `MultiLevelCacheService` directly in your constructor, providing the cache implementations you want to use. The Redis client should be injected via dependency injection:

```php
use Tbessenreither\MultiLevelCache\Service\MultiLevelCacheService;
use Tbessenreither\MultiLevelCache\Service\Implementations\InMemoryCacheService;
use Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService;
use Redis; // or RedisCluster

public function __construct(Redis $redisClient) {
    $inMemory = new InMemoryCacheService();
    $redis = new DirectRedisCacheService($redisClient);
    $this->cache = new MultiLevelCacheService([
        $inMemory,
        $redis,
    ]);
}
```

#### 1.2 Using the Factory (Recommended for Symfony DI)

Inject the `MultiLevelCacheFactory` and use it to create a pre-configured cache service:

```php
use Tbessenreither\MultiLevelCache\Factory\MultiLevelCacheFactory;

public function __construct(MultiLevelCacheFactory $cacheFactory) {
    $this->cache = $cacheFactory->createDefault2LevelCache();
}
```

### 2. Using the Cache (Identical for Both Approaches)

Once you have a `MultiLevelCacheService` instance (from either method above), usage is identical:

```php
$this->cache->set('my_key', $object, 3600);
$value = $this->cache->get('my_key', function() {
    // This callback is called only if there is a cache miss.
    // Return the value to be cached and returned.
    return $expensiveComputationOrFetch();
}, 3600);
$this->cache->delete('my_key');
```

## Cached Service Generation

> **⚠️ Note: This feature is still experimental and may be subject to changes in future releases.**


The bundle also provides a way to autogenerate cached versions of your services. By using the `CachedServiceGenerator`, you can create cached proxies for your existing services without modifying their code. This allows you to easily add caching to any service method by simply configuring the generator.
### Setup
If you're using [Copycat](https://github.com/tbessenreither/php-copycat), the Package automatically provides ddev commands to generate a service (i.e `ddev mlc-make App.Service.TestService`), and update all existing generated services (i.e `ddev mlc-update`).

If you don't use Copycat, first of all, why?, second: It's fine. You can find the commands in the `/bin` directory and copy them wherever you'd like.

Make sure you've added the bundle to Symfony Bundles if you're using that one.

#### How to create and updated cached services

Easy, first you need the namespace of the Service you want to create a cached version of. Let's say `App\Service\TestService`. Oh damn, backslash sucks in cli let's make that `App.Service.TestService`. Now you can just run the make command
```bash
ddev mlc-make App.Service.TestService
```
which will output
```text
$ ddev mlc-make App.Service.TestService
========================================================
          Create or Update a cached service...
========================================================


Parsing command line arguments...
| Argument   | Value                     |
------------------------------------------
| service    | App.Service.TestService   |

Resolving service class for 'App.Service.TestService'...
Service class found: App\Service\TestService

------------------------------------
     Generating cached service.
------------------------------------

Use statement for interface 'App\Interface\Service\TestServiceInterface' does not exists in file: /var/www/html/src/Service/TestService.php
Attempt adding it.
Added interface 'App\Interface\Service\TestServiceInterface' to class 'App\Service\TestService' in file: /var/www/html/src/Service/TestService.php

Cached service generated successfully.

| Key         | Value                                                          |
--------------------------------------------------------------------------------
| Class       | /var/www/html/src/Service/TestServiceCached.php                |
| Interface   | /var/www/html/src/Interface/Service/TestServiceInterface.php   |
```
As you can see it generated the TestServiceCached Class as well as an interface linking the cached and non cached versions together. It also added the interface to the non cached version of the service.

This means they are feature compatible and you can switch them out however you like. Neat.

But wait, how does the script know how long to cache method responses for and what to cache and what shoudn't? Well... it doesn't. Right now it's not caching anything. Let's fix that.

For this we make use of the MlcCachableMethod Attribute.

You just add this above any Method of `TestService` you want to have cached and tell it for how long via the `ttlSeconds` argument.
```php
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachableMethod;

    #[MlcCachableMethod(ttlSeconds: 600)]
    public function reverse(string $input): string
    {
        return strrev($input);
    }
```

Okay, now that we have annotated all Methods we want cached we need to regenerate all the cached versions of Services we did. But don't sweat, it's just one single command.
```bash
ddev mlc-update
```
Output will be something allong those lines:
```text
$ ddev mlc-update
===================================================
          Updating all cached services...
===================================================

--------------------------------------------------------
     Starting update process for cached services...
--------------------------------------------------------

| Service                         | Status    | Message   |
-----------------------------------------------------------
| App\Service\TestServiceCached   | updated   |           |

Cached services updated. See output for details.
```

You get a table with all services that where updated including any problems that might have occured.

That's it, everything is now up to date and ready to use. You can just inject the cached version directly or use the Interface and a entry in your services.yaml to descide which version to inject.
Caching is now no longer part of your Business logic.

#### Cache Invalidation

This feature comes with it's own Invalidator Service.

It allows you to invalidate the cache based on class or based on class and method.

```php

use App\Service\TestService;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;

public function __construct(
   private InvalidatorService $invalidatorService,
) {
}

// Invalidate cache for a specific class
$this->invalidatorService->invalidateCacheForClass(TestService::class);

// Invalidate cache for a specific method
$this->invalidatorService->invalidateCacheForMethod(TestService::class, 'methodName');
```

---

## Configuration Example

> **Note:** While it is possible to configure the MultiLevelCacheService directly in `services.yaml`, this approach is discouraged. The intended and recommended way is to use the `MultiLevelCacheFactory` for setup and configuration.

```yaml
# config/services.yaml
services:
    Tbessenreither\MultiLevelCache\Service\MultiLevelCacheService:
        arguments:
            $caches:
                - '@Tbessenreither\MultiLevelCache\Service\Implementations\InMemoryCacheService'
                - '@Tbessenreither\MultiLevelCache\Service\Implementations\DirectRedisCacheService'
            $writeL0OnSet: true
            $ttlRandomnessSeconds: 10
            $cacheGroupName: 'default'
```

---

## Testing

Run PHPUnit tests:
```bash
ddev composer test
```

---

## License

MIT
