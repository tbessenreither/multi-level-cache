Index:
- [The Cached Service Generator](#the-cached-service-generator)
- [InvalidatorService](#invalidatorservice)

---

# The Cached Service Generator

This is the main thing you hopefully will interact with as the goal of this package is to make manual cache implementations a thing of the past.

## Overview

This is the main way of using the MLC. The basic idea is to implement your services without any caching logic and then wrapp them within an API Compatible Wrapper (Front Loaded Caching).

To create a cached version of a service you can use the `ddev mlc-make App.Service.TestService` command.

Cacheable Methods are marked as such with the `#[MlcCacheableMethod(ttlSeconds: 300)]` attribute.
Everything else is hands off.

## Basic relation between the Source and the Cached Service

![How Cached Services work](images/svg/CacheGeneratorPrinciple.svg)


## Usage

### Making your Service Ready for Caching

Given this example `TestService`

```php
<?php
declare(strict_types=1);

namespace Tbessenreither\Example\Service;

use Tbessenreither\Example\Entity\ExampleEntity;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;

readonly class TestService
{
	public function getOffers(string $id): ExampleEntity
	{
		return $this->getOffersByIri(sprintf('%s/%s', self::getApiUrl(), $id));
	}

	#[MlcCacheableMethod(ttlSeconds: 600)]
	public function getOffersByIri(string $iri): ExampleEntity
	{
		$brand = $this->getObjectContent(
			pageId: $iri,
			query: [],
			className: ExampleEntity::class
		);

		return $brand;
	}

	// [...]
}
```


### Generating the cached service

We've added the Attribute to the Endpoint we want to cache. And now we need to run `ddev mlc-make Tbessenreither.Example.Service` to generate our Cache wrapper. Now the following will happen.

1. It will create a `TestServiceInterface` for the `TestService`.
2. The Generator will create a wrapper under the name `TestServiceCached` that implements the `TestServiceInterface` to ensure compatibility
3. It will add the `TestServiceInterface` to the original Class
4. All Interfaces implemented by the original `TestService` will also automatically be implemented by the `TestServiceCached` class. This is to ensure that the cached version can be used as a drop in replacement without any issues.

The Cache Wrapper is stored in the same directory as your Original `TestService` postfixed with `Cached` giving you `TestServiceCached`.

The `TestServiceInterface` will be put into the appropriate Interface directory.

Example Output of the `ddev mlc-make` command:
![mlc-make Command Output Example](images/mlc-make.png)

### Using the cached service

You basically have two options here.
1. You can either swap out the `TestService` for `TestServiceCached` directly as a drop in replacement
2. Or you can inject the `TestServiceInterface` and configure the Version you want to use via the `services.yaml`

Do whatever fits your needs best. The MLC has no opinions on how to do this.

### Updating the cached services

This is nice and all. But what if i change something in my `TestService`? Do i need to update everything again?

No, good god no.

You just run `ddev mlc-update`. This command will auto detect all Cache Wrappers and update them + the interfaces with the latest methods and `MlcCacheableMethod` annotations.

If any service can't be updated (There are some reasons why this might happen) it will show this in the status collumn and print a detailed reason in the Message collumn.

Example Output of the `ddev mlc-update` command:
![mlc-update Command Output Example](images/mlc-update.png)

## More about how the MLC generates the cached service

### Automatic Interface Transfer

The CSG will automatically transfer all interfaces implemented by the original service to the cached version. This means that if your original service implements `SomeInterface`, the cached version will also implement `SomeInterface` without you having to do anything extra.

#### Exceptions:
- Interfaces that define a constructor are not transferred as the cached version needs to have a different constructor signature. No way around this one.


## Advanced Usage

The attributes `MlcCacheableMethod` and `MlcCacheableService` control more than just which methods are cached and how long the TTL is.

They also give you control over how the cache is set up and behaves. Let's go over the options here.

### MlcCacheableMethod

You already know this one and it's required `ttlSeconds` parameter. But there is also:
- `dataVersion`,
- `bulkConfig`, and
- `additionalCacheKeyGetter`

#### dataVersion
`dataVersion` is a string that is added to the cache key and represents the version number of the returned data.

As the MLC allows you to share, for example, the Redis Cache between different Services, you might have different versions of a package deployed at one time. This can happen during a rollout or based on dependencies. To prevent the different versions of your service poisoning each others cache, you can separate them out by using the `dataVersion` parameter. Whenever you make a breaking change to your returned data, you should update the `dataVersion`. This way the two caches can just co-exist until all services are updated and the old cache entries are decayed.

Note: the invalidator will delete all dataVersions of a given service and or method. So you don't need to run it on every version.


#### additionalCacheKeyGetter

If your Method is dependent on lets say a mandator or an API-Domain the cache Key can not be generated based on the arguments of the method alone.
To tell the MLC that there is more to this you can set this attribute with a string of the name of a method in the class that returns all additional content your class depends on.

The only requirement is that it can be passed through `serialize()`.

This will make the MLC aware of your context and prevent key colissions.

Usage example:
```php
class TestService implements TestServiceInterface
{
    private string $mandator = 'defaultMandator';

    #[MlcCacheableMethod(
        ttlSeconds: 600,
		additionalCacheKeyGetter: 'getAdditionalCacheKeys',
    )]
    public function doThings(string $input): string
    {
        return strrev($input);
    }

    public function getAdditionalCacheKeys(): array
    {
        return [
            'leMandator' => $this->mandator,
        ];
    }
}
```


#### bulkConfig

Sometimes you have a method that accepts an array of ids that you want to fetch in a more performant way. Maybe by a bulk SQL query or via parallel CURL requests or other methods. Those requests are very inefficient to cache with just one entry because every change in the list will create a new cache key and therefore a separate entry in the cache.
This means:
- A lot of wasted storage,
- horrible cache hit rates,
- and huge cache entries.

To solve this problem the MLC provides a solution in form of BulkConfig.

For details see the [Bulk Requests](./mlc-service-and-factory.md#getbulk) section in the documentation.


##### How to activate it

To use Bulk requests you first need to know about it's requirements.
1. The first argument must be an array of Identifiers used to fetch the data
2. The method must return an array of result objects or arrays
3. Each result object needs to have the identifier in it's data structure

Those requirements are needed for the cache do be able to split, read, cache, and combine the requests.

Now on how to enable this feature.

You need to add the `bulkConfig` attribute to the `MlcCacheableMethod`
```php
#[MlcCacheableMethod(
	ttlSeconds: 300,
	bulkConfig: new BulkConfig(
		identifierSelector: 'getKey',
		listType: BulkListTypeEnum::ARRAY_NUMERIC,
	)
)]
public function bulkRequest(array $ids, int $anotherArgument, string $prefix = 'default'): array
{
	return $this->doParallelRequests($ids);
}
```

As already mentioned the `$ids` argument must be an array of Identifiers.

All other arguments are passed through to the source function and are taken into account for Cache Key generation.

The response is an `object[]` or an `array[]`.

The `identifierSelector` is a dot separated string trough the getters and keys of the array to the `identifier`
So for example with the following data structure
```php
[
	'responseObject1' => [
		'id' => 'myuuid1',
		'value' => 'value1',
	],
	'responseObject2' => [
		'id' => 'myuuid2',
		'value' => 'value2',
	],
	//...
]
```
The `identifierSelector` would just be `id`.

If your data structure is nested like this
```php
[
	'responseObject1' => [
		'entity' => [
			'getId' => 'myuuid1',
			'name' => 'entityName1'
		],
		'value' => 'value1',
	],
	'responseObject2' => [
		'entity' => [
			'getId' => 'myuuid2',
			'name' => 'entityName2'
		],
		'value' => 'value2',
	],
	//...
]
```
The `identifierSelector` would be `entity.getId`

If you have real objects you just use the getter Name without the `()`.

Last but not least we currently have two response types implemented.
- Numeric array of response objects
- Associative array of response objects (Indexed by the identifier)

You can select which one you want via the `listType` argument and passing in a `BulkListTypeEnum`

### MlcCacheableService

Now we haven't talked about this one. It's more of a metadata Attribute that controls the general setup and behaviour of the Cached Service.

It has the following parameters, all of them are optional:

#### additionalInterface

Sometimes you need to have the created Cached Service to implement an additional Interface. For example when you're using autowiring or dependency injection. This parameter allows you to specify that interface and it will be added to the generated class.

This does not affect the generated `TestServiceInterface`. It is still generated and implemented.

Please note that the Cached Service will already implement all interfaces of the Source Service. So you only need to use this if you want to add an interface that is not implemented by the Original Service.

#### dataVersion

The Cacheable Service Attribute also has a `dataVersion` parameter. This one serves as a default if the method does not specify one. So you can set a general version for the whole service here and only override it for specific methods that need it.

It might save you some time and makes managing of versions easier. You can choose what fits your needs best.

#### cacheType

The `cacheType` parameter allows you do select one of the existing presets for the cache setup of this one service.

It uses the `CacheTypeEnum` as an argument.

You can look at the [MLC](mlc-service-and-factory.md) documentation for more details on the different cache types and what they do.

#### inMemoryCacheMaxSize

By default the in memory cache has a max size of 100 entries. This argument allows you to modify this maximum.

This can be helpfull depending on your memory constraints or use case. Use as you see fit.

#### additionalCacheKeyGetter
The same as the Method version [see here](#additionalcachekeygetter) but it is applied to all cached methods.

Usage example:
```php
#[MlcCacheableService(
		additionalCacheKeyGetter: 'getAdditionalCacheKeys',
)]
class TestService implements TestServiceInterface
{
    private string $mandator = 'defaultMandator';

    #[MlcCacheableMethod(
        ttlSeconds: 600,
    )]
    public function doThings(string $input): string
    {
        return strrev($input);
    }

    public function getAdditionalCacheKeys(): array
    {
        return [
            'leMandator' => $this->mandator,
        ];
    }
}
```

# InvalidatorService

If you generate a Cached Service, one more benefit you get is the `InvalidatorService`.

In short it allows you to easily invalidate cache entries for a given Service Class, or a specific method of a Service.

So, how does it work?

You inject the `InvalidatorService` into your Service and then you can call either the `invalidateCacheForClass` or the `invalidateCacheForMethod` method with the appropriate arguments.

```php
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Service\InvalidatorService;

readonly class MyService
{
	public function __construct(
		private InvalidatorService $invalidatorService,
	) {}

	public function invalidateThings():void {
		// This will invalidate all cache entries for the SomeService class, regardless of the method.
		$this->invalidatorService->invalidateCacheForClass(SomeService::class);

		// This will invalidate all cache entries for the someMethod of the SomeService class.
		// All other cache entries for the SomeService class that are not related to someMethod will not be affected.
		$this->invalidatorService->invalidateCacheForMethod(AnotherService::class, 'someMethod');
	}
}
```