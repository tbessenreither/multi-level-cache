<?php declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache;

use Tbessenreither\PhpCopycat\Enum\CopyTargetEnum;
use Tbessenreither\PhpCopycat\Interface\CopycatConfigInterface;
use Tbessenreither\PhpCopycat\Interface\CopycatInterface;


class CopycatConfig implements CopycatConfigInterface
{

    public static function run(CopycatInterface $copycat): void
    {
        $copycat->copy(
            target: CopyTargetEnum::DDEV_COMMANDS_WEB,
            file: 'bin/mlc-make',
        );
        $copycat->copy(
            target: CopyTargetEnum::DDEV_COMMANDS_WEB,
            file: 'bin/mlc-update',
        );
    }

}
