<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Service;

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachableMethod;

class TestService
{
    #[MlcCachableMethod(ttlSeconds: 120)]
    public function sayHello(string $name): string
    {
        return "Hello, " . $name . "!";
    }
}
