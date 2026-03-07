<?php

declare(strict_types=1);

namespace Tbessenreither\MultiLevelCache;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tbessenreither\MultiLevelCache\DependencyInjection\Compiler\CompilerPass;

class MultiLevelCacheBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if ($container->isCompiled()) {
            return;
        }

        $container->addCompilerPass(new CompilerPass());
    }

}
