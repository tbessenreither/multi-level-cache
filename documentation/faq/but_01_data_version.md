# Preventing data colissions when a DTO changes

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

What we want to do is tell the MLC about the change in Data structure.

We do this by setting the `dataVersion` property of either the `MlcCacheableMethod` or as a default in `MlcCacheableService`. The later will act as the default data version for all cached methods.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;

class TestService
{

    #[MlcCacheableMethod(
        ttlSeconds: 300,
        dataVersion: '1',
    )]
    public function doSomething(): string
    {
        // very expensive operation here, e.g. a call to an external API or a complex calculation
        sleep(2); // Simulate a delay

        return "something";
    }

}
```

The Data Version can be any string. I would recommend starting with 1 and then just counting up every time you change something.

That's it. Now the MLC knows about your DTO changes and can keep the variants separate.