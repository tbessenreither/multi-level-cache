<?php

declare(strict_types=1);

use Tbessenreither\MultiLevelCache\CachedServiceGenerator\Attribute\MlcCachedService;

#[MlcCachedService(
    originalServiceClass: 'asdf',
    allowSync: false,
)]
