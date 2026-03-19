# Simple example of a cached service:

Given you have the following Service that you want a cached Version of.

```php
<?php

declare(strict_types=1);

namespace App\Service;

class TestService
{
    public function doSomething(): string
    {
        // very expensive operation here, e.g. a call to an external API or a complex calculation
        sleep(2); // Simulate a delay

        return "something";
    }

}
```

## Preparing the source service
First we need to tell the MLC what methods can be cached and how long to hold the data for.

We do that by adding the `MlcCacheableMethod` attribute to each method we want cached and set the `ttlSeconds` property accordingly.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;

class TestService
{

    #[MlcCacheableMethod(ttlSeconds: 300)]
    public function doSomething(): string
    {
        // very expensive operation here, e.g. a call to an external API or a complex calculation
        sleep(2); // Simulate a delay

        return "something";
    }

}
```

## Generating the Cached Version

We now run the `ddev mlc-make App.Service.TestService` command.

```
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

## Using the Service

To use the new Service you can:
- Swap the `TestService` to `TestServiceCached` to always use the cached version, or
- Set the Injection to `TestServiceInterface` and configure the dependency injection via your symfony services.yaml