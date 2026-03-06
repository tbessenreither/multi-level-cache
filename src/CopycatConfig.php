<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache;

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
                'REDIS_DSN' => 'redis://redis:6379',
                'MLC_DISABLE_READ' => 'does not matter, this var existing will disable reads',
                'MLC_COLLECT_ENHANCED_DATA' => false,
            ],
            overwrite: true,
        );
        $copycat->envAdd(
            target: EnvTargetEnum::DOT_LOCAL,
            entries: [
                'REDIS_DSN' => 'redis://redis:6379',
                'MLC_COLLECT_ENHANCED_DATA' => false,
            ],
            overwrite: false,
        );
    }

}
