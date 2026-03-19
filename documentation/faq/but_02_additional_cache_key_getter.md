# Exposing internal properties to the MLC for Key generation

The response of the cached method is dependend on some internal state that is not present in the method arguments.

Let's say we have this example:

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;

class TestService
{
    private string $stringToReturn = 'something';

    #[MlcCacheableMethod(ttlSeconds: 300)]
    public function doSomething(): string
    {
        // very expensive operation here, e.g. a call to an external API or a complex calculation
        sleep(2); // Simulate a delay

        return $this->stringToReturn;
    }

    public function setStringToReturn(string $string): void
    {
        $this->stringToReturn = $string;
    }

}
```

This would cause the MLC to cache the first response and then ignore all changes to `$this->stringToReturn`. That's not good.

Let's fix this by setting the `additionalCacheKeyGetter` property of `MlcCacheableService`.

```php
<?php

declare(strict_types=1);

namespace App\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCacheableMethod;

#[MlcCacheableService(additionalCacheKeyGetter: 'getAdditionalCacheKeys')]
class TestService
{
    private string $stringToReturn = 'something';

    #[MlcCacheableMethod(ttlSeconds: 300)]
    public function doSomething(): string
    {
        // very expensive operation here, e.g. a call to an external API or a complex calculation
        sleep(2); // Simulate a delay

        return $this->stringToReturn;
    }

    public function setStringToReturn(string $string): void
    {
        $this->stringToReturn = $string;
    }

    public function getAdditionalCacheKeys(): array
    {
        return [
            'importantString' => $this->stringToReturn,
        ];
    }

}
```

Now the MLC is aware that there is more to your Service function than the method attributes and everything should work as expected.

Additional Bonus Fact:

You can return any serializable data through the getter. As long as PHPs `serialize()` can handle it, the MLC is fine with it.