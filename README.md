# MultiLevelCache Symfony Bundle

A high-performance, multi-level caching system for Symfony applications. This bundle provides a flexible, extensible, and developer-friendly way to combine multiple cache backends (memory, Redis, file, etc.) for optimal speed and reliability.

It has two parts to it.
1. The Multi Level Cache (MLC)
2. The Cached Service Generator (CSG)

Of the two parts, the CSG is the intended way of using the MLC. But the MLC itself also has it's use cases and can be used directly without the CSG. Both are documented in sections bellow.

## Installation

Install via Composer from GitHub:

1. Require the package:
   ```bash
   composer require tbessenreither/multi-level-cache
   ```

> [!TIP]
> The following steps can be automated by using [Copycat](https://github.com/tbessenreither/copycat)

2. Enable the Bundle in `config/bundles.php`:
   ```php
   Tbessenreither\MultiLevelCache\MultiLevelCacheBundle::class => ['all' => true],
   ```
3. Configure Environment Variables as needed:
   - `REDIS_DSN` (if using Redis, so basically always)
   - `MLC_DISABLE_READ` (optional, disables cache reads if it exists at all, no matter the value)
   - `MLC_COLLECT_ENHANCED_DATA` (optional, enables enhanced data collection but has performance impact)


## Basic concept

Its a fast multi level cache consisting, in it's standard configuration, of:
- a fast In Memory Ring Cache
- and a second Level Redis Cache

## Most notable features

- Faster and much more memory efficient than the symfony cache (We love it, but we needed something faster)
- Deduplication of cached objects on read
- Cache statistics and profiling (Symfony Profiler integration)
- Beta decay and TTL randomization to prevent stampedes


# Cached Service Generator (CSG)

As mentioned above, the main way of using the MLC is via the Cached Service Generator.

[Cached Service Generator Documentation](documentation/mlc-cachedservicegenerator.md)


# MultiLevelCacheService and MultiLevelCacheFactory

For details on how to use the MLC Service and Factory directly.

[MultiLevelCacheService and MultiLevelCacheFactory Documentation](documentation/mlc-service-and-factory.md)

## License

MIT

# Contributors

Awesome people who contributed to this package

- [dsentker](https://github.com/dsentker)

## Honorable mentions
thanks for support go to:
- [Robinson-Software-Development](https://github.com/robinson-software-development)