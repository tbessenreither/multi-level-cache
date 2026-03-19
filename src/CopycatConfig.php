<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache;

use Tbessenreither\Copycat\Dto\EnvVar;
use Tbessenreither\Copycat\Enum\CopyTargetEnum;
use Tbessenreither\Copycat\Enum\EnvTargetEnum;
use Tbessenreither\Copycat\Interface\CopycatConfigInterface;
use Tbessenreither\Copycat\Interface\CopycatInterface;

class CopycatConfig implements CopycatConfigInterface
{
    public static function run(CopycatInterface $copycat): void
    {
        $copycat->copy(
            target: CopyTargetEnum::DDEV_COMMANDS_WEB,
            file: 'bin/mlc-make',
            overwrite: true,
            gitIgnore: true,
        );
        $copycat->copy(
            target: CopyTargetEnum::DDEV_COMMANDS_WEB,
            file: 'bin/mlc-update',
            overwrite: true,
            gitIgnore: true,
        );

        $copycat->symfonyBundleAdd(
            bundleClassName: MultiLevelCacheBundle::class,
        );

        $copycat->envAdd(
            target: EnvTargetEnum::DOT_EXAMPLE,
            entries: [
                new EnvVar(
                    name: 'REDIS_DSN',
                    value: 'redis://redis:6379',
                    description: 'The DSN for the Redis server used by the Multi-Level Cache Bundle.',
                ),
                new EnvVar(
                    name: 'MLC_DISABLE_READ',
                    isFlag: true,
                    description: 'When present, the Multi-Level-Cache will not perform cache reads and always call the source, but it will still perform cache writes. This can be useful for testing or debugging purposes.',
                ),
                new EnvVar(
                    name: 'MLC_COLLECT_ENHANCED_DATA',
                    value: true,
                    description: 'When true the profiler will collect extra data for statistics and debugging. This will take more time and memory, so it should only be enabled in development environments.',
                ),
            ],
            overwrite: true,
        );
        $copycat->envAdd(
            target: EnvTargetEnum::DOT_LOCAL,
            entries: [
                new EnvVar(
                    name: 'REDIS_DSN',
                    value: 'redis://redis:6379',
                    description: 'The DSN for the Redis server used by the Multi-Level Cache Bundle.',
                ),
                new EnvVar(
                    name: 'MLC_COLLECT_ENHANCED_DATA',
                    value: true,
                    description: 'When true the profiler will collect extra data for statistics and debugging. This will take more time and memory, so it should only be enabled in development environments.',
                ),
            ],
            overwrite: false,
        );
    }

}
