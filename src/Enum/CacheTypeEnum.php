<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache\Enum;

enum CacheTypeEnum: string
{
    case DEFAULT = 'createDefault2LevelCache';
    case IN_MEMORY = 'createInMemoryOnlyCache';
    case REDIS = 'createRedisOnlyCache';
}
