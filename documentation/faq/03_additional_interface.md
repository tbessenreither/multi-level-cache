# Adding an additional interface to the cached service

We have this service:

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

What we now want to do is add the `MlcCacheableService` attribute to the class and set the `additionalInterface` property.

For our example we will use `SomeGreatInterface`.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use App\Interface\SomeGreatInterface;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;
use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableService;
use Tbessenreither\MultiLevelCache\Enum\CacheTypeEnum;

#[MlcCacheableService(additionalInterface: SomeGreatInterface::class)]
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

Then you update the cached service by running `ddev mlc-update`.