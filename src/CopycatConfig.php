<?php declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache;

use Tbessenreither\PhpCopycat\Copycat;
use Tbessenreither\PhpCopycat\Enum\CopyTargetEnum;
use Tbessenreither\PhpCopycat\Interface\CopycatConfigInterface;


class CopycatConfig implements CopycatConfigInterface
{

    public static function run(Copycat $copycat): void
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
